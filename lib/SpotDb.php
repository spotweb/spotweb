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
							  
		    default			: throw new Exception('Unknown DB engine specified, please choose either sqlite3 or mysql');
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
	 * Returns the highest messageid from server 
	 */
	function getMaxMessageId($headers) {
		if ($headers == 'headers') {
			$msgId = $this->_conn->singleQuery("SELECT messageid FROM spots ORDER BY id DESC LIMIT 1");
		} else {
			$msgId = $this->_conn->singleQuery("SELECT messageid FROM commentsxover ORDER BY id DESC LIMIT 1");
		} # else
		if ($msgId == null) {
			$msgId = '';
		} # if
		
		return $msgId;
	} # func. getMaxMessageId
	 
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

	/*
	 * Remove extra spots 
	 */
	function removeExtraSpots($messageId) {
		# vraag eerst het id op
		$spot = $this->getFullSpot($messageId);
		
		# als deze spot leeg is, is er iets raars aan de hand
		if (empty($spot)) {
			throw new Exception("Our highest spot is not in the database!?");
		} # if
		
		# en wis nu alles wat 'jonger' is dan deze spot
		$this->_conn->exec("DELETE FROM spots WHERE id > %d", Array($spot['spotdbid']));
		
		if (((int) $spot['fullspotdbid']) > 0) {
			$this->_conn->exec("DELETE FROM spotsfull WHERE id > %d", Array( (int) $spot['fullspotdbid']));
		} # if
	} # removeExtraSpots

	/*
	 * Remove extra comments
	 */
	function removeExtraComments($messageId) {
		# vraag eerst het id op
		$commentId = $this->_conn->singleQuery("SELECT id FROM commentsxover WHERE messageid = '%s'", Array($messageId));
		
		# als deze spot leeg is, is er iets raars aan de hand
		if (empty($commentId)) {
			throw new Exception("Our highest comment is not in the database!?");
		} # if
		
		# en wis nu alles wat 'jonger' is dan deze spot
		$this->_conn->exec("DELETE FROM commentsxover WHERE id > %d", Array($commentId));
	} # removeExtraComments

	/*
	 * Zet de tijd/datum wanneer retrieve voor het laatst geupdate heeft
	 */
	function setLastUpdate($server) {
		return $this->_conn->exec("UPDATE nntp SET lastrun = '%d' WHERE server = '%s'", Array(time(), $server));
	} # getLastUpdate

	/*
	 * Geef de datum van de laatste update terug
	 */
	function getLastUpdate($server) {
		return $this->_conn->singleQuery("SELECT lastrun FROM nntp WHERE server = '%s'", Array($server));
	} # getLastUpdate
	
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
	 * Match set of spots
	 */
	function matchMessageIds($hdrList) {
		$idList = array('spot' => array(), 'fullspot' => array());
		
		# geen message id's gegeven? vraag het niet eens aan de db
		if (count($hdrList) == 0) {
			return $idList;
		} # if
		
		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($hdrList as $hdr) {
			$msgIdList .= "'" . substr($this->_conn->safe($hdr['Message-ID']), 1, -1) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);

		# Omdat MySQL geen full joins kent, doen we het zo
		$rs = $this->_conn->arrayQuery("SELECT messageid AS spot, '' AS fullspot FROM spots WHERE messageid IN (" . $msgIdList . ")
											UNION
					 				    SELECT '' as spot, messageid AS fullspot FROM spotsfull WHERE messageid IN (" . $msgIdList . ")");
									  
		# en lossen we het hier op
		foreach($rs as $msgids) {
			if (!empty($msgids['spot'])) {
				$idList['spot'][] = $msgids['spot'];
			} # if
			
			if (!empty($msgids['fullspot'])) {
				$idList['fullspot'][] = $msgids['fullspot'];
			} # if
		} # foreach
		
		return $idList;
	} # matchMessageIds 
		

	/*
	 * Geef alle spots terug in de database die aan $sqlFilter voldoen.
	 * 
	 */
	function getSpots($pageNr, $limit, $sqlFilter, $sort, $getFull) {
		$results = array();
		$offset = (int) $pageNr * (int) $limit;

		if (!empty($sqlFilter)) {
			$sqlFilter = ' WHERE ' . $sqlFilter;
		} # if
		
		# de optie getFull geeft aan of we de volledige fieldlist moeten 
		# hebben of niet. Het probleem met die volledige fieldlist is duidelijk
		# het geheugen gebruik, dus liefst niet.
		if ($getFull) {
			$extendedFieldList = ',
							f.usersignature AS "user-signature",
							f.userkey AS "user-key",
							f.xmlsignature AS "xml-signature",
							f.fullxml AS fullxml';
		} else {
			$extendedFieldList = '';
		} # else

										 
 		return $this->_conn->arrayQuery("SELECT s.id AS id,
												s.messageid AS messageid,
												s.spotid AS spotid,
												s.category AS category,
												s.subcat AS subcat,
												s.poster AS poster,
												s.groupname AS groupname,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.title AS title,
												s.tag AS tag,
												s.stamp AS stamp,
												s.moderated AS moderated,
												f.userid AS userid,
												f.verified AS verified,
												f.filesize AS filesize" . 
												$extendedFieldList . "
										 FROM spots AS s 
										 LEFT JOIN spotsfull AS f ON s.messageid = f.messageid
										 " . $sqlFilter . " 
										 ORDER BY s." . $this->safe($sort['field']) . " " . $this->safe($sort['direction']) . " LIMIT " . (int) $limit ." OFFSET " . (int) $offset);
	} # getSpots()

	/*
	 * Vraag 1 specifieke spot op
	 */
	function getFullSpot($messageId) {
		$tmpArray = $this->_conn->arrayQuery("SELECT s.*,
												s.id AS spotdbid,
												f.id AS fullspotdbid,
												s.spotid AS id,
												f.messageid AS messageid,
												f.userid AS userid,
												f.verified AS verified,
												f.usersignature AS \"user-signature\",
												f.userkey AS \"user-key\",
												f.xmlsignature AS \"xml-signature\",
												f.fullxml AS fullxml,
												f.filesize AS filesize
												FROM spots AS s 
												JOIN spotsfull AS f ON f.messageid = s.messageid 
										  WHERE s.messageid = '%s'", Array($messageId));
		if (empty($tmpArray)) {
			return ;
		} # if
		$tmpArray = $tmpArray[0];
		
		# If spot is fully stored in db and is of the new type, we process it to
		# make it exactly the same as when retrieved using NNTP
		if (!empty($tmpArray['fullxml']) && (!empty($tmpArray['user-signature']))) {
			$tmpArray['user-signature'] = base64_decode($tmpArray['user-signature']);
			$tmpArray['user-key'] = unserialize(base64_decode($tmpArray['user-key']));
		} # if
		
		return $tmpArray;		
	} # getFullSpot()

	
	/*
	 * Insert commentreg, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addCommentRef($messageid, $nntpref) {
		$this->_conn->exec("INSERT INTO commentsxover(messageid, nntpref) VALUES('%s', '%s')",
								Array($messageid, $nntpref));
	} # addCommentRef
	
	/*
	 * Geef al het commentaar voor een specifieke spot terug
	 */
	function getCommentRef($nntpref) {
		return $this->_conn->arrayQuery("SELECT messageid FROM commentsxover WHERE nntpref = '%s'", Array($nntpref));
	} # getCommentRef

	/*
	 * Geef het aantal reacties voor een specifieke spot terug
	 */
	function getCommentCount($nntpref) {
		return $this->_conn->singleQuery("SELECT COUNT(1) FROM commentsxover WHERE nntpref = '%s'", Array($nntpref));
	} # getCommentCount

	/*
	 * Voeg een spot toe aan de lijst van gedownloade files
	 */
	function addDownload($messageid) {
		$this->_conn->exec("INSERT INTO downloadlist(messageid, stamp) VALUES('%s', '%d')",
								Array($messageid, time()));
	} # addDownload

	/*
	 * Is een messageid al gedownload?
	 */
	function hasBeenDownload($messageid) {
		$artId = $this->_conn->singleQuery("SELECT stamp FROM downloadlist WHERE messageid = '%s'", Array($messageid));
		return (!empty($artId));
	} # hasBeenDownload

	/*
	 * Geef een lijst terug van alle downloads
	 */
	function getDownloads() {
		return $this->_conn->arrayQuery("SELECT s.title AS title, dl.stamp AS stamp, dl.messageid AS messageid FROM downloadlist dl, spots s WHERE dl.messageid = s.messageid");
	} # getDownloads

	/*
	 * Wis de lijst met downloads
	 */
	function emptyDownloadList() {
		return $this->_conn->exec("TRUNCATE TABLE downloadlist;");
	} # emptyDownloadList()

	/*
	 * Verwijder een spot uit de db
	 */
	function deleteSpot($msgId) {
		switch ($this->_dbsettings['engine']) {
			case 'sqlite3'	: { 
				$this->_conn->exec("DELETE FROM spots WHERE messageid = '%s'", Array($msgId));
				$this->_conn->exec("DELETE FROM spotsfull WHERE messageid = '%s'", Array($msgId));
				$this->_conn->exec("DELETE FROM commentsxover WHERE nntpref = '%s'", Array($msgId));
				break; 
			} # sqlite3
			default			: {
				$this->_conn->exec("DELETE FROM spots, spotsfull, commentsxover USING spots
									LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
									LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
									WHERE spots.messageid = '%s'", Array($msgId));
			} # default
		} # switch
	} # deleteSpot

	/*
	 * Markeer een spot in de db moderated
	 */
	function markSpotModerated($msgId) {
		$this->_conn->exec("UPDATE spots SET moderated = 1 WHERE messageid = '%s'", Array($msgId));
	} # markSpotModerated

	/*
	 * Verwijder oude spots uit de db
	 */
	function deleteSpotsRetention($retention) {
		$retention = $retention * 24 * 60 * 60; // omzetten in seconden
		
		$this->_conn->exec('DELETE FROM spots, spotsfull, commentsxover USING spots
			LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
			LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
			WHERE spots.stamp < ' . (time() - $retention) );
	} # deleteSpotsRetention

	/*
	 * Voeg een spot toe aan de database
	 */
	function addSpot($spot, $fullSpot = array()) {
		$this->_conn->exec("INSERT INTO spots(spotid, messageid, category, subcat, poster, groupname, subcata, subcatb, subcatc, subcatd, title, tag, stamp) 
				VALUES(%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				 Array($spot['id'],
					   $spot['messageid'],
					   $spot['category'],
					   $spot['subcat'],
					   $spot['poster'],
					   $spot['groupname'],
					   $spot['subcata'],
					   $spot['subcatb'],
					   $spot['subcatc'],
					   $spot['subcatd'],
					   $spot['title'],
					   $spot['tag'],
					   $spot['stamp']));
					   
		if (!empty($fullSpot)) {
			$this->addFullSpot($fullSpot);
		} # if
	} # addSpot()
	
	/*
	 * Voeg enkel de full spot toe aan de database, niet gebruiken zonder dat er een entry in 'spots' staat
	 * want dan komt deze spot niet in het overzicht te staan.
	 */
	function addFullSpot($fullSpot) {
		$this->_conn->exec("INSERT INTO spotsfull(messageid, userid, verified, usersignature, userkey, xmlsignature, fullxml, filesize)
				VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
				Array($fullSpot['messageid'],
					  $fullSpot['userid'],
					  (int) $fullSpot['verified'],
					  base64_encode($fullSpot['user-signature']),
					  base64_encode(serialize($fullSpot['user-key'])),
					  $fullSpot['xml-signature'],
					  $fullSpot['fullxml'],
					  $fullSpot['filesize']));
	} # addFullSpot

	function addToWatchlist($messageId, $comment) {
		$this->_conn->exec("INSERT INTO watchlist(messageid, dateadded, comment) VALUES ('%s', %d, '%s')",
				Array($messageId, time(), $comment)); 
	} # addToWatchList

	function removeFromWatchlist($messageid) {
		$this->_conn->exec("DELETE FROM watchlist WHERE messageid = '%s'", 
				Array($messageid));
	} # removeFromWatchlist

	function getWatchlist($sort) {
		return $this->_conn->arrayQuery("SELECT w.messageid AS messageid, 
										 w.dateadded AS dateadded, 
										 w.comment AS comment, 
										 s.title AS title, 
										 s.spotid AS spotid, 
										 s.category AS category, 
										 s.poster AS poster, 
										 s.subcata AS subcata, 
										 s.subcatb AS subcatb, 
										 s.subcatc AS subcatc, 
										 s.subcatd AS subcatd, 
										 s.title AS title, 
										 s.tag AS tag, 
										 s.stamp AS stamp, 
										 s.filesize AS filesize, 
										 s.moderated AS moderated 
									FROM watchlist w 
									LEFT JOIN spots s ON s.messageid = w.messageid 
									ORDER BY s." . $this->safe($sort['field']) . " " . $this->safe($sort['direction']));
	} # addToWatchList

	function beginTransaction() {
		$this->_conn->exec('BEGIN;');
	} # beginTransaction

	function abortTransaction() {
		$this->_conn->exec('ROLLBACK;');
	} # abortTransaction
	
	function commitTransaction() {
		$this->_conn->exec('COMMIT;');
	} # commitTransaction
	
	function safe($q) {
		return $this->_conn->safe($q);
	} # safe
	
} # class db
