<?php
# a mess
require_once "dbeng/db_abs.php";

class db_mysql extends db_abs {
	private $_conn;
	
	function __construct($host, $user, $pass, $db)
    {
		$this->_conn = mysql_connect($host, $user, $pass);
		
		if (!$this->_conn) {
			die("Unable to connect to MySQL db: " . mysql_error($this->_conn));
		} # if 
		
		
		if (!mysql_select_db($db, $this->_conn)) {
			die("Unabel to select MySQL db: " . mysql_error($this->_conn));
		} # if
		
		$this->createDatabase();
    } # ctor
		
	static function safe($s) {
		return mysql_real_escape_string($s);
	} # safe

	function rawExec($s) {
		return mysql_query($s, $this->_conn);
	} # rawExec

	function exec($s, $p = array()) {
		if (!empty($p)) {
			$p = array_map(array('db_mysql', 'safe'), $p);
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
			$res = $this->exec("CREATE TABLE spots(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
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
			$res = $this->exec("CREATE TABLE nntp(server varchar(128) PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE);");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if

			# create indices
			$this->exec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->exec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->exec("CREATE INDEX idx_spots_3 ON spots(messageid)");
		} # if
		
		$q = $this->singleQuery("SHOW TABLES LIKE 'commentsxover'");
		if (!$q) {
			$res = $this->exec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(250),
										   revid INTEGER,
										   nntpref VARCHAR(250));");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if
			$res = $this->exec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
			if (!$res) {
				die(mysql_error($this->_conn));
			} # if
		} # if
		
	} # Createdatabase

} # class