<?php
# a mess
require_once "lib/dbeng/db_abs.php";
require_once "lib/dbstruct/SpotStruct_sqlite.php";

class db_sqlite3 extends db_abs {
	private $_db_path;
	private $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		$this->_conn = @sqlite_factory($this->_db_path);
		if ($this->_conn === null) {
			throw new Exception("Unable to connect to database: " . sqlite_error_string($this->_conn->lastError()));
		} # if
		
		# Create the database structure
		$dbStruct = new SpotStruct_sqlite($this);
		$dbStruct->createDatabase();
	} # connect()
	
	function safe($s) {
		return sqlite_escape_string($s);
	} # safe

	function rawExec($s) {
		# var_dump($s);
		$errorMsg = '';
		$tmpRes = @$this->_conn->unbufferedQuery($s, SQLITE_BOTH, $errorMsg);
		if ($tmpRes === false) {
			if (empty($errorMsg)) {
				$errorMsg =  sqlite_error_string($this->_conn->lastError());
			} # if
			throw new Exception("Error executing query: " . $errorMsg);
		} # if

		return $tmpRes;		
	} # rawExec
	
	function singleQuery($s, $p = array()) {
		# We gebruiken niet meer de 'native' singleQuery() omdat de SQL syntax errors
		# daar niet naar  boven komen
		$res = $this->exec($s, $p);
		$row = $res->fetch();

		unset($res);
		return $row[0];
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		# We gebruiken niet meer de 'native' arrayQuery() omdat de SQL syntax errors
		# daar niet naar  boven komen
		$rows = array();

		$res = $this->exec($s, $p); 
		while ($rows[] = $res->fetch(SQLITE_ASSOC));

		# remove last element (false element)
		array_pop($rows); 
		
		unset($res);
		return $rows;
	} # arrayQuery

	function rows() {
		return $this->_conn->changes();
	} # rows()

} # class
