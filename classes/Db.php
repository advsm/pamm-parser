<?php

/**
 * Класс-обертка для PDO.
 */
class Db
{
	/**
	 * Экземпляр текущего класса.
	 *
	 * @var Db
	 */
	private static $instance;

	/**
	 * Экземпляр PDO
	 * @var PDO
	 */
	private $pdo;

	/**
	 * Возвращает экземпляр класса Db
	 *
	 * @return Db
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Возвращает коннект к PDO.
	 * Коннектится только при первом вызове.
	 *
	 * @return PDO
	 */
	public function getPDO()
	{
		if (!$this->pdo) {
			$this->pdo = new PDO('mysql:dbname=pamm;host=127.0.0.1', 'root', '');
		}

		return $this->pdo;
	}
}