<?php
# a mess
require_once "lib/dbeng/db_abs.php";
require_once "lib/dbstruct/SpotStruct_mysql.php";

class db_mysql extends db_abs {
	private $_db_host;
	private $_db_user;
	private $_db_pass;
	private $_db_db;
	
	private $_conn;
	
	function __construct($host, $user, $pass, $db)
    {
		$this->_db_host = $host;
		$this->_db_user = $user;
		$this->_db_pass = $pass;
		$this->_db_db = $db;
	}
	
	function connect() {
		$this->_conn = @mysql_connect($this->_db_host, $this->_db_user, $this->_db_pass);
		
		if (!$this->_conn) {
			throw new Exception("Unable to connect to MySQL server: " . mysql_error());
		} # if 
				
		if (!@mysql_select_db($this->_db_db, $this->_conn)) {
			throw new Exception("Unable to select MySQL db: " . mysql_error($this->_conn));
			return false;
		} # if
		
		# Create the database structure
		$dbStruct = new SpotStruct_mysql($this);
		$dbStruct->createDatabase();
    } # connect()
		
	function safe($s) {
		return mysql_real_escape_string($s);
	} # safe

	function rawExec($s) {
		$tmpRes = @mysql_unbuffered_query($s, $this->_conn);
		if ($tmpRes === false) {
			throw new Exception("Error executing query: " . mysql_error($this->_conn));
		} # if
		
		return $tmpRes;
	} # rawExec

	function singleQuery($s, $p = array()) {
		$res = $this->exec($s, $p);
		$row = mysql_fetch_array($res);
		mysql_free_result($res);
		
		return $row[0];
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		$rows = array();

		$res = $this->exec($s, $p); 
		while ($rows[] = mysql_fetch_assoc($res));

		# remove last element (false element)
		array_pop($rows); 
		
		mysql_free_result($res);
		return $rows;
	} # arrayQuery

	/*
	 * Utility functie omdat MySQL 0 rows affected teruggeeft als je
	 * een update uitvoert op een rij die hetzelfde blijft.
	 * 
	 * Copied from:
	 *    http://nl.php.net/manual/en/function.mysql-info.php#36008
	 */
	function get_mysql_info() {
		$strInfo = mysql_info($this->_conn);
	   
		$return = array();
		ereg("Records: ([0-9]*)", $strInfo, $records);
		ereg("Duplicates: ([0-9]*)", $strInfo, $dupes);
		ereg("Warnings: ([0-9]*)", $strInfo, $warnings);
		ereg("Deleted: ([0-9]*)", $strInfo, $deleted);
		ereg("Skipped: ([0-9]*)", $strInfo, $skipped);
		ereg("Rows matched: ([0-9]*)", $strInfo, $rows_matched);
		ereg("Changed: ([0-9]*)", $strInfo, $changed);
	   
		$return['records'] = $records[1];
		$return['duplicates'] = $dupes[1];
		$return['warnings'] = $warnings[1];
		$return['deleted'] = $deleted[1];
		$return['skipped'] = $skipped[1];
		$return['rows_matched'] = $rows_matched[1];
		$return['changed'] = $changed[1];
	   
		return $return;
	} # get_mysql_info()
	
	function rows() {
		$rows = $this->get_mysql_info();
		return $rows['rows_matched'];
	} # rows()

} # class
