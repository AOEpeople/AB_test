<?php

/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 08.08.2016
 * Time: 10:03
 */
class MysqlAdapter extends AbstractDbAdapter {

	protected function connect() {
		$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8;', $this->host, $this->database);

		try {
			$this->db = new \PDO($dsn, $this->username, $this->password);
		} catch(\PDOException $e) {
			$this->logger->log('Unable to connect to database.', Logger::LOG_ERROR);
			exit(1);
		}
	}

	/**
	 * @param  string $query        SQL Query
	 * @return PDOStatement
	 */
	public function query($query) {
		return $this->getDbConnector()->query($query, PDO::FETCH_ASSOC);
	}
}
