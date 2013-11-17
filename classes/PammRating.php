<?php

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Uri\Uri;
use Zend\Dom\Query;

/**
 * Класс управляет парсингом таблицы рейтинга ПАММ-счетов.
 */
class PammRating
{
	/**
	 * Массив с HTML содержимым страничек конкретных ПАММ-счетов.
	 *
	 * @var array
	 */
	private $pammHtml = [];

	/**
	 * Инкапсулирует работу с Zend\Http\Client. Возвращает сразу HTML код переданного Uri.
	 *
	 * @param string $uri
	 * @return string HTML код страницы.
	 */
	private function requestUri($uri)
	{
		$request = new Request();
		$request->setUri($uri);

		$client = new Client();
		$response = $client->send($request);

		return $response->getBody();
	}

	/**
	 * Заходит на страницу рейтинга ПАММ-счетов и получает список DOMElement'ов с ПАММ-счетами.
	 *
	 * @param int $page Номер страницы рейтинга
	 * @return DOMElement[]
	 */
	private function getAllPammsRaw($page)
	{
		$pamms = [];

		$html = $this->requestUri("http://fx-trend.com/pamm/rating/?rep_page=$page");
		$dom = new Query($html);
		$elements = $dom->execute("table.my_accounts_table[1] tr");

		$needToParse = false;
		foreach ($elements as $element) {
			// Пропускаем первую таблицу, начинаем парсить только после того, как увидим заголовок второй таблицы
			if (substr($element->nodeValue, 0, 6) === 'Ник') {
				if ($needToParse) {
					break;
				}

				$needToParse = true;
				continue;
			}

			if (!$needToParse) {
				continue;
			}

			// Убираем строки с заголовками
			if (strlen($element->nodeValue) > 170) {
				continue;
			}

			// Убираем пустые tr'ы
			if (!$element->nodeValue) {
				continue;
			}

			$pamms[] = $element;
			var_dump($element->nodeValue);
		}

		return $pamms;
	}

	/**
	 * Заходит на страницу рейтинга ПАММ-счетов и получает всю информацию о них.
	 */
	public function getAllPamms()
	{
		foreach (range(1, 29) as $page) {
			$elements = $this->getAllPammsRaw($page);
			foreach ($elements as $element) {
				$pamm = Pamm::fromRatingTable($element);
				$this->getSpecifics($pamm);
				$pamm->save();
			}
		}

	}

	/**
	 * Заходит на страницу конкретного ПАММ-счета, используя ID, и получает всю дополнительную информацию.
	 *
	 * @param Pamm $pamm
	 */
	public function getSpecifics(Pamm $pamm)
	{
		// Если информация уже есть в БД, просто возвращаем её.
		if ($pamm->getSpecifics()) {
			return;
		}

		if (!$this->hasPammOffer($pamm)) {
			return;
		}

		$this->loadInvestorPercent($pamm);
		$this->loadMinimalBalance($pamm);
		$this->loadAgentProfit($pamm);
		$this->loadAgentDeposit($pamm);
		$this->loadStartCapital($pamm);
		$this->loadCurrentCapital($pamm);
		$this->loadFullCapital($pamm);



		echo $pamm->toString();
		$pamm->save();

		$dom = new Query($this->getPammHtml($pamm));
		$elements = $dom->execute('table.my_accounts_table tr');


		$needToParse = false;
		foreach ($elements as $element) {
			if (!$element->nodeValue) {
				continue;
			}

			// Как только мы находим заголовок таблицы - начинаем парсить понедельную статистику.
			if (substr($element->nodeValue, 0, 12) === 'Неделя') {
				$needToParse = true;
				continue;
			}

			if (!$needToParse) {
				continue;
			}

			$info = explode("\t\t\t\t\t", $element->nodeValue);

			// Преобразовываем диапазон вида 11.11.2013 - 17.11.2013 в порядковый номер недели
			$mondayDate      = substr($info[0], 0, 10);
			$mondayTimestamp = strtotime($mondayDate);

			$weekNumber = date('W', $mondayTimestamp);
			$year       = date('Y', $mondayTimestamp);
			$percent = rtrim($info[2], "%\t\n");

			$pamm->addWeekStats($weekNumber, $year, $percent);
		}
	}

	/**
	 * Проверяет, есть ли у ПАММ-счета публичная оферта. Если её нет, он нас не интересует.
	 *
	 * @param Pamm $pamm
	 * @return bool
	 */
	private function hasPammOffer(Pamm $pamm)
	{
		$matches = [];
		preg_match('#У данного счета отсутствует оферта#isu', $this->getPammHtml($pamm), $matches);

		if (isset($matches[0])) {
			return false;
		}

		return true;
	}

	/**
	 * Возвращает процент вознаграждения инвестора.
	 * Подгружает HTML страницу конкретного ПАММ-счета.
	 *
	 * @return int
	 */
	private function loadInvestorPercent(Pamm $pamm)
	{
		preg_match(
			'#<td align="right">Вознаграждение:\&nbsp\;<\/td>\s*<td>(\d{1,2}) %<\/td>#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$pamm->setInvestorPercent(100 - $matches[1]);
		return $pamm->getInvestorPercent();
	}

	/**
	 * Возвращает минимальный депозит для ПАММ-счета.
	 *
	 * @param Pamm $pamm
	 * @return int
	 */
	private function loadMinimalBalance(Pamm $pamm)
	{
		preg_match(
			'#<td align="right">Мин\. сумма и остаток:\&nbsp\;<\/td>\s*<td>([\d\.]{4,8}) USD<\/td>#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$pamm->setMinimalBalance($matches[1]);
		return $pamm->getMinimalBalance();
	}

	/**
	 * Прогружает и возвращает вознаграждение агента с профита инвестора ПАММ-счета.
	 *
	 * @param Pamm $pamm
	 * @return int
	 */
	private function loadAgentProfit(Pamm $pamm)
	{
		preg_match(
			'#Привлеки инвестора в этот ПАММ и получи ([\d\.]{4})% от прибыли инвестора#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$agentProfit = 0;
		if (isset($matches[1])) {
			$agentProfit = $matches[1];
		}

		$pamm->setAgentProfit($agentProfit);
		return $pamm->getAgentProfit();
	}

	/**
	 * Прогружает и возвращает вознаграждение агента с депозита привлеченного на данный ПАММ-счет инвестора.
	 *
	 * @param Pamm $pamm
	 * @return int
	 */
	private function loadAgentDeposit(Pamm $pamm)
	{
		preg_match(
			'#Привлеки инвестора в этот ПАММ и получи ([\d\.]{4})% от суммы инвестиций#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$agentDeposit = 0;
		if (isset($matches[1])) {
			$agentDeposit = $matches[1];
		}

		$pamm->setAgentDeposit($agentDeposit);
		return $pamm->getAgentDeposit();
	}

	/**
	 * Прогружает и устанавливает стартовый капитал управляющего.
	 *
	 * @param Pamm $pamm
	 * @return int
	 */
	private function loadStartCapital(Pamm $pamm)
	{
		preg_match(
			'#<td><b>Начальный капитал управляющего:<\/b><\/td>\s*<td align="right">([\-\d\.]{4,10}) USD<\/td>#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$pamm->setStartCapital($matches[1]);
		return $pamm->getStartCapital();
	}

	/**
	 * Прогружает и устанавливает текущий капитал управляющего.
	 *
	 * @param Pamm $pamm
	 * @return int
	 */
	private function loadCurrentCapital(Pamm $pamm)
	{
		preg_match(
			'#<td><b>Tекущий капитал управляющегo:<\/b><\/td>\s*<td[^>]*>([\-\d\.]{4,12}) USD<\/td>#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$pamm->setCurrentCapital($matches[1]);
		return $pamm->getCurrentCapital();
	}

	/**
	 * Прогружает и устанавливает совокупный полный капитал управляющего и всех инвесторов.
	 *
	 * @param Pamm $pamm
	 * @return int
	 */
	private function loadFullCapital(Pamm $pamm)
	{
		preg_match(
			'#<td><b>Cумма в управлении:<\/b><\/td>\s*<td[^>]*>([\-\d\.]{4,12}) USD<\/td>#isu',
			$this->getPammHtml($pamm),
			$matches
		);

		$pamm->setFullCapital($matches[1]);
		return $pamm->getCurrentCapital();
	}

	/**
	 * Подгружает HTML страницу конкретного ПАММа для дальнейшего парсинга.
	 *
	 * @param Pamm $pamm
	 * @return string
	 */
	private function getPammHtml(Pamm $pamm)
	{
		if (isset($this->pammHtml[$pamm->getVendorId()])) {
			return $this->pammHtml[$pamm->getVendorId()];
		}

		$uri = "http://fx-trend.com/pamm/{$pamm->getVendorId()}/";
		return $this->pammHtml[$pamm->getVendorId()] = $this->requestUri($uri);
	}

}