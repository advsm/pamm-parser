<?php

/**
 * Объект описания ПАММ-счета.
 * Используется и для парсинга и для сохранения в БД.
 */
class Pamm
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
	 */
	public function addWeekStats($weekNumber, $year, $percent)
	{

	}

	/**
	 * Сохраняет ПАММ-счет в базе данных.
	 *
	 * @return void
	 */
	public function save()
	{
		echo "Сохраняем информацию по счету {$this->getName()} ({$this->getVendorId()})\n";
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
		$this->startedAt = trim($startedAt);
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
}