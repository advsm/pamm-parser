<?php

require_once './classes/BaseModel.php';

/**
 * Класс описания недельной статистику ПАММ-счета.
 */
class PammWeekStat extends BaseModel
{
	/**
	 * ID ПАММ-счета из базы.
	 *
	 * @var int
	 */
	private $pammId;

	/**
	 * Год записи статистики.
	 * @var int
	 */
	private $year;

	/**
	 * Порядковый номер недели записи статистики.
	 *
	 * @var int
	 */
	private $week;

	/**
	 * Прибыль в процентах за указанную неделю.
	 *
	 * @var int
	 */
	private $profitPercent;

	/**
	 * Устанавливает ID Памм-счета из базы данных.
	 *
	 * @param int $pammId
	 * @return $this
	 */
	public function setPammId($pammId)
	{
		$this->pammId = $pammId;
		return $this;
	}

	/**
	 * Возвращает ID Памм-счета из базы данных.
	 *
	 * @return int
	 */
	public function getPammId()
	{
		return $this->pammId;
	}

	/**
	 * Устанавливает процент прибыли за неделю.
	 *
	 * @param int $profitPercent
	 * @return $this
	 */
	public function setProfitPercent($profitPercent)
	{
		$this->profitPercent = $profitPercent;
		return $this;
	}

	/**
	 * Возвращает процент прибыли за неделю.
	 *
	 * @return int
	 */
	public function getProfitPercent()
	{
		return $this->profitPercent;
	}

	/**
	 * Устанавливает порядковый номер недели.
	 *
	 * @param int $week
	 * @return $this
	 */
	public function setWeek($week)
	{
		$this->week = $week;
		return $this;
	}

	/**
	 * Возвращает порядковый номер недели.
	 *
	 * @return int
	 */
	public function getWeek()
	{
		return $this->week;
	}

	/**
	 * Устанавливает год записи статистики.
	 *
	 * @param int $year
	 * @return $this
	 */
	public function setYear($year)
	{
		$this->year = $year;
		return $this;
	}

	/**
	 * Возвращает год записи статистики.
	 *
	 * @return int
	 */
	public function getYear()
	{
		return $this->year;
	}

	/**
	 * Сохраняет запись статистики в базе данных.
	 * Если запись уже есть - ничего не делает.
	 *
	 * @return bool
	 * @throws PDOException
	 */
	public function save()
	{
		$stmt = $this->getPdo()->query(
			"
			select id from `pamm_stat_week`
				where `pamm_id` = '{$this->getPammId()}'
				and   `year`    = '{$this->getYear()}'
				and   `week`    = '{$this->getWeek()}'
			limit 1
			"
		);

		if ($stmt === false) {
			throw new PDOException("Ошибка select из таблицы pamm_stat_week: " . $this->getLastError());
		}
		$stmt->execute();

		$row = $stmt->fetch();
		if ($row) {
			return false;
		}

		$result = $this->getPdo()->exec(
			"insert into pamm_stat_week set
				`pamm_id`        = '{$this->getPammId()}',
				`year`           = '{$this->getYear()}',
				`week`           = '{$this->getWeek()}',
				`profit_percent` = '{$this->getProfitPercent()}'
			"
		);

		if ($result === false) {
			throw new PDOException("Ошибка вставки в таблицу pamm_stat_week: " . $this->getLastError());
		}

		return true;
	}
}