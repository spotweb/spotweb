<?php
/*
 * A mess
 */
require_once "lib/dbeng/db_mysql.php";
require_once "lib/dbeng/db_pdo_sqlite.php";
require_once "lib/dbeng/db_pdo_mysql.php";
require_once "lib/dbstruct/SpotStruct_abs.php";

class SpotDb
{
	private $_dbsettings = null;
	private $_conn = null;
	
    function __construct($db)
    {
		$this->_dbsettings = $db;
	} # __ctor

	/*
	 * Open connectie naar de database (basically factory), de 'engine' wordt uit de 
	 * settings gehaald die mee worden gegeven in de ctor.
	 */
	function connect() {
		switch ($this->_dbsettings['engine']) {
			case 'mysql'	: $this->_conn = new db_mysql($this->_dbsettings['host'],
												$this->_dbsettings['user'],
												$this->_dbsettings['pass'],
												$this->_dbsettings['dbname']); 
							  break;
							  
			case 'pdo_mysql': $this->_conn = new db_pdo_mysql($this->_dbsettings['host'],
												$this->_dbsettings['user'],
												$this->_dbsettings['pass'],
												$this->_dbsettings['dbname']);
							  break;
			case 'pdo_sqlite': $this->_conn = new db_pdo_sqlite($this->_dbsettings['path']);
							   break;
				
			default			: throw new Exception('Unknown DB engine specified, please choose pdo_sqlite, mysql or pdo_mysql');
		} # switch
		
		$this->_conn->connect();
    } # ctor

	/*
	 * Geeft het database connectie object terug
	 */
	function getDbHandle() {
		return $this->_conn;
	} # getDbHandle

	/* 
	 * Haalt alle settings op uit de database
	 */
	function getAllSettings() {
		return $this->_conn->arrayQuery('SELECT name,value FROM settings');
	} # getAllSettings
	
	/*
	 * Update setting
	 */
	function updateSetting($name, $value) {
		$res = $this->_conn->exec("UPDATE settings SET value = '%s' WHERE name = '%s'", Array($value, $name));
		if ($this->_conn->rows() == 0) {	
			$this->_conn->exec("INSERT INTO settings(name,value) VALUES('%s', '%s')", Array($name, $value));
		} # if
	} # updateSetting
	 
	/*
	 * Haalt een user op uit de database 
	 */
	function getUser($userid) {
		$tpl_user = array(
				'userid'		=> 0,
				'username'		=> 'anonymous',
				'firstname'		=> 'John',
				'lastname'		=> 'Doe',
				'mail'			=> 'john@example.com',
				'lastlogin'		=> time(),
				'lastvisit'		=> time() - 3600,
				'banned'		=> false);
				
		return $tpl_user;
	} # getUser

	/*
	 * Update de informatie over een user
	 */
	function setUser($user) {
		throw new Exception("Niet geimplementeerd");
	} # setUser
	
	/* 
	 * Voeg een user toe
	 */
	function addUser($user) {
		throw new Exception("Niet geimplementeerd");
	} # addUser

	/*
	 * Kan de user inloggen met opgegeven password?
	 *
	 * Een userid als de user gevonden kan worden, of false voor failure
	 */
	function authUser($username, $password) {
		return 0;
	} # verifyUser
	
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
	
	function getMaxMessageTime() {
		$stamp = $this->_conn->singleQuery("SELECT MAX(stamp) AS stamp FROM spots");
		if ($stamp == null) {
			$stamp = time();
		} # if
		
		return $stamp;
	} # getMaxMessageTime()
	
	/*
	 * Geeft een database engine specifieke text-match (bv. fulltxt search) query onderdeel terug
	 */
	function createTextQuery($field, $value) {
		return $this->_conn->createTextQuery($field, $value);
	} # createTextQuery()

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
		$spot = $this->getSpotHeader($messageId);

		# als deze spot leeg is, is er iets raars aan de hand
		if (empty($spot)) {
			throw new Exception("Our highest spot is not in the database!?");
		} # if
		
		# en wis nu alles wat 'jonger' is dan deze spot, geen join delete omdat
		# sqlite dat niet kan
		$this->_conn->exec("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE id > %d)", Array($spot['id']));
		$this->_conn->exec("DELETE FROM spots WHERE id > %d", Array($spot['id']));
	} # removeExtraSpots

	/*
	 * Remove extra comments
	 */
	function removeExtraComments($messageId) {
		# vraag eerst het id op
		$commentId = $this->_conn->singleQuery("SELECT id FROM commentsxover WHERE messageid = '%s'", Array($messageId));
		$fullCommentId = $this->_conn->singleQuery("SELECT id FROM commentsfull WHERE messageid = '%s'", Array($messageId));
		
		# als deze spot leeg is, is er iets raars aan de hand
		if (empty($commentId)) {
			throw new Exception("Our highest comment is not in the database!?");
		} # if
		
		# en wis nu alles wat 'jonger' is dan deze spot
		$this->_conn->exec("DELETE FROM commentsxover WHERE id > %d", Array($commentId));
		$this->_conn->exec("DELETE FROM commentsfull WHERE id > %d", Array((int) $fullCommentId));
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
	function getSpotCount($sqlFilter) {
		if (empty($sqlFilter)) {
			$query = "SELECT COUNT(1) FROM spots AS s";
		} else {
			$query = "SELECT COUNT(1) FROM spots AS s 
						LEFT JOIN spotsfull AS f ON s.messageid = f.messageid WHERE " . $sqlFilter; 
		} # else
		$cnt = $this->_conn->singleQuery($query);
		
		if ($cnt == null) {
			return 0;
		} else {
			return $cnt;
		} # if
	} # getSpotCount
	
	/*
	 * Match set of comments
	 */
	function matchCommentMessageIds($hdrList) {
		# We negeren commentsfull hier een beetje express, als die een 
		# keer ontbreken dan fixen we dat later wel.
		$idList = array();
		
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

		# en vraag alle comments op die we kennen
		$rs = $this->_conn->arrayQuery("SELECT messageid FROM commentsxover WHERE messageid IN (" . $msgIdList . ")");
		
		# geef hier een array terug die kant en klaar is voor array_search
		foreach($rs as $msgids) {
			$idList[] = $msgids['messageid'];
		} # foreach
		
		return $idList;
	} # matchCommentMessageIds
	
	/*
	 * Match set of spots
	 */
	function matchSpotMessageIds($hdrList) {
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
				$idList['spot'][$msgids['spot']] = 1;
			} # if
			
			if (!empty($msgids['fullspot'])) {
				$idList['fullspot'][$msgids['fullspot']] = 1;
			} # if
		} # foreach
		
		return $idList;
	} # matchMessageIds 
		

	/*
	 * Geef alle spots terug in de database die aan $sqlFilter voldoen.
	 * 
	 */
	function getSpots($ourUserId, $pageNr, $limit, $sqlFilter, $sort, $getFull) {
		$results = array();
		$offset = (int) $pageNr * (int) $limit;

		if (!empty($sqlFilter)) {
			$sqlFilter .= ' AND ';
		} # if 
		
		# Voeg nu onze userid filter toe omdat we enkel de watchlist en downloadlist
		# van onze eigen user willen zien
		$sqlFilter .= '	((d.ouruserid = ' . $this->safe($ourUserId) . ') OR (d.ouruserid IS NULL)) ' .
					  ' AND ((w.ouruserid = ' . $this->safe($ourUserId) . ') OR (w.ouruserid IS NULL)) ';
		
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
		
		# als er gevraagd is om op 'stamp' descending te sorteren, dan draaien we dit
		# om en voeren de query uit reversestamp zodat we een ASCending sort doen. Dit maakt
		# het voor MySQL ISAM een stuk sneller
		if ((strtolower($sort['field']) == 'stamp') && (strtolower($sort['direction']) == 'desc')) {
			$sort['field'] = 'reversestamp';
			$sort['direction'] = 'ASC';
		} # if

		# en voer de query uit
 		return $this->_conn->arrayQuery("SELECT s.id AS id,
												s.messageid AS messageid,
												s.category AS category,
												s.subcat AS subcat,
												s.poster AS poster,
												s.groupname AS groupname,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												s.title AS title,
												s.tag AS tag,
												s.stamp AS stamp,
												s.moderated AS moderated,
												s.filesize AS filesize,
												d.stamp AS downloadstamp,
												f.userid AS userid,
												f.verified AS verified,
												w.dateadded as w_dateadded
												" . $extendedFieldList . "
										 FROM spots AS s 
										 LEFT JOIN spotsfull AS f ON s.messageid = f.messageid
										 LEFT JOIN downloadlist AS d on s.messageid = d.messageid
										 LEFT JOIN watchlist AS w on s.messageid = w.messageid
										 WHERE " . $sqlFilter . " 
										 ORDER BY s." . $this->safe($sort['field']) . " " . $this->safe($sort['direction']) . " LIMIT " . (int) $limit ." OFFSET " . (int) $offset);
	} # getSpots()

	/*
	 * Geeft enkel de header van de spot terug
	 */
	function getSpotHeader($msgId) {
		$tmpArray = $this->_conn->arrayQuery("SELECT s.id AS id,
												s.messageid AS messageid,
												s.category AS category,
												s.subcat AS subcat,
												s.poster AS poster,
												s.groupname AS groupname,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												s.title AS title,
												s.tag AS tag,
												s.stamp AS stamp,
												s.moderated AS moderated
											  FROM spots AS s
											  WHERE messageid = '%s'", Array($msgId));
		if (empty($tmpArray)) {
			return ;
		} # if
		return $tmpArray[0];
	} # getSpotHeader 
	
	
	/*
	 * Vraag 1 specifieke spot op, als de volledig spot niet in de database zit
	 * geeft dit NULL terug
	 */
	function getFullSpot($messageId, $ourUserId) {
		$tmpArray = $this->_conn->arrayQuery("SELECT s.id AS id,
												s.messageid AS messageid,
												s.category AS category,
												s.subcat AS subcat,
												s.poster AS poster,
												s.groupname AS groupname,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												s.title AS title,
												s.tag AS tag,
												s.stamp AS stamp,
												s.moderated AS moderated,
												s.id AS spotdbid,
												f.id AS fullspotdbid,
												d.stamp AS downloadstamp,
												f.messageid AS messageid,
												f.userid AS userid,
												f.verified AS verified,
												f.usersignature AS \"user-signature\",
												f.userkey AS \"user-key\",
												f.xmlsignature AS \"xml-signature\",
												f.fullxml AS fullxml,
												f.filesize AS filesize,
												w.dateadded as w_dateadded
												FROM spots AS s 
												LEFT JOIN downloadlist AS d on s.messageid = d.messageid
												LEFT JOIN watchlist AS w on s.messageid = w.messageid
												JOIN spotsfull AS f ON f.messageid = s.messageid 
										  WHERE s.messageid = '%s'
											AND ((w.ouruserid = %d) OR (w.ouruserid IS NULL)) 
											AND ((d.ouruserid = %d) OR (d.ouruserid IS NULL))", Array($messageId, $ourUserId, $ourUserId));
		if (empty($tmpArray)) {
			return ;
		} # if
		$tmpArray = $tmpArray[0];
	
		# If spot is fully stored in db and is of the new type, we process it to
		# make it exactly the same as when retrieved using NNTP
		if (!empty($tmpArray['fullxml']) && (!empty($tmpArray['user-signature']))) {
			$tmpArray['user-key'] = unserialize(base64_decode($tmpArray['user-key']));
		} # if
		
		return $tmpArray;		
	} # getFullSpot()

	
	/*
	 * Insert commentreg, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addCommentRef($messageid, $nntpref, $rating) {
		$this->_conn->exec("INSERT INTO commentsxover(messageid, nntpref, spotrating) VALUES('%s', '%s', %d)",
								Array($messageid, $nntpref, $rating));
	} # addCommentRef

	/*
	 * Insert commentfull, gaat er van uit dat er al een commentsxover entry is
	 */
	function addCommentsFull($commentList) {
		foreach($commentList as $comment) {
			$this->_conn->exec("INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, userid, body, verified) 
					VALUES ('%s', '%s', %d, '%s', '%s', '%s', '%s', %d)",
					Array($comment['messageid'],
						  $comment['fromhdr'],
						  $comment['stamp'],
						  $comment['usersignature'],
						  serialize($comment['user-key']),
						  $comment['userid'],
						  implode("\r\n", $comment['body']),
						  $comment['verified']));
		} # foreach
	} # addCommentFull

	/*
	 * Insert commentfull, gaat er van uit dat er al een commentsxover entry is
	 */
	function getCommentsFull($commentMsgIds) {
		# als er geen comments gevraagd worden, vragen we het niet eens aan de db
		if (empty($commentMsgIds)) {
			return array();
		} # if
		
		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($commentMsgIds as $msgId) {
			$msgIdList .= "'" . $this->_conn->safe($msgId) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);
		
		# en vraag de comments daadwerkelijk op
		$commentList = $this->_conn->arrayQuery("SELECT messageid, fromhdr, stamp, usersignature, userkey as \"user-key\", userid, body, verified FROM commentsfull WHERE messageid IN (" . $msgIdList . ")", array());
		for($i = 0; $i < count($commentList); $i++) {
			$commentList[$i]['user-key'] = base64_decode($commentList[$i]['user-key']);
			$commentList[$i]['body'] = explode("\r\n", $commentList[$i]['body']);
		} # foreach
		
		return $commentList;
	} # getCommentsFull
	
	/*
	 * Geeft de gemiddelde spot rating terug
	 */
	function getSpotRating($msgId) {
		return $this->_conn->singleQuery("SELECT AVG(spotrating) AS rating FROM commentsxover WHERE nntpref = '%s' GROUP BY nntpref;", Array($nntpref));
	} # getSpotRating
	
	/*
	 * Geef al het commentaar references voor een specifieke spot terug
	 */
	function getCommentRef($nntpref) {
		$tmpList = $this->_conn->arrayQuery("SELECT messageid FROM commentsxover WHERE nntpref = '%s'", Array($nntpref));
		$msgIdList = array();
		foreach($tmpList as $value) {
			$msgIdList[] = $value['messageid'];
		} # foreach
		
		return $msgIdList;
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
	function addDownload($messageid, $ourUserId) {
		if (!$this->hasBeenDownload($messageid, $ourUserId)) {
			$this->_conn->exec("INSERT INTO downloadlist(messageid, stamp, ouruserid) VALUES('%s', '%d', %d)",
									Array($messageid, time(), $ourUserId));
		} # if
	} # addDownload

	/* 
	 * Is onze database versie nog wel geldig?
	 */
	function schemaValid() {
		$schemaVer = $this->_conn->singleQuery("SELECT value FROM settings WHERE name = 'schemaversion'");
		
		# SPOTDB_SCHEMA_VERSION is gedefinieerd in SpotStruct_Abs
		return ($schemaVer == SPOTDB_SCHEMA_VERSION);
	} # schemaValid
	
	/*
	 * Is een messageid al gedownload?
	 */
	function hasBeenDownload($messageid, $ourUserId) {
		$artId = $this->_conn->singleQuery("SELECT stamp FROM downloadlist WHERE messageid = '%s' AND ouruserid = %d", Array($messageid, $ourUserId));
		return (!empty($artId));
	} # hasBeenDownload

	/*
	 * Wis de lijst met downloads
	 */
	function emptyDownloadList($ourUserId) {
		return $this->_conn->exec("DELETE FROM downloadlist WHERE ouruserid = %d", array($ourUserId));
	} # emptyDownloadList()

	/*
	 * Verwijder een spot uit de db
	 */
	function deleteSpot($msgId) {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_sqlite' : {
				$this->_conn->exec("DELETE FROM spots WHERE messageid = '%s'", Array($msgId));
				$this->_conn->exec("DELETE FROM spotsfull WHERE messageid = '%s'", Array($msgId));
				$this->_conn->exec("DELETE FROM commentsfull WHERE messageid IN (SELECT nntpref FROM commentsxover WHERE messageid= '%s')", Array($msgId));
				$this->_conn->exec("DELETE FROM commentsxover WHERE nntpref = '%s'", Array($msgId));
				$this->_conn->exec("DELETE FROM downloadlist WHERE messageid = '%s'", Array($msgId));
				$this->_conn->exec("DELETE FROM watchlist WHERE messageid = '%s'", Array($msgId));
				break; 
			} # pdo_sqlite
			default			: {
				$this->_conn->exec("DELETE FROM spots, spotsfull, commentsxover, commentsfull, watchlist USING spots
									LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
									LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
									LEFT JOIN commentsfull ON spots.messageid=commentsfull.messageid
									LEFT JOIN downloadlist ON spots.messageid=downloadlist.messageid
									LEFT JOIN watchlist ON spots.messageid=watchlist.messageid
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
		
		switch ($this->_dbsettings['engine']) {
 			case 'pdo_sqlite': {
				$this->_conn->exec("DELETE FROM spots WHERE spots.stamp < " . (time() - $retention) );
				$this->_conn->exec("DELETE FROM spotsfull WHERE spotsfull.messageid not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->exec("DELETE FROM commentsfull WHERE messageid IN 
									(SELECT nntpref FROM commentsxover WHERE commentsxover.nntpref not in 
									(SELECT messageid FROM spots))") ;
				$this->_conn->exec("DELETE FROM commentsxover WHERE commentsxover.nntpref not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->exec("DELETE FROM downloadlist WHERE downloadlist.messageid not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->exec("DELETE FROM watchlist WHERE watchlist.messageid not in 
									(SELECT messageid FROM spots)") ;
				break;
			} # pdo_sqlite
			default		: {
				$this->_conn->exec("DELETE FROM spots, spotsfull, commentsxover, watchlist, commentsfull USING spots
					LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
					LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
					LEFT JOIN commentsfull ON spots.messageid=commentsfull.messageid
					LEFT JOIN downloadlist ON spots.messageid=downloadlist.messageid
					LEFT JOIN watchlist ON spots.messageid=watchlist.messageid
					WHERE spots.stamp < " . (time() - $retention) );
			} # default
		} # switch
	} # deleteSpotsRetention

	/*
	 * Voeg een spot toe aan de database
	 */
	function addSpot($spot, $fullSpot = array()) {
		$this->_conn->exec("INSERT INTO spots(messageid, category, subcat, poster, groupname, subcata, subcatb, subcatc, subcatd, subcatz, title, tag, stamp, reversestamp, filesize) 
				VALUES('%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
				 Array($spot['messageid'],
					   (int) $spot['category'],
					   $spot['subcat'],
					   $spot['poster'],
					   $spot['groupname'],
					   $spot['subcata'],
					   $spot['subcatb'],
					   $spot['subcatc'],
					   $spot['subcatd'],
					   $spot['subcatz'],
					   $spot['title'],
					   $spot['tag'],
					   $spot['stamp'],
					   ($spot['stamp'] * -1),
					   $spot['filesize']) );
					   
		if (!empty($fullSpot)) {
			$this->addFullSpot($fullSpot);
		} # if
	} # addSpot()
	
	/*
	 * Voeg enkel de full spot toe aan de database, niet gebruiken zonder dat er een entry in 'spots' staat
	 * want dan komt deze spot niet in het overzicht te staan.
	 */
	function addFullSpot($fullSpot) {
		# we checken hier handmatig of filesize wel numeriek is, dit is omdat printen met %d in sommige PHP
		# versies een verkeerde afronding geeft bij >32bits getallen.
		if (!is_numeric($fullSpot['filesize'])) {
			$fullSpot['fileSize'] = 0;
		} # if
	
		# en voeg het aan de database toe
		$this->_conn->exec("INSERT INTO spotsfull(messageid, userid, verified, usersignature, userkey, xmlsignature, fullxml, filesize)
				VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				Array($fullSpot['messageid'],
					  $fullSpot['userid'],
					  (int) $fullSpot['verified'],
					  $fullSpot['user-signature'],
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

	function beginTransaction() {
		$this->_conn->beginTransaction();
	} # beginTransaction

	function abortTransaction() {
		$this->_conn->rollback();
	} # abortTransaction
	
	function commitTransaction() {
		$this->_conn->commit();
	} # commitTransaction
	
	function safe($q) {
		return $this->_conn->safe($q);
	} # safe
	
} # class db
