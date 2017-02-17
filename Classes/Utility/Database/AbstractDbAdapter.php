<?php

/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 08.08.2016
 * Time: 10:23
 */
abstract class AbstractDbAdapter {

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $database;

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * Database Connector
	 *
	 * @var \PDO
	 */
	protected $db;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param  string $username
	 * @param  string $password
	 * @param  string $database
	 * @param  string $host
	 */
	public function __construct($username, $password, $database, $host) {
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		$this->host     = $host;

		$this->db = NULL;
	}

	/**
	 * Establishes database connection.
	 *
	 * @return void
	 */
	abstract protected function connect();

	/**
	 * @return \PDO
	 */
	protected function getDbConnector() {
		if (is_null($this->db)) {
			$this->connect();
		}

		return $this->db;
	}

	/**
	 * @param  Logger $logger
	 */
	public function setLogger(Logger $logger) {
		$this->logger = $logger;
	}
}
