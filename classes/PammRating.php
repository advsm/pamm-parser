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
	const URI_RATING   = "http://fx-trend.com/pamm/rating/";
	const CSS_SELECTOR = "table.my_accounts_table[1] tr";

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
	 * @return DOMElement[]
	 */
	private function getAllPammsRaw()
	{
		$pamms = [];

		$html = $this->requestUri(self::URI_RATING);
		$dom = new Query($html);
		$elements = $dom->execute(self::CSS_SELECTOR);

		$needToParse = false;
		foreach ($elements as $element) {
			// Пропускаем первую таблицу, начинаем парсить только после того, как увидим заголовок второй таблицы
			if (strlen($element->nodeValue) > 270) {
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
			if (strlen($element->nodeValue) > 250) {
				continue;
			}

			// Убираем пустые tr'ы
			if (!$element->nodeValue) {
				continue;
			}

			$pamms[] = $element;
		}

		return $pamms;
	}

	/**
	 * Заходит на страницу рейтинга ПАММ-счетов и получает всю информацию о них.
	 */
	public function getAllPamms()
	{
		$elements = $this->getAllPammsRaw();
		foreach ($elements as $element) {
			$pamm = Pamm::fromRatingTable($element);
			$this->getSpecifics($pamm);
			$pamm->save();
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

		$uri = "http://fx-trend.com/pamm/{$pamm->getVendorId()}/";
		$html = $this->requestUri($uri);

		$dom = new Query($html);
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


}