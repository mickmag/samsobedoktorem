<?php


interface IDatabaseConnection {
	function sqlQuery($sql);
	function sqlExecute($sql);
	function getInsertId();
}


class MysqliConnection implements IDatabaseConnection {
	
	protected $mysqli;

	public function __construct($server, $login, $password, $dbname) {
		if (preg_match('/^(.*):([0-9]+)$/', $server, $matches)) {
			$host = $matches[1]; $port = $matches[2];
			$this->mysqli = new mysqli($host, $login, $password, $dbname, $port);
		} elseif (preg_match('#^.*:(/.*)$#', $server, $matches)) {
			$socket = $matches[1];
			$this->mysqli = new mysqli('', $login, $password, $dbname, ini_get("mysqli.default_port"), $socket);
		} else {
			$this->mysqli = new mysqli($server, $login, $password, $dbname);
		}
		$this->mysqli->set_charset("utf8");
		$GLOBALS['DatabaseConnection_toSql_object'] = $this;
	}

	
	public function sqlQuery($sql) {
		$res = $this->mysqli->query($sql);
		if (!$res) trigger_error("Query Failed! SQL: $sql - Error: ".$this->mysqli->error);
		$ar = array();
		while($row = $res->fetch_array()) {
			$ar[] = $row;
		}
		$res->free();
		return $ar;
	}

	
	public function sqlExecute($sql) {
		$res = $this->mysqli->query($sql);
		if (!$res) trigger_error("Query Failed! SQL: $sql - Error: ".$this->mysqli->error);
		if ($res instanceof mysqli_result) $res->free();
	}

	function getInsertId() {
		return $this->mysqli->insert_id;
	}

	function toSql($text) {
		if (is_null($text)) {
			return "null";
		} else {
			return "'".$this->mysqli->real_escape_string($text)."'";
		}
	}

}


class MysqliConnectionShared extends MysqliConnection {
	public function __construct($server, $login, $password, $dbname) {
		global $MysqliConnectionShared_mysqli;
		if ($MysqliConnectionShared_mysqli != null) {
			$this->mysqli = $MysqliConnectionShared_mysqli;
		} else {
			parent::__construct($server, $login, $password, $dbname);
			$MysqliConnectionShared_mysqli = $this->mysqli;
		}
	}
}



class MysqlConnection implements IDatabaseConnection {
	
	protected $dbconn;

	public function __construct($server, $login, $password, $dbname) {
		$this->dbconn = mysql_connect($server, $login, $password);
		mysql_select_db($dbname) or user_error("Chyba databaze: " . mysql_error());
		mysql_query("SET NAMES 'UTF-8'");
		$GLOBALS['DatabaseConnection_toSql_object'] = $this;
	}

	
	public function sqlQuery($sql) {
		$res = $this->sqlExecute($sql);

		$ar = array();
		while($row = mysql_fetch_array($res)) {
			$ar[] = $row;
		}

		return $ar;
	}

	
	public function sqlExecute($sql) {
		$res = mysql_query($sql) or user_error(mysql_error() . "<br/>SQL: ".$sql, E_USER_ERROR);
		return $res;
	}

	function getInsertId() {
		return mysql_insert_id();
	}

	function toSql($text) {
		if (is_null($text)) {
			return "null";
		} else {
			return "'".mysql_real_escape_string($text)."'";
		}
	}

}



if (!function_exists('toSql')) {

	function toSql($text) {
		return $GLOBALS['DatabaseConnection_toSql_object']->toSql($text);
	}

}
