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
							  
		    default			: throw new Exception("Unknown DB engine specified, please choose sqlite3 or mysql");
		} # switch
		
		$this->_conn->connect();
    } # ctor
	
	/* 
	 * Update of insert the maximum article id in de database.
	 */
	function setMaxArticleId($server, $maxarticleid) {
		# Replace INTO reset de kolommen die we niet updaten naar 0 en dat is stom
		$res = $this->_conn->exec("UPDATE nntp SET maxarticleid = '%s' WHERE server = '%s'", Array((int) $maxarticleid, $server));
		
		if ($this->_conn->rows() == 0) {	
			$this->_conn->exec("INSERT INTO nntp(server, maxarticleid) VALUES('%s', '%s')", Array($server, (int) $maxarticleid));
		} # if
	} # setMaxArticleId()

	/*
	 * Vraag het huidige articleid (van de NNTP server) op, als die nog 
	 * niet bestaat, voeg dan een nieuw record toe en zet die op 0
	 */
	function getMaxArticleId($server) {
		$artId = $this->_conn->singleQuery("SELECT maxarticleid FROM nntp WHERE server = '%s'", Array($server));
		if ($artId == null) {
			$this->setMaxArticleId($server, 0);
			$artId = 0;
		} # if
		
		return $artId;
	} # getMaxArticleId

	/*
	 * Geef terug of de huidige nntp server al bezig is volgens onze eigen database
	 */
	function isRetrieverRunning($server) {
		$artId = $this->_conn->singleQuery("SELECT nowrunning FROM nntp WHERE server = '%s'", Array($server));
		return ((!empty($artId)) && ($artId > (time() - 3000)));
	} # isRetrieverRunning

	/*
	 * Geef terug of de huidige nntp server al bezig is volgens onze eigen database
	 */
	function setRetrieverRunning($server, $isRunning) {
		if ($isRunning) {
			$runTime = time();
		} else {
			$runTime = 0;
		} # if
		
		# Replace INTO reset de kolommen die we niet updaten naar 0 en dat is stom
		$res = $this->_conn->exec("UPDATE nntp SET nowrunning = '%s' WHERE server = '%s'", Array((int) $runTime, $server));
		if ($this->_conn->rows() == 0) {	
			$this->_conn->exec("INSERT INTO nntp(server, nowrunning) VALUES('%s', '%s')", Array($server, (int) $runTime));
		} # if
	} # setRetrieverRunning
	
	/**
	 * Geef het aantal spots terug dat er op dit moment in de db zit
	 */
	function getSpotCount() {
		$cnt = $this->_conn->singleQuery("SELECT COUNT(1) FROM spots");
		
		if ($cnt == null) {
			return 0;
		} else {
			return $cnt;
		} # if
	} # getSpotCount

	/*
	 * Geef alle spots terug in de database die aan $sqlFilter voldoen.
	 * 
	 */
	function getSpots($pageNr, $limit, $sqlFilter) {
		$results = array();
		$offset = (int) $pageNr * (int) $limit;

		if (!empty($sqlFilter)) {
			$sqlFilter = ' WHERE ' . $sqlFilter;
		} # if
 		return $this->_conn->arrayQuery("SELECT * FROM spots " . $sqlFilter . " ORDER BY stamp DESC LIMIT " . (int) $limit ." OFFSET " . (int) $offset);
	} # getSpots()

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
		$this->_conn->exec("INSERT INTO commentsxover(messageid, revid, nntpref) VALUES('%s', %d, '%s')",
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
		$this->_conn->exec("INSERT INTO spots(spotid, messageid, category, subcat, poster, groupname, subcata, subcatb, subcatc, subcatd, title, tag, stamp) 
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
