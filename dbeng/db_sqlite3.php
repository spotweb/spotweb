<?php
# a mess
require_once "dbeng/db_abs.php";

class db_sqlite3 extends db_abs {
	private $_conn;
	
	function __construct($path)
    {
		$this->_conn = sqlite_factory($path);
		$this->createDatabase();
    } # ctor
		
	static function safe($s) {
		return sqlite_escape_string($s);
	} # safe

	function rawExec($s) {
		return $this->_conn->queryExec($s);
	} # rawExec
	
	function exec($s, $p = array()) {
		$p = array_map(array('db_sqlite3', 'safe'), $p);
		
		# echo "EXECUTING: " . vsprintf($s, $p) . "\r\n";

		# niet op empty checken gaat mis als er %'s in de query string zitten omdat
		# er dan gereplaced wordt waar dat niet moet..
		if (empty($p)) {
			return $this->rawExec($s);
		} else {
			return $this->rawExec(vsprintf($s, $p));
		} # if
	} # exec
		
	function singleQuery($s, $p = array()) {
		$p = array_map(array('db_sqlite3', 'safe'), $p);
		
		# niet op empty checken gaat mis als er %'s in de query string zitten omdat
		# er dan gereplaced wordt waar dat niet moet..
		if (empty($p)) {
			return $this->_conn->singleQuery($s, true);
		} else {
			return $this->_conn->singleQuery(vsprintf($s, $p), true);
		} # else
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		$p = array_map(array('db_sqlite3', 'safe'), $p);
		
		# niet op empty checken gaat mis als er %'s in de query string zitten omdat
		# er dan gereplaced wordt waar dat niet moet..
		if (empty($p)) {
			return $this->_conn->arrayQuery($s);
		} else {
			return $this->_conn->arrayQuery(vsprintf($s, $p));
		} # else
	} # arrayQuery

	
	function createDatabase() {
		$q = $this->_conn->singleQuery("PRAGMA table_info(spots)");
		if (!$q) {
			$this->_conn->queryExec("CREATE TABLE spots(id INTEGER PRIMARY KEY ASC, 
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
			$this->_conn->queryExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE);");

			# create indices
			$this->_conn->queryExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->_conn->queryExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->_conn->queryExec("CREATE INDEX idx_spots_3 ON spots(messageid)");
		} # if
		
		$q = $this->_conn->singleQuery("PRAGMA table_info(commentsxover)");
		if (!$q) {
			$this->_conn->queryExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY ASC,
										   messageid TEXT,
										   revid INTEGER,
										   nntpref TEXT);");
			$this->_conn->queryExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
		} # if
	} # Createdatabase

} # class