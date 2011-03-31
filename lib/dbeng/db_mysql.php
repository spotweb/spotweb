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

	/*
	 * Construeert een stuk van een query om op text velden te matchen, geabstraheerd
	 * zodat we eventueel gebruik kunnen maken van FTS systemen in een db
	 */
	function createTextQuery($field, $searchValue) {
		$searchValue = trim($searchValue);
		$search = $this->getSearchMode($searchValue);

		switch($search['searchMode']) {
			case 'normal'			: $queryPart = " (" . $field . " LIKE '%" . $this->safe($search['searchValue']) . "%')"; break;
			/* Natural language mode is altijd het default in MySQL 5.0 en 5.1, maar kan in 5.0 niet expliciet opgegeven worden */
			case 'match-natural'	: $queryPart = " MATCH(" . $field . ") AGAINST ('" . $this->safe($search['searchValue']) . "')"; break;
			case 'match-boolean'	: $queryPart = " MATCH(" . $field . ") AGAINST ('" . $this->safe($search['searchValue']) . "' IN BOOLEAN MODE)"; break;
		} # switch
//echo $queryPart;
		return $queryPart;
	} # createTextQuery()

	function getSearchMode($search) {
		# MySQL fulltext search kent een minimum aan lengte voor woorden dat het indexeert,
		# standaard staat dit op 4 en dat betekent bv. dat een zoekstring als 'Top 40' niet gevonden
		# zal worden omdat zowel Top als 40 onder de 4 karakters zijn. We kijken hier wat de server
		# instelling is, en vallen eventueel terug op een normale 'LIKE' zoekopdracht.
		$serverSetting = $this->arrayQuery("SHOW VARIABLES WHERE variable_name = 'ft_min_word_len'");
		$minWordLen = $serverSetting[0]['Value'];

		$replacedSearch = str_replace(array('+', '-', '~', '<', '>', '*', '|', 'AND', 'NOT', 'OR'), '', $search);
		$termList = explode(" ", $replacedSearch);
		foreach($termList as $term) {
			if ((strlen($term) < $minWordLen) && (strlen($term) > 0)) {
				return array('searchMode' => 'normal', 'searchValue' => $replacedSearch); /* direct terugschakelen op LIKE search, verdere tests zijn niet nodig */
			} # if
		} # foreach

		# Als alle woorden langer zijn dan $minWordLen gaan we de bekende Boolean syntax vervangen
		# voor hun NATURAL tegenhanger. Zo krijgen we altijd hetzelfde resultaat, ongeacht de invoermethode.
		$termList = explode(" ", $search);
		$newSearchTerms = array();
		foreach($termList as $term) {
			if (strpos('+', $term[0]) !== false) {
				$newSearchTerms[] = $this->str_replace_once('+', 'AND ', $term);
			} elseif (strpos('-', $term[0]) !== false) {
				$newSearchTerms[] = $this->str_replace_once('-', 'NOT ', $term);
			} elseif (strpos('|', $term[0]) !== false) {
				$newSearchTerms[] = $this->str_replace_once('|', 'OR ', $term);
			} elseif (strpos('~', $term[0]) !== false) {
				$newSearchTerms[] = $this->str_replace_once('~', 'NEAR ', $term);
			} else {
				$newSearchTerms[] = $term;
			} # if
		} # foreach

		return array('searchMode' => 'match-natural', 'searchValue' => implode(" ", $newSearchTerms));
	} # getSearchMode

	function str_replace_once($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		if ($pos === false) {
			return $haystack;
		}
		return substr_replace($haystack, $replace, $pos, strlen($needle));
	} # str_replace_once

} # class
