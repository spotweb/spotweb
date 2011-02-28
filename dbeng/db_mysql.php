<?php
# a mess
require_once "dbeng/db_abs.php";

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
			$this->setError("Unable to connect to MySQL server: " . mysql_error());
			return false;
		} # if 
				
		if (!@mysql_select_db($this->_db_db, $this->_conn)) {
			$this->setError("Unabel to select MySQL db: " . mysql_error($this->_conn));
			return false;
		} # if
		
		return $this->createDatabase();
    } # ctor
		
	function safe($s) {
		return mysql_real_escape_string($s);
	} # safe

	function rawExec($s) {
		return mysql_query($s, $this->_conn);
	} # rawExec

	function exec($s, $p = array()) {
		if (!empty($p)) {
			$p = array_map(array($this, 'safe'), $p);
		} # if
		
		if (empty($p)) {
			$res = $this->rawExec($s);
		} else {
			$res = $this->rawExec(vsprintf($s, $p));
		} # if
		if (!$res) {
			die(mysql_error());
		}
		return $res;
	} # exec
		
	function singleQuery($s, $p = array()) {
		$res = $this->exec($s, $p);
		$row = mysql_fetch_array($res);
		mysql_free_result($res);

		return $row[0];
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		$res = $this->exec($s, $p); 
		$rows = array();
			
		while ($rows[] = mysql_fetch_assoc($res));
		# remove last element (false element)
		array_pop($rows); 
		
		mysql_free_result($res);
		return $rows;
	} # arrayQuery

	
	function createDatabase() {
		$q = $this->singleQuery("SHOW TABLES");
		if (!$q) {
			$res = $this->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
											messageid varchar(250),
											spotid INTEGER,
											category INTEGER, 
											subcat INTEGER,
											poster TEXT,
											groupname TEXT,
											subcata VARCHAR(24),
											subcatb VARCHAR(24),
											subcatc VARCHAR(24),
											subcatd VARCHAR(24),
											title TEXT,
											tag TEXT,
											stamp INTEGER);");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if
			$res = $this->rawExec("CREATE TABLE nntp(server varchar(128) PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE);");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if

			# create indices
			$this->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->rawExec("CREATE INDEX idx_spots_3 ON spots(messageid)");
		} # if
		
		$q = $this->singleQuery("SHOW TABLES LIKE 'commentsxover'");
		if (!$q) {
			$res = $this->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(250),
										   revid INTEGER,
										   nntpref VARCHAR(250));");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if
			$res = $this->rawExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if
		} # if
		
		return true;
	} # Createdatabase

} # class