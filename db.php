<?php
/*
 * A mess
 */
class db
{
	private $_conn;
	
    function __construct($path)
    {
		$this->_conn = sqlite_factory($path);
		$this->createDatabase();
    }
	
	static function safe($s) {
		return sqlite_escape_string($s);
	} # safe
	
	function exec($s, $p) {
		$p = array_map(array('db', 'safe'), $p);
		
		# echo "EXECUTING: " . vsprintf($s, $p) . "\r\n";
		
		return $this->_conn->queryExec(vsprintf($s, $p));
	} # exec
		
	function singleQuery($s, $p) {
		$p = array_map(array('db', 'safe'), $p);
		
		return $this->_conn->singleQuery(vsprintf($s, $p));
	} # singleQuery

	function arrayQuery($s, $p) {
		$p = array_map(array('db', 'safe'), $p);
		
		return $this->_conn->arrayQuery(vsprintf($s, $p));
	} # arrayQuery
	
	function setMaxArticleId($server, $maxarticleid) {
		return $this->exec("INSERT OR REPLACE INTO nntp(server, maxarticleid) VALUES('%s',%s)", Array($server, (int) $maxarticleid));
	} # setMaxArticleId

	function getMaxArticleId($server) {
		$p = $this->singleQuery("SELECT maxarticleid FROM nntp WHERE server = '%s'", Array($server));
		
		if ($p == null) {
			$this->setMaxArticleId($server, 0);
			$p = 0;
		} # if
		
		return $p;
	} # getMaxArticleId

	function getSpots($id, $limit, $sqlFilter) {
		$results = array();

		if (!empty($sqlFilter)) {
			$sqlFilter = ' AND ' . $sqlFilter;
		} # if
		
		$resource = $this->_conn->unbufferedQuery("SELECT * FROM spots WHERE id > " . (int) $id . $sqlFilter . " ORDER BY stamp DESC LIMIT " . (int) $limit, SQLITE_ASSOC);
		
		while ($row = $resource->fetch()) {
			$results[] = $row;
		} # while
		
		return $results;
	} # getSpots
	
	function beginTransaction() {
		$this->exec('BEGIN;', array());
	} # beginTransaction

	function abortTransaction() {
		$this->exec('ABORT;', array());
	} # abortTransaction
	
	function endTransaction() {
		$this->exec('COMMIT;', array());
	} # endTransaction
	
	function getSpot($messageId) {
		return $this->arrayQuery("SELECT * FROM spots WHERE messageid = '%s'", Array($messageId));
	} # getSpot()
	
	function addSpot($spot) {
		return $this->exec("INSERT INTO spots(spotid, messageid, category, subcat, poster, groupname, subcata, subcatb, subcatc, subcatd, title, tag, stamp) 
				VALUES(%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				 Array($spot['ID'],
					   $spot['MessageID'],
					   $spot['Category'],
					   $spot['SubCat'],
					   $spot['Poster'],
					   $spot['GroupName'],
					   $spot['SubCatA'],
					   $spot['SubCatB'],
					   $spot['SubCatC'],
					   $spot['SubCatD'],
					   $spot['Title'],
					   $spot['Tag'],
					   $spot['Stamp']));
	} # addSpot()
	
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
	} # Createdatabase
}

