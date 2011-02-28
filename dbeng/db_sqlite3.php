<?php
# a mess
require_once "dbeng/db_abs.php";

class db_sqlite3 extends db_abs {
	private $_db_path;
	private $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		try {
			$this->_conn = sqlite_factory($this->_db_path);
		} catch(Exception $x) {
			$this->setError("Unable to open sqlite3 database: " . $x->getMessage());
			return false;
		} # try
		
		return $this->createDatabase();
	} # connect()
	
	function safe($s) {
		return sqlite_escape_string($s);
	} # safe

	function rawExec($s) {
		return $this->_conn->queryExec($s);
	} # rawExec
	
	function exec($s, $p = array()) {
		return $this->rawExec($this->prepareSql($s, $p));
	} # exec
		
	function singleQuery($s, $p = array()) {
		return $this->_conn->singleQuery($this->prepareSql($s, $p), true);
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		return $this->_conn->arrayQuery($this->prepareSql($s, $p));
	} # arrayQuery

	
	function createDatabase() {
		$q = $this->_conn->singleQuery("PRAGMA table_info(spots)");
		if (!$q) {
			$this->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY ASC, 
											messageid TEXT,
											spotid INTEGER,
											category INTEGER, 
											subcat INTEGER,
											poster TEXT,
											groupname TEXT,
											subcata TEXT,
											subcatb TEXT,
											subcatc TEXT,
											subcatd TEXT,
											title TEXT,
											tag TEXT,
											stamp INTEGER);");
			$this->rawExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE);");

			# create indices
			$this->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->rawExec("CREATE INDEX idx_spots_3 ON spots(messageid)");
		} # if
		
		$q = $this->rawExec("PRAGMA table_info(commentsxover)");
		if (!$q) {
			$this->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY ASC,
										   messageid TEXT,
										   revid INTEGER,
										   nntpref TEXT);");
			$this->rawExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
		} # if
		
		return true;
	} # Createdatabase

} # class