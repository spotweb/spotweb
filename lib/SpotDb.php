<?php
/*
 * A mess
 */
require_once "lib/dbeng/db_sqlite3.php";
require_once "lib/dbeng/db_mysql.php";

class SpotDb
{
	private $_dbsettings = null;
	private $_conn = null;
	
    function __construct($db)
    {
		global $settings;		
		$this->_dbsettings = $db;
	} # __ctor

	/*
	 * Open connectie naar de database (basically factory), de 'engine' wordt uit de 
	 * settings gehaald die mee worden gegeven in de ctor.
	 * 
	 * Als connectie mislukt geeft deze een false terug, anders een true.
	 */
	function connect() {
		switch ($this->_dbsettings['engine']) {
			case 'sqlite3'	: $this->_conn = new db_sqlite3($this->_dbsettings['path']);
							  break;
							  
			case 'mysql'	: $this->_conn = new db_mysql($this->_dbsettings['host'],
												$this->_dbsettings['user'],
												$this->_dbsettings['pass'],
												$this->_dbsettings['dbname']); 
							  break;
							  
		    default			: die("Unknown DB engine specified, please choose sqlite3 or mysql");
		} # switch
		
		return ($this->_conn->connect());
    } # ctor
	
	/**
	 * Utility functie om snel de error code van een mislukte query te kunnen opvragen
	 */
	function getError() {
		return $this->_conn->getError();
	} # getError()
	
	/* 
	 * Update of insert the maximum article id in de database.
	 * 
	 * Geeft false terug als dit een error gegeven heeft
	 */
	function setMaxArticleId($server, $maxarticleid) {
		return $this->_conn->exec("REPLACE INTO nntp(server, maxarticleid) VALUES('%s',%s)", Array($server, (int) $maxarticleid));
	} # setMaxArticleId()

	/*
	 * Vraag het huidige articleid (van de NNTP server) op, als die nog 
	 * niet bestaat, voeg dan een nieuw record toe en zet die op 0
	 */
	function getMaxArticleId($server) {
		$p = $this->_conn->singleQuery("SELECT maxarticleid FROM nntp WHERE server = '%s'", Array($server));
		
		if ($p == null) {
			if (!$this->setMaxArticleId($server, 0)) {
				return false;
			} # if
			
			$p = 0;
		} # if
		
		return $p;
	} # getMaxArticleId

	/**
	 * Geef het aantal spots terug dat er op dit moment in de db zit
	 */
	function getSpotCount() {
		$p = $this->_conn->singleQuery("SELECT COUNT(1) FROM spots");
		
		if ($p == null) {
			return 0;
		} else {
			return $p;
		} # if
	} # getSpotCount

	/*
	 * Geef alle spots terug in de database die aan $sqlFilter voldoen.
	 * 
	 */
	function getSpots($id, $limit, $sqlFilter) {
		$results = array();

		if (!empty($sqlFilter)) {
			$sqlFilter = ' AND ' . $sqlFilter;
		} # if
		
		return $this->_conn->arrayQuery("SELECT * FROM spots WHERE id > " . (int) $id . $sqlFilter . " ORDER BY stamp DESC LIMIT " . (int) $limit);
	} # getSpots
	
	/*
	 * Vraag 1 specifieke spot op
	 */
	function getSpot($messageId) {
		return $this->_conn->arrayQuery("SELECT * FROM spots WHERE messageid = '%s'", Array($messageId));
	} # getSpot()

	
	/*
	 * Insert commentreg, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 *   revid is een of ander revisie nummer of iets dergelijks
	 */
	function addCommentRef($messageid, $revid, $nntpref) {
		return $this->_conn->exec("REPLACE INTO commentsxover(messageid, revid, nntpref) 
								   VALUES('%s', %d, '%s')",
								Array($messageid, (int) $revid, $nntpref));
	} # addCommentRef
	
	/*
	 * Geef al het commentaar voor een specifieke spot terug
	 */
	function getCommentRef($nntpref) {
		return $this->_conn->arrayQuery("SELECT messageid, MAX(revid) FROM commentsxover WHERE nntpref = '%s' GROUP BY messageid", Array($nntpref));
	} # getCommentRef

	/*
	 * Voeg een spot toe aan de database
	 */
	function addSpot($spot) {
		return $this->_conn->exec("INSERT INTO spots(spotid, messageid, category, subcat, poster, groupname, subcata, subcatb, subcatc, subcatd, title, tag, stamp) 
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

	
	
	function beginTransaction() {
		$this->_conn->exec('BEGIN;');
	} # beginTransaction

	function abortTransaction() {
		$this->_conn->exec('ABORT;');
	} # abortTransaction
	
	function commitTransaction() {
		$this->_conn->exec('COMMIT;');
	} # commitTransaction
	
	function safe($q) {
		return $this->_conn->safe($q);
	} # safe
	
} # class db

