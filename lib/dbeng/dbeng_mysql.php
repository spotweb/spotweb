<?php
# a mess

class dbeng_mysql extends dbeng_abs {
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

		/* 
		 * arbitrarily chosen because some insert statements might
		 * be very large.
		 */
		$this->_batchInsertChunks = 100;
	}
	
	function connect() {
		$this->_conn = mysql_connect($this->_db_host, $this->_db_user, $this->_db_pass);
		
		if (!$this->_conn) {
			throw new DatabaseConnectionException("Unable to connect to MySQL server: " . mysql_error());
		} # if 
				
		if (!@mysql_select_db($this->_db_db, $this->_conn)) {
			throw new DatabaseConnectionException("Unable to select MySQL db: " . mysql_error($this->_conn));
			return false;
		} # if
		
		# Set that we will be talking in utf8
		$this->rawExec("SET NAMES utf8;"); # mysql_set_charset is not compatible with older PHP versions
    } # connect()
		
	function safe($s) {
		return mysql_real_escape_string($s);
	} # safe

	function rawExec($s) {
		SpotTiming::start(__FUNCTION__);
		$tmpRes = mysql_unbuffered_query($s, $this->_conn);
		if ($tmpRes === false) {
			throw new SqlErrorException(mysql_error($this->_conn), mysql_errno($this->_conn));
		} # if
		SpotTiming::stop(__FUNCTION__, array($s));
		
		return $tmpRes;
	} # rawExec

	/*
	 * INSERT, DELETE or UPDATE statement
	 */
	function modify($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);

		$res = $this->exec($s, $p);
		if (!is_bool($res)) {
			mysql_free_result($res);
		} # if
		
		SpotTiming::stop(__FUNCTION__, array($s,$p));
		return ((bool) $res);
	} # modify
	
	function singleQuery($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);
		
		$res = $this->exec($s, $p);
		$row = mysql_fetch_array($res);
		mysql_free_result($res);
		
		SpotTiming::stop(__FUNCTION__, array($s,$p));
		
		return $row[0];
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);
		$rows = array();

		$res = $this->exec($s, $p); 
		while ($rows[] = mysql_fetch_assoc($res));

		# remove last element (false element)
		array_pop($rows); 
		
		mysql_free_result($res);
		SpotTiming::stop(__FUNCTION__, array($s,$p));
		
		return $rows;
	} # arrayQuery

	/*
	 * Returns a database specific representation of a boolean value
	 */
	function bool2dt($b) {
		if ($b) {
			return '1';
		} # if
		
		return '0';
	} # bool2dt
	
	/* 
	 * Begins an transaction
	 */
	function beginTransaction() {
		$this->exec('BEGIN;');
	} # beginTransaction
	
	/* 
	 * Commits an transaction
	 */
	function commit() {
		$this->exec('COMMIT;');
	} # commit
	
	/* 
	 * Rolls back an transaction
	 */
	function rollback() {
		$this->exec('ROLLBACK;');
	} # rollback
	
	/*
	 * Utility functie because MySQL returns '0 rows affected' when a update
	 * occurs for a row, while no values change
	 * 
	 * Copied from:
	 *    http://nl.php.net/manual/en/function.mysql-info.php#36008
	 */
	function get_mysql_info() {
		$strInfo = mysql_info($this->_conn);
	   
		$return = array();
		preg_match("/Rows matched: ([0-9]*)/", $strInfo, $rows_matched);
	   
		$return['rows_matched'] = $rows_matched[1];

		return $return;
	} # get_mysql_info()
	
	function rows() {
		$rows = $this->get_mysql_info();
		return $rows['rows_matched'];
	} # rows()
	
	function lastInsertId($tableName) {
		return mysql_insert_id($this->_conn);
	} # lastInsertId

	

} # class
