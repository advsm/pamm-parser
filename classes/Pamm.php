<?php

require_once './classes/BaseModel.php';
require_once './classes/PammWeekStat.php';

/**
 * Объект описания ПАММ-счета.
 * Используется и для парсинга и для сохранения в БД.
 */
class Pamm extends BaseModel
{
	/**
	 * Название ПАММ-счета на сайте брокера.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * ID ПАММ-счета на сайте брокера.
	 *
	 * @var int
	 */
	private $vendorId;

	/**
	 * Дата открытия ПАММ-счета.
	 *
	 * @var string
	 */
	private $startedAt;

	/**
	 * Вознаграждение инвестора, в процентах.
	 *
	 * @var int
	 */
	private $investorPercent;

	/**
	 * Минимальный депозит инвестора.
	 *
	 * @var int
	 */
	private $minimalBalance;

	/**
	 * Вознаграждение агенту с прибыли Инвестора данного ПАММ-счета.
	 *
	 * @var int
	 */
	private $agentProfit;

	/**
	 * Вознаграждение агента c депозита инвестора на данный ПАММ-счет.
	 *
	 * @var int
	 */
	private $agentDeposit;

	/**
	 * Начальный капитал управляющего.
	 *
	 * @var int
	 */
	private $startCapital;

	/**
	 * Текущий капитал управляющего.
	 *
	 * @var int
	 */
	private $currentCapital;

	/**
	 * Полный капитал управляющего.
	 * Вычисляется как КУ + совокупный капитал инвесторов.
	 *
	 * @var int
	 */
	private $fullCapital;

	/**
	 * Массив с записями статистики текущего ПАММ-счета.
	 *
	 * @var PammWeekStat[]
	 */
	private $weekStats = [];

	/**
	 * Создает ПАММ-счет с базовой информацией из строки таблицы рейтинга.
	 *
	 * @param DOMElement $row
	 * @return $this
	 */
	public static function fromRatingTable(DOMElement $row)
	{
		$info = explode("\t", $row->nodeValue);

		// Информация, которая нам нужна - имя памм счета, id и дата создания
		return (new self())
			->setName($info[0])
			->setVendorId($info[1])
			->setStartedAt($info[2]);
	}

	/**
	 * Получает информацию о текущем счете из БД, если она там есть.
	 *
	 * @return bool
	 */
	public function getSpecifics()
	{
		//echo "Получаем специфичную информацию по счету {$this->getName()} ({$this->getVendorId()})\n";
		return false;
	}

	/**
	 * Добавляет запись о статистике ПАММ-счета за переданную неделю.
	 * Если такая запись уже есть в БД, дубликат не делается.
	 *
	 * @param int $weekNumber ex: 42
	 * @param int $year       ex: 2013
	 * @param float $percent  ex: 2.14
	 * @return bool
	 */
	public function addWeekStats($weekNumber, $year, $percent)
	{
		return (new PammWeekStat())
			->setPammId($this->getId())
			->setWeek($weekNumber)
			->setYear($year)
			->setProfitPercent($percent)
			->save();
	}

	/**
	 * Сохраняет ПАММ-счет в базе данных.
	 *
	 * @return void
	 */
	public function save()
	{
		$stmt = $this->getPdo()->query("select id from pamm where vendor_id = {$this->getVendorId()} limit 1");
		if ($stmt === false) {
			throw new PDOException('Произошла ошибка при select из pamm: ' . $this->getLastError());
		}

		$stmt->execute();
		$row = $stmt->fetch();
		if ($row) {
			$this->setId($row['id']);
			return false;
		}

		$date = date('Y-m-d H:i:s');
		$result = $this->getPdo()->exec(
			"insert into pamm set
				`vendor_id`        = {$this->getVendorId()},
				`broker_id`        = 1,
				`name`             = '{$this->getName()}',
				`started_at`       = '{$this->getStartedAt()}',
				`investor_percent` = '{$this->getInvestorPercent()}',
				`minimal_balance`  = '{$this->getMinimalBalance()}',
				`agent_deposit`    = '{$this->getAgentDeposit()}',
				`agent_profit`     = '{$this->getAgentProfit()}',
				`start_capital`    = '{$this->getStartCapital()}',
				`current_capital`  = '{$this->getCurrentCapital()}',
				`full_capital`     = '{$this->getFullCapital()}',
				`created_at`       = '$date',
				`stats_updated_at` = '$date'
			"
		);

		$this->setId($this->getPdo()->lastInsertId());

		if ($result === false) {
			throw new PDOException("Ошибка вставки в таблицу pamm_stat_week: " . $this->getLastError());
		}

		return true;
	}


	/**
	 * Устанавливает имя ПАММ-счета.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = trim($name);
		return $this;
	}

	/**
	 * Устанавливает внешний ID ПАММ-счета.
	 *
	 * @param int $vendorId
	 * @return $this
	 */
	public function setVendorId($vendorId)
	{
		$this->vendorId = intval(trim($vendorId));
		return $this;
	}

	/**
	 * Устанавливает дату открытия ПАММ-счета
	 *
	 * @param string $startedAt
	 * @return $this
	 */
	public function setStartedAt($startedAt) {
		$this->startedAt = date('Y-m-d', strtotime(trim($startedAt)));
		return $this;
	}

	/**
	 * Возвращает название ПАММ-счета.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Возвращает внешний ID ПАММ-счета.
	 *
	 * @return int
	 */
	public function getVendorId()
	{
		return $this->vendorId;
	}

	/**
	 * Возвращает дату открытия ПАММ-счета.
	 *
	 * @return string
	 */
	public function getStartedAt()
	{
		return $this->startedAt;
	}

	/**
	 * Устанавливает вознаграждение агента с депозита привлеченного инвестора.
	 *
	 * @param int $agentDeposit
	 * @return $this
	 */
	public function setAgentDeposit($agentDeposit)
	{
		$this->agentDeposit = $agentDeposit;
		return $this;
	}

	/**
	 * Возвращает вознаграждение агента с депозита привлеченного инвестора.
	 *
	 * @return int
	 */
	public function getAgentDeposit()
	{
		return $this->agentDeposit;
	}

	/**
	 * Устанавливает вознаграждение агента с прибыли привлеченного на ПАММ-счет инвестора.
	 *
	 * @param int $agentProfit
	 * @return $this
	 */
	public function setAgentProfit($agentProfit)
	{
		$this->agentProfit = $agentProfit;
		return $this;
	}

	/**
	 * Возвращает вознаграждение агента с прибыли привлеченного на ПАММ-счет инвестора.
	 *
	 * @return int
	 */
	public function getAgentProfit()
	{
		return $this->agentProfit;
	}

	/**
	 * Устанавливает стартовый капитал управляющего данного ПАММ-счета.
	 *
	 * @param int $startCapital
	 * @return $this
	 */
	public function setStartCapital($startCapital)
	{
		$this->startCapital = $startCapital;
		return $this;
	}

	/**
	 * Возвращает стартовый капитал управляющего данного ПАММ-счета.
	 *
	 * @return int
	 */
	public function getStartCapital()
	{
		return $this->startCapital;
	}

	/**
	 * Устанавливает текущий капитал управляющего данного ПАММ-счета.
	 *
	 * @param int $currentCapital
	 * @return $this
	 */
	public function setCurrentCapital($currentCapital)
	{
		$this->currentCapital = $currentCapital;
		return $this;
	}

	/**
	 * Возвращает текущий капитал управляющего данного ПАММ-счета.
	 *
	 * @return int
	 */
	public function getCurrentCapital()
	{
		return $this->currentCapital;
	}

	/**
	 * Устанавливает полный капитал ПАММ-счета. Формируется как КУ + совместный капитал всех инвесторов.
	 *
	 * @param int $fullCapital
	 * @return $this
	 */
	public function setFullCapital($fullCapital)
	{
		$this->fullCapital = $fullCapital;
		return $this;
	}

	/**
	 * Возвращает полный капитал ПАММ-счета. Формируется как КУ + совместный капитал всех инвесторов.
	 *
	 * @return int
	 */
	public function getFullCapital()
	{
		return $this->fullCapital;
	}

	/**
	 * Устанавливает процент вознаграждение инвестора в данном ПАММ-счете.
	 *
	 * @param int $investorPercent
	 * @return $this
	 */
	public function setInvestorPercent($investorPercent)
	{
		$this->investorPercent = $investorPercent;
		return $this;
	}

	/**
	 * Возвращает процент вознаграждение инвестора в данном ПАММ-счете.
	 *
	 * @return int
	 */
	public function getInvestorPercent()
	{
		return $this->investorPercent;
	}

	/**
	 * Устанавливает минимальный депозит инвестора для данного ПАММ-счета.
	 *
	 * @param int $minimalBalance
	 * @return $this
	 */
	public function setMinimalBalance($minimalBalance)
	{
		$this->minimalBalance = $minimalBalance;
		return $this;
	}

	/**
	 * Возвращает минимальный депозит инвестора для данного ПАММ-счета.
	 *
	 * @return int
	 */
	public function getMinimalBalance()
	{
		return $this->minimalBalance;
	}

	/**
	 * Текстовое описание ПАММ-счета.
	 *
	 * @return string
	 */
	public function toString()
	{
		return sprintf(
			"%s %s, создан %s, минимальный депозит %s USD, вознаграждение инвестору %s%%\n" .
			"КУ на старте %s USD, сейчас %s USD, полный %s\n" .
			"Агенту с депозита дается %s%%, с профита %s%% \n\n",
			$this->getVendorId(),
			$this->getName(),
			$this->getStartedAt(),
			$this->getMinimalBalance(),
			$this->getInvestorPercent(),
			$this->getStartCapital(),
			$this->getCurrentCapital(),
			$this->getFullCapital(),
			$this->getAgentDeposit(),
			$this->getAgentProfit()
		);
	}
}