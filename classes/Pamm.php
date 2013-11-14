<?php

/**
 * Объект описания ПАММ-счета.
 * Используется и для парсинга и для сохранения в БД.
 */
class Pamm
{
	private $name;
	private $vendorId;
	private $startedAt;

	/**
	 * Создает ПАММ-счет с базовой информацией из строки таблицы рейтинга.
	 *
	 * @param DOMElement $row
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
	 * Заходит на страницу конкретного ПАММ-счета, используя ID, и получает всю дополнительную информацию.
	 */
	public function getSpecifics()
	{
		echo "Получаем специфичную информацию по счету {$this->getName()} ({$this->getVendorId()})\n";
	}


	public function setName($name)
	{
		$this->name = trim($name);
		return $this;
	}

	public function setVendorId($vendorId)
	{
		$this->vendorId = trim($vendorId);
		return $this;
	}

	public function setStartedAt($startedAt) {
		$this->startedAt = trim($startedAt);
		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getVendorId()
	{
		return $this->vendorId;
	}

	public function getStartedAt()
	{
		return $this->startedAt;
	}
}