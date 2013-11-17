<?php

require_once './classes/Db.php';

/**
 * Класс базовой модели.
 * Необходим для проведения запросов к БД.
 */
class BaseModel
{
	/**
	 * Устанавливает ID.
	 *
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * Возвращает ID.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * ID из БД.
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Возвращает экземпляр PDO.
	 *
	 * @return PDO
	 */
	protected function getPdo()
	{
		return Db::getInstance()->getPDO();
	}

	/**
	 * Возвращает последнюю ошибку БД.
	 *
	 * @return string
	 */
	public function getLastError()
	{
		return array_pop($this->getPdo()->errorInfo());
	}
}