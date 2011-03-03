<?php
# a mess
require_once "lib/dbeng/db_abs.php";

class db_sqlite3 extends db_abs {
	private $_db_path;
	private $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		try {
			$this->_conn = @sqlite_factory($this->_db_path);
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
		return @$this->_conn->queryExec($s);
	} # rawExec
	
	function singleQuery($s, $p = array()) {
		$res = @$this->_conn->singleQuery($this->prepareSql($s, $p), true);
		if ($res !== false) {
			return $res;
		} else {
			$this->setError("Error executing query (" . $s . "): " . sqlite_error_string($this->_conn->lastError()));
			return false;
		} # else
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		$res = @$this->_conn->arrayQuery($this->prepareSql($s, $p));
		if ($res !== false) {
			return $res;
		} else {
			$this->setError("Error executing query (" . $this->prepareSql($s, $p) . "): " . sqlite_error_string($this->_conn->lastError()));
		} # if
	} # arrayQuery

	function createDatabase() {
		$q = @$this->_conn->singleQuery("PRAGMA table_info(spots)");
		if ($q === false) {
			$this->setError("Error querying tables in database: " . sqlite_error_string($this->_conn->lastError()));
			return false;
		} # if

		if (empty($q)) {
			$res = $this->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY ASC, 
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
			if (!$res) {
				$this->setError("Error creating table spots in database: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if

			$res = $this->rawExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE);");

			if (!$res) {
				$this->setError("Error creating table nntp in database: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if

			# create indices
			if (!$this->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)")) {
				$this->setError("Error creating index1 on table spots: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if
			
			if (!$this->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)")) {
				$this->setError("Error creating index2 on table spots: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if
			
			if (!$this->rawExec("CREATE INDEX idx_spots_3 ON spots(messageid)")) {
				$this->setError("Error creating index3 on table spots: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if
		} # if
		
		$q = $this->singleQuery("PRAGMA table_info(commentsxover)");
		if (!$q) {
			$res = $this->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY ASC,
										   messageid TEXT,
										   revid INTEGER,
										   nntpref TEXT);");
			if (!$res) {
				$this->setError("Error creating table commentsxover in database: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if
										   
			if (!$this->rawExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)")) {
				$this->setError("Error creating index1 on table commentsxover: " . sqlite_error_string($this->_conn->lastError()));
				return false;
			} # if
		} # if
		
		return true;
	} # Createdatabase

} # class