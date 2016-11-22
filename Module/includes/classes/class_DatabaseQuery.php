<?php

class DatabaseQuery {
	private $log_execute = false;
	private $last_query = false;

	private function writeToLog($sql, $text = '') {
		static $i;
		static $time;
		$fp = fopen('C:/temp/sqllog.txt', 'a');
		fputs($fp, $text."\n".(microtime(true)-$time).' - '.$i++.' - '.preg_replace('/\s+/', ' ', $sql)."\n");
		fclose($fp);
	}
	
	private function connect() {
		$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DB);
		$db->set_charset("utf8");
		return $db;
	}
	
	public function __construct($create_connection = null) {
		if ($create_connection) $mysqli = $this->connect();
		elseif (!$mysqli = request($GLOBALS['mysqli'])) $mysqli = $GLOBALS['mysqli'] = $this->connect();
		
		$this->mysqli = $mysqli;
		echo $this->mysqli->error;
		$this->dummy = 5;
	}

	public function execute($sql) {
		$this->last_query = $sql;
		$start = microtime(true);
		$result = $this->mysqli->query($sql);
		$rs = new DatabaseResultSet($result);
		echo $this->mysqli->error;
		$this->time = microtime(true) - $start;
		if ($this->log_execute)
			$this->writeToLog($sql, $text);
		return $rs;
	}

	public function getNewID() {
		return $this->mysqli->insert_id;
	}

	/**
	 * @return string The last SQL query that was run
	 */
	public function getLastQuery() {
		return str_replace("\t", '', $this->last_query);
	}

	/**
	 * @return string The time it took to run the last SQL query
	 */
	public function getTime() {
		return $this->time;
	}

	public function clean($datum) {
		return $this->mysqli->real_escape_string(trim($datum));
	}

	public function close() {
		$this->mysqli->close();
	}
}
?>