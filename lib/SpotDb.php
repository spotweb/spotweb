<?php
define('SPOTDB_SCHEMA_VERSION', '0.31');

class SpotDb {
	private $_dbsettings = null;
	private $_conn = null;

	/*
	 * Constants used for updating the SpotStateList 
	 */
	const spotstate_Down	= 0;
	const spotstate_Watch	= 1;
	const spotstate_Seen	= 2;

	function __construct($db) {
		$this->_dbsettings = $db;
	} # __ctor

	/*
	 * Open connectie naar de database (basically factory), de 'engine' wordt uit de 
	 * settings gehaald die mee worden gegeven in de ctor.
	 */
	function connect() {
		SpotTiming::start(__FUNCTION__);

		switch ($this->_dbsettings['engine']) {
			case 'mysql'	: $this->_conn = new dbeng_mysql($this->_dbsettings['host'],
												$this->_dbsettings['user'],
												$this->_dbsettings['pass'],
												$this->_dbsettings['dbname']); 
							  break;

			case 'pdo_mysql': $this->_conn = new dbeng_pdo_mysql($this->_dbsettings['host'],
												$this->_dbsettings['user'],
												$this->_dbsettings['pass'],
												$this->_dbsettings['dbname']);
							  break;
			case 'pdo_sqlite': $this->_conn = new dbeng_pdo_sqlite($this->_dbsettings['path']);
							   break;

			default			: throw new Exception('Unknown DB engine specified, please choose pdo_sqlite, mysql or pdo_mysql');
		} # switch

		$this->_conn->connect();
		SpotTiming::stop(__FUNCTION__);
	} # connect

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
		$dbSettings = $this->_conn->arrayQuery('SELECT name,value,serialized FROM settings');
		$tmpSettings = array();
		foreach($dbSettings as $item) {
			if ($item['serialized']) {
				$item['value'] = unserialize($item['value']);
			} # if
			
			$tmpSettings[$item['name']] = $item['value'];
		} # foreach
		
		return $tmpSettings;
	} # getAllSettings

	/* 
	 * Controleer of een messageid niet al eerder gebruikt is door ons om hier
	 * te posten
	 */
	function isCommentMessageIdUnique($messageid) {
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM commentsposted WHERE messageid = '%s'",
						Array($messageid));
		
		return (empty($tmpResult));
	} # isCommentMessageIdUnique

	/*
	 * Sla het gepostte comment op van deze user
	 */
	function addPostedComment($userId, $comment) {
		$this->_conn->modify(
				"INSERT INTO commentsposted(ouruserid, messageid, inreplyto, randompart, rating, body, stamp)
					VALUES('%d', '%s', '%s', '%s', '%d', '%s', %d)", 
				Array((int) $userId,
					  $comment['newmessageid'],
					  $comment['inreplyto'],
					  $comment['randomstr'],
					  (int) $comment['rating'],
					  $comment['body'],
					  (int) time()));
	} # addPostedComment

	/*
	 * Verwijder een setting
	 */
	function removeSetting($name) {
		$this->_conn->exec("DELETE FROM settings WHERE name = '%s'", Array($name));
	} # removeSetting
	
	/*
	 * Update setting
	 */
	function updateSetting($name, $value) {
		# zet het eventueel serialized in de database als dat nodig is
		if ((is_array($value) || is_object($value))) {
			$value = serialize($value);
			$serialized = true;
		} else {
			$serialized = false;
		} # if
		
		switch ($this->_dbsettings['engine']) {
			case 'pdo_sqlite': $this->_conn->exec("UPDATE settings SET value = '%s', serialized = '%d' WHERE name = '%s'", Array($value, $serialized, $name));
								if ($this->_conn->rows() == 0) {
									$this->_conn->modify("INSERT INTO settings(name,value,serialized) VALUES('%s', '%s', '%d')", Array($name, $value, $serialized));
								} # if
							break;
			default			 : $this->_conn->modify("INSERT INTO settings(name,value,serialized) VALUES ('%s', '%s', '%d') ON DUPLICATE KEY UPDATE value = '%s', serialized = '%d'",
										Array($name, $value, $serialized, $value, $serialized));
		} # switch
	} # updateSetting

	/*
	 * Haalt een session op uit de database
	 */
	function getSession($sessionid, $userid) {
		$tmp = $this->_conn->arrayQuery(
						"SELECT s.sessionid as sessionid,
								s.userid as userid,
								s.hitcount as hitcount,
								s.lasthit as lasthit
						FROM sessions AS s
						WHERE (sessionid = '%s') AND (userid = %d)",
				 Array($sessionid,
				       (int) $userid));
		if (!empty($tmp)) {
			return $tmp[0];
		} # if
		
		return false;
	} # getSession

	/*
	 * Creert een sessie
	 */
	function addSession($session) {
		$this->_conn->modify(
				"INSERT INTO sessions(sessionid, userid, hitcount, lasthit) 
					VALUES('%s', %d, %d, %d)",
				Array($session['sessionid'],
					  (int) $session['userid'],
					  (int) $session['hitcount'],
					  (int) $session['lasthit']));
	} # addSession

	/*
	 * Haalt een session op uit de database
	 */
	function deleteSession($sessionid) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE sessionid = '%s'",
					Array($sessionid));
	} # deleteSession

	/*
	 * Haalt een session op uit de database
	 */
	function deleteAllUserSessions($userid) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE userid = %d",
					Array( (int) $userid));
	} # deleteAllUserSessions
	
	/*
	 * Haalt een session op uit de database
	 */
	function deleteExpiredSessions($maxLifeTime) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE lasthit < %d",
					Array(time() - $maxLifeTime));
	} # deleteExpiredSessions

	/*
	 * Update de last hit van een session
	 */
	function hitSession($sessionid) {
		$this->_conn->modify("UPDATE sessions
								SET hitcount = hitcount + 1,
									lasthit = %d
								WHERE sessionid = '%s'", 
							Array(time(), $sessionid));
	} # hitSession

	/*
	 * Checkt of een username al bestaat
	 */
	function usernameExists($username) {
		$tmpResult = $this->_conn->singleQuery("SELECT username FROM users WHERE username = '%s'", Array($username));
		
		return (!empty($tmpResult));
	} # usernameExists

	/*
	 * Checkt of een emailaddress al bestaat
	 */
	function userEmailExists($mail) {
		$tmpResult = $this->_conn->singleQuery("SELECT id FROM users WHERE mail = '%s'", Array($mail));
		
		if (!empty($tmpResult)) {
			return $tmpResult;
		} # if

		return false;
	} # userEmailExists

	/*
	 * Haalt een user op uit de database 
	 */
	function getUser($userid) {
		$tmp = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								u.firstname AS firstname,
								u.lastname AS lastname,
								u.mail AS mail,
								u.apikey AS apikey,
								u.deleted AS deleted,
								u.lastlogin AS lastlogin,
								u.lastvisit AS lastvisit,
								u.lastread AS lastread,
								u.lastapiusage AS lastapiusage,
								s.publickey AS publickey,
								s.otherprefs AS prefs
						 FROM users AS u
						 JOIN usersettings s ON (u.id = s.userid)
						 WHERE u.id = %d AND NOT DELETED",
				 Array( (int) $userid ));

		if (!empty($tmp)) {
			# Other preferences worden serialized opgeslagen in de database
			$tmp[0]['prefs'] = unserialize($tmp[0]['prefs']);
			return $tmp[0];
		} # if
		
		return false;
	} # getUser

	/*
	 * Haalt een user op uit de database 
	 */
	function listUsers($username, $pageNr, $limit) {
		SpotTiming::start(__FUNCTION__);
		$offset = (int) $pageNr * (int) $limit;
		
		$tmpResult = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								u.firstname AS firstname,
								u.lastname AS lastname,
								u.mail AS mail,
								u.lastlogin AS lastlogin,
								u.lastvisit AS lastvisit,
								s.otherprefs AS prefs
						 FROM users AS u
						 JOIN usersettings s ON (u.id = s.userid)
						 WHERE (username LIKE '%" . $this->safe($username) . "%') AND (NOT DELETED)
					     LIMIT " . (int) ($limit + 1) ." OFFSET " . (int) $offset);
		if (!empty($tmpResult)) {
			# Other preferences worden serialized opgeslagen in de database
			for($i = 0; $i < count($tmpResult); $i++) {
				$tmpResult[$i]['prefs'] = unserialize($tmpResult[$i]['prefs']);
			} # for
		} # if

		# als we meer resultaten krijgen dan de aanroeper van deze functie vroeg, dan
		# kunnen we er van uit gaan dat er ook nog een pagina is voor de volgende aanroep
		$hasMore = (count($tmpResult) > $limit);
		if ($hasMore) {
			# verwijder het laatste, niet gevraagde, element
			array_pop($tmpResult);
		} # if

		SpotTiming::stop(__FUNCTION__, array($username, $pageNr, $limit));
		return array('list' => $tmpResult, 'hasmore' => $hasMore);
	} # listUsers

	/*
	 * Disable/delete een user. Echt wissen willen we niet 
	 * omdat eventuele comments dan niet meer te traceren
	 * zouden zijn waardoor anti-spam maatregelen erg lastig
	 * worden
	 */
	function deleteUser($userid) {
		$this->_conn->modify("UPDATE users 
								SET deleted = true
								WHERE id = '%s'", 
							Array( (int) $userid));
	} # deleteUser

	/*
	 * Update de informatie over een user behalve het password
	 */
	function setUser($user) {
		# eerst updaten we de users informatie
		$this->_conn->modify("UPDATE users 
								SET firstname = '%s',
									lastname = '%s',
									mail = '%s',
									apikey = '%s',
									lastlogin = %d,
									lastvisit = %d,
									lastread = %d,
									lastapiusage = %d,
									deleted = '%s'
								WHERE id = '%s'", 
				Array($user['firstname'],
					  $user['lastname'],
					  $user['mail'],
					  $user['apikey'],
					  (int) $user['lastlogin'],
					  (int) $user['lastvisit'],
					  (int) $user['lastread'],
					  (int) $user['lastapiusage'],
					  $user['deleted'],
					  (int) $user['userid']));

		# daarna updaten we zijn preferences
		$this->_conn->modify("UPDATE usersettings
								SET otherprefs = '%s'
								WHERE userid = '%s'", 
				Array(serialize($user['prefs']),
					  (int) $user['userid']));
	} # setUser

	/*
	 * Stel users' password in
	 */
	function setUserPassword($user) {
		$this->_conn->modify("UPDATE users 
								SET passhash = '%s'
								WHERE id = '%s'", 
				Array($user['passhash'],
					  (int) $user['userid']));
	} # setUserPassword

	/*
	 * Vul de public en private key van een user in, alle andere
	 * user methodes kunnen dit niet updaten omdat het altijd
	 * een paar moet zijn
	 */
	function setUserRsaKeys($userId, $publicKey, $privateKey) {
		# eerst updaten we de users informatie
		$this->_conn->modify("UPDATE usersettings
								SET publickey = '%s',
									privatekey = '%s'
								WHERE userid = '%s'",
				Array($publicKey, $privateKey, $userId));
	} # setUserRsaKeys 

	/*
	 * Vraagt de users' private key op
	 */
	function getUserPrivateRsaKey($userId) {
		return $this->_conn->singleQuery("SELECT privatekey FROM usersettings WHERE userid = '%s'", 
					Array($userId));
	} # getUserPrivateRsaKey

	/* 
	 * Voeg een user toe
	 */
	function addUser($user) {
		$this->_conn->modify("INSERT INTO users(username, firstname, lastname, passhash, mail, apikey, lastread, deleted) 
										VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', 'false')",
								Array($user['username'], 
									  $user['firstname'],
									  $user['lastname'],
									  $user['passhash'],
									  $user['mail'],
									  $user['apikey'],
									  $this->getMaxMessageTime()));

		# We vragen nu het userrecord terug op om het userid te krijgen,
		# niet echt een mooie oplossing, maar we hebben blijkbaar geen 
		# lastInsertId() exposed in de db klasse
		$user['userid'] = $this->_conn->singleQuery("SELECT id FROM users WHERE username = '%s'", Array($user['username']));

		# en voeg een usersettings record in
		$this->_conn->modify("INSERT INTO usersettings(userid, privatekey, publickey, otherprefs) 
										VALUES('%s', '', '', 'a:0:{}')",
								Array((int)$user['userid']));
		return $user;
	} # addUser

	/*
	 * Kan de user inloggen met opgegeven password of API key?
	 *
	 * Een userid als de user gevonden kan worden, of false voor failure
	 */
	function authUser($username, $passhash) {
		if ($username === false) {
			$tmp = $this->_conn->arrayQuery("SELECT id FROM users WHERE apikey = '%s' AND NOT DELETED", Array($passhash));
		} else {
			$tmp = $this->_conn->arrayQuery("SELECT id FROM users WHERE username = '%s' AND passhash = '%s' AND NOT DELETED", Array($username, $passhash));
		} # if

		return (empty($tmp)) ? false : $tmp[0]['id'];
	} # authUser

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
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM spots ORDER BY id DESC LIMIT 5000");
		} else {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM commentsxover ORDER BY id DESC LIMIT 5000");
		} # else
		if ($msgIds == null) {
			return array();
		} # if

		$tempMsgIdList = array();
		$msgIdCount = count($msgIds);
		for($i = 0; $i < $msgIdCount; $i++) {
			$tempMsgIdList['<' . $msgIds[$i]['messageid'] . '>'] = 1;
		} # for
		return $tempMsgIdList;
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
		return ((!empty($artId)) && ($artId > (time() - 900)));
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

		switch ($this->_dbsettings['engine']) {
			case 'pdo_sqlite': $this->_conn->modify("UPDATE nntp SET nowrunning = %d WHERE server = '%s'", Array((int) $runTime, $server));
								if ($this->_conn->rows() == 0) {
									$this->_conn->modify("INSERT INTO nntp(server, nowrunning) VALUES('%s', %d)", Array($server, (int) $runTime));
								} # if
							break;
			default			 : $this->_conn->modify("INSERT INTO nntp (server, nowrunning) VALUES ('%s', %d) ON DUPLICATE KEY UPDATE nowrunning = %d",
										Array($server, (int) $runTime, (int) $runTime));
		} # switch
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

		# en wis nu alles wat 'jonger' is dan deze spot
		switch ($this->_dbsettings['engine']) {
			# geen join delete omdat sqlite dat niet kan
			case 'pdo_sqlite' : {
				$this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE id > %d)", Array($spot['id']));
				$this->_conn->modify("DELETE FROM spottexts WHERE messageid IN (SELECT messageid FROM spots WHERE id > %d)", Array($spot['id']));
				$this->_conn->modify("DELETE FROM spots WHERE id > %d", Array($spot['id']));
				break;
			} # case

			default			  : {
				$this->_conn->modify("DELETE FROM spots, spottexts USING spots
									INNER JOIN spottexts on spots.messageid=spottexts.messageid WHERE spots.id > %d", array($spot['id']));
			} # default
		} # switch
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
		$this->_conn->modify("DELETE FROM commentsxover WHERE id > %d", Array($commentId));
		$this->_conn->modify("DELETE FROM commentsfull WHERE id > %d", Array((int) $fullCommentId));
	} # removeExtraComments

	/*
	 * Zet de tijd/datum wanneer retrieve voor het laatst geupdate heeft
	 */
	function setLastUpdate($server) {
		return $this->_conn->modify("UPDATE nntp SET lastrun = '%d' WHERE server = '%s'", Array(time(), $server));
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
		SpotTiming::start(__FUNCTION__);
		if (empty($sqlFilter)) {
			$query = "SELECT COUNT(1) FROM spots AS s";
		} else {
			$query = "SELECT COUNT(1) FROM spots AS s 
						LEFT JOIN spotsfull AS f ON s.messageid = f.messageid
						LEFT JOIN spottexts AS t ON (s.messageid = t.messageid)
						LEFT JOIN spotstatelist AS l ON s.messageid = l.messageid
						WHERE " . $sqlFilter;
		} # else
		$cnt = $this->_conn->singleQuery($query);
		SpotTiming::stop(__FUNCTION__, array($sqlFilter));
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
	 * Geef alle spots terug in de database die aan $parsedSearch voldoen.
	 * 
	 */
	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch, $sort, $getFull) {
		SpotTiming::start(__FUNCTION__);
		$results = array();
		$offset = (int) $pageNr * (int) $limit;

		# je hebt de zoek criteria (category, titel, etc)
		$criteriaFilter = $parsedSearch['filter'];
		if (!empty($criteriaFilter)) {
			$criteriaFilter = ' WHERE ' . $criteriaFilter;
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

		# er kunnen ook nog additionele velden gevraagd zijn door de filter parser
		# als dat zo is, voeg die dan ook toe
		foreach($parsedSearch['additionalFields'] as $additionalField) {
			$extendedFieldList = ', ' . $additionalField . $extendedFieldList;
		} # foreach
		
		if (!empty($sort)) {
			# Omdat sort zelf op een ambigu veld kan komen, prefixen we dat met 's'
			$sort['field'] = 's.' . $sort['field'];
		} # if

		# Nu prepareren we de sorterings lijst, we voegen hierbij de sortering die we
		# expliciet hebben gekregen, samen met de sortering die voortkomt uit de filtering
		# 
		$sortFields = array_merge(array($sort), $parsedSearch['sortFields']);
		$sortList = array();
		foreach($sortFields as $sortValue) {
			if (!empty($sortValue)) {
				# als er gevraagd is om op 'stamp' descending te sorteren, dan draaien we dit
				# om en voeren de query uit reversestamp zodat we een ASCending sort doen. Dit maakt
				# het voor MySQL ISAM een stuk sneller
				if ((strtolower($sortValue['field']) == 'stamp' || strtolower($sortValue['field']) == 's.stamp') && strtolower($sortValue['direction']) == 'desc') {
					$sortValue['field'] = 's.reversestamp';
					$sortValue['direction'] = 'ASC';					
				} # if
				
				$sortList[] = $sortValue['field'] . ' ' . $sortValue['direction'];
			} # if
		} # foreach
		$sortList = implode(', ', $sortList);

		# en voer de query uit. 
		# We vragen altijd 1 meer dan de gevraagde limit zodat we ook een hasMore boolean flag
		# kunnen zetten.
 		$tmpResult = $this->_conn->arrayQuery("SELECT s.id AS id,
												s.messageid AS messageid,
												s.category AS category,
												s.subcat AS subcat,
												t.poster AS poster,
												l.download as downloadstamp, 
												l.watch as watchstamp,
												l.seen AS seenstamp,
												s.groupname AS groupname,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												t.title AS title,
												t.tag AS tag,
												s.stamp AS stamp,
												s.moderated AS moderated,
												s.filesize AS filesize,
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												f.userid AS userid,
												f.verified AS verified
												" . $extendedFieldList . "
									 FROM spots AS s 
									 LEFT JOIN spottexts AS t ON (s.messageid = t.messageid)
									 LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->safe( (int) $ourUserId) . ")) 
									 LEFT JOIN spotsfull AS f ON (s.messageid = f.messageid) " .
									 $criteriaFilter . " 
									 ORDER BY " . $sortList . 
								   " LIMIT " . (int) ($limit + 1) ." OFFSET " . (int) $offset);

		# als we meer resultaten krijgen dan de aanroeper van deze functie vroeg, dan
		# kunnen we er van uit gaan dat er ook nog een pagina is voor de volgende aanroep
		$hasMore = (count($tmpResult) > $limit);
		if ($hasMore) {
			# verwijder het laatste, niet gevraagde, element
			array_pop($tmpResult);
		} # if

		SpotTiming::stop(__FUNCTION__, array($ourUserId, $pageNr, $limit, $criteriaFilter, $sort, $getFull));
		return array('list' => $tmpResult, 'hasmore' => $hasMore);
	} # getSpots()

	/*
	 * Geeft enkel de header van de spot terug
	 */
	function getSpotHeader($msgId) {
		SpotTiming::start(__FUNCTION__);
		$tmpArray = $this->_conn->arrayQuery("SELECT s.id AS id,
												s.messageid AS messageid,
												s.category AS category,
												s.subcat AS subcat,
												t.poster AS poster,
												s.groupname AS groupname,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												t.title AS title,
												t.tag AS tag,
												s.stamp AS stamp,
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.moderated AS moderated
											  FROM spots AS s
											  LEFT JOIN spottexts AS t ON (s.messageid = t.messageid)
											  WHERE s.messageid = '%s'", Array($msgId));
		if (empty($tmpArray)) {
			return ;
		} # if
		SpotTiming::stop(__FUNCTION__);
		return $tmpArray[0];
	} # getSpotHeader 

	/*
	 * Vraag 1 specifieke spot op, als de volledig spot niet in de database zit
	 * geeft dit NULL terug
	 */
	function getFullSpot($messageId, $ourUserId) {
		SpotTiming::start(__FUNCTION__);
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
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.id AS spotdbid,
												f.id AS fullspotdbid,
												l.download AS downloadstamp,
												l.watch as watchstamp,
												l.seen AS seenstamp,
												f.userid AS userid,
												f.verified AS verified,
												f.usersignature AS \"user-signature\",
												f.userkey AS \"user-key\",
												f.xmlsignature AS \"xml-signature\",
												f.fullxml AS fullxml,
												f.filesize AS filesize
												FROM spots AS s
												LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->safe( (int) $ourUserId) . "))
												JOIN spotsfull AS f ON f.messageid = s.messageid
										  WHERE s.messageid = '%s'", Array($messageId));
		if (empty($tmpArray)) {
			return ;
		} # if
		$tmpArray = $tmpArray[0];

		# If spot is fully stored in db and is of the new type, we process it to
		# make it exactly the same as when retrieved using NNTP
		if (!empty($tmpArray['fullxml']) && (!empty($tmpArray['user-signature']))) {
			$tmpArray['user-key'] = unserialize(base64_decode($tmpArray['user-key']));
		} # if

		SpotTiming::stop(__FUNCTION__, array($messageId, $ourUserId));
		return $tmpArray;		
	} # getFullSpot()

	/*
	 * Insert commentreg, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addCommentRef($messageid, $nntpref, $rating) {
		$this->_conn->modify("INSERT INTO commentsxover(messageid, nntpref, spotrating) VALUES('%s', '%s', %d)",
								Array($messageid, $nntpref, $rating));
	} # addCommentRef

	/*
	 * Insert commentfull, gaat er van uit dat er al een commentsxover entry is
	 */
	function addCommentsFull($commentList) {
		foreach($commentList as $comment) {
			$this->_conn->modify("INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, userid, body, verified) 
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
	 * Update een lijst van messageid's met de gemiddelde spotrating
	 */
	function updateSpotRating($spotMsgIdList) {
		# Geen message id's gegeven? Doe niets!
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($spotMsgIdList as $spotMsgId) {
			$msgIdList .= "'" . $this->_conn->safe($spotMsgId) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);

		# en update de spotrating
		$this->_conn->modify("UPDATE spots 
								SET spotrating = 
									(SELECT AVG(spotrating) as spotrating 
									 FROM commentsxover 
									 WHERE 
										spots.messageid = commentsxover.nntpref 
										AND spotrating BETWEEN 1 AND 10
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $msgIdList . ")
						");
	} # updateSpotRating

	/*
	 * Update een lijst van messageid's met het aantal niet geverifieerde comments
	 */
	function updateSpotCommentCount($spotMsgIdList) {
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($spotMsgIdList as $spotMsgId) {
			$msgIdList .= "'" . $this->_conn->safe($spotMsgId) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);

		# en update de spotrating
		$this->_conn->modify("UPDATE spots 
								SET commentcount = 
									(SELECT COUNT(1) as commentcount 
									 FROM commentsxover 
									 WHERE 
										spots.messageid = commentsxover.nntpref 
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $msgIdList . ")
						");
	} # updateSpotCommentCount

	/*
	 * Vraag de volledige commentaar lijst op, gaat er van uit dat er al een commentsxover entry is
	 */
	function getCommentsFull($nntpRef) {
		SpotTiming::start(__FUNCTION__);

		# en vraag de comments daadwerkelijk op
		$commentList = $this->_conn->arrayQuery("SELECT c.messageid AS messageid, 
														(f.messageid IS NOT NULL) AS havefull,
														f.fromhdr AS fromhdr, 
														f.stamp AS stamp, 
														f.usersignature AS usersignature, 
														f.userkey AS \"user-key\", 
														f.userid AS userid, 
														f.body AS body, 
														f.verified AS verified,
														c.spotrating AS spotrating 
													FROM commentsfull f 
													RIGHT JOIN commentsxover c on (f.messageid = c.messageid)
													WHERE c.nntpref = '%s'
													ORDER BY c.id", array($nntpRef));
		$commentListCount = count($commentList);
		for($i = 0; $i < $commentListCount; $i++) {
			if ($commentList[$i]['havefull']) {
				$commentList[$i]['user-key'] = base64_decode($commentList[$i]['user-key']);
				$commentList[$i]['body'] = explode("\r\n", $commentList[$i]['body']);
			} # if
		} # foreach

		SpotTiming::stop(__FUNCTION__);
		return $commentList;
	} # getCommentsFull

	/*
	 * Geeft huidig database schema versie nummer terug
	 */
	function getSchemaVer() {
		return $this->_conn->singleQuery("SELECT value FROM settings WHERE name = 'schemaversion'");
	} # getSchemaVer

	/* 
	 * Is onze database versie nog wel geldig?
	 */
	function schemaValid() {
		$schemaVer = $this->getSchemaVer();

		# SPOTDB_SCHEMA_VERSION is gedefinieerd bovenin dit bestand
		return ($schemaVer == SPOTDB_SCHEMA_VERSION);
	} # schemaValid

	/*
	 * Verwijder een spot uit de db
	 */
	function deleteSpot($msgId) {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_sqlite' : {
				$this->_conn->modify("DELETE FROM spots WHERE messageid = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM spotsfull WHERE messageid = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN (SELECT nntpref FROM commentsxover WHERE messageid= '%s')", Array($msgId));
				$this->_conn->modify("DELETE FROM spottexts WHERE messageid = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM commentsxover WHERE nntpref = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM spotstatelist WHERE messageid = '%s'", Array($msgId));
				break; 
			} # pdo_sqlite
			default			: {
				$this->_conn->modify("DELETE FROM spots, spottexts, commentsxover USING spots
									LEFT JOIN spottexts ON spots.messageid=spottexts.messageid
									LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
									WHERE spots.messageid = '%s'", Array($msgId));
			} # default
		} # switch
	} # deleteSpot

	/*
	 * Markeer een spot in de db moderated
	 */
	function markSpotModerated($msgId) {
		$this->_conn->modify("UPDATE spots SET moderated = 1 WHERE messageid = '%s'", Array($msgId));
	} # markSpotModerated

	/*
	 * Verwijder oude spots uit de db
	 */
	function deleteSpotsRetention($retention) {
		$retention = $retention * 24 * 60 * 60; // omzetten in seconden

		switch ($this->_dbsettings['engine']) {
 			case 'pdo_sqlite': {
				$this->_conn->modify("DELETE FROM spots WHERE spots.stamp < " . (time() - $retention) );
				$this->_conn->modify("DELETE FROM spotsfull WHERE spotsfull.messageid not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN 
									(SELECT nntpref FROM commentsxover WHERE commentsxover.nntpref not in 
									(SELECT messageid FROM spots))") ;
				$this->_conn->modify("DELETE FROM spottexts WHERE spottexts.messageid not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM commentsxover WHERE commentsxover.nntpref not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM spotstatelist WHERE spotstatelist.messageid not in 
									(SELECT messageid FROM spots)") ;
				break;
			} # pdo_sqlite
			default		: {
				$this->_conn->modify("DELETE FROM spots, spottexts, commentsxover USING spots
					LEFT JOIN spottexts ON spots.messageid=spottexts.messageid
					LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
					WHERE spots.stamp < " . (time() - $retention) );
			} # default
		} # switch
	} # deleteSpotsRetention

	/*
	 * Voeg een spot toe aan de database
	 */
	function addSpot($spot, $fullSpot = array()) {
		$this->_conn->modify("INSERT INTO spots(messageid, category, subcat, groupname, subcata, subcatb, subcatc, subcatd, subcatz, stamp, reversestamp, filesize) 
				VALUES('%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
				 Array($spot['messageid'],
					   (int) $spot['category'],
					   $spot['subcat'],
					   $spot['groupname'],
					   $spot['subcata'],
					   $spot['subcatb'],
					   $spot['subcatc'],
					   $spot['subcatd'],
					   $spot['subcatz'],
					   $spot['stamp'],
					   ($spot['stamp'] * -1),
					   $spot['filesize']) );

		$this->_conn->modify("INSERT INTO spottexts(messageid, poster, title) VALUES('%s', '%s', '%s')",
				 Array($spot['messageid'],
					   $spot['poster'],
					   $spot['title'],
					   $spot['tag']) );

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
		$this->_conn->modify("INSERT INTO spotsfull(messageid, userid, verified, usersignature, userkey, xmlsignature, fullxml, filesize)
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

	function addToSpotStateList($list, $messageId, $ourUserId, $stamp='') {
		SpotTiming::start(__FUNCTION__);
		$verifiedList = $this->verifyListType($list);
		if (empty($stamp)) { $stamp = time(); }

		switch ($this->_dbsettings['engine']) {
			case 'pdo_sqlite': $this->_conn->modify("UPDATE spotstatelist SET " . $verifiedList . " = %d WHERE messageid = '%s' AND ouruserid = %d", array($stamp, $messageId, $ourUserId));
								if ($this->_conn->rows() == 0) {
									$this->_conn->modify("INSERT INTO spotstatelist (messageid, ouruserid, " . $verifiedList . ") VALUES ('%s', %d, %d)",
										Array($messageId, (int) $ourUserId, $stamp));
								} # if
							break;
			default			 : $this->_conn->modify("INSERT INTO spotstatelist (messageid, ouruserid, " . $verifiedList . ") VALUES ('%s', %d, %d) ON DUPLICATE KEY UPDATE " . $verifiedList . " = %d",
										Array($messageId, (int) $ourUserId, $stamp, $stamp));
		} # switch
		SpotTiming::stop(__FUNCTION__, array($list, $messageId, $ourUserId, $stamp));
	} # addToSpotStateList

	function clearSpotStateList($list, $ourUserId) {
		SpotTiming::start(__FUNCTION__);
		$verifiedList = $this->verifyListType($list);
		$this->_conn->modify("UPDATE spotstatelist SET " . $verifiedList . " = NULL WHERE ouruserid = %d", array($ourUserId));
		SpotTiming::stop(__FUNCTION__, array($list, $ourUserId));
	} # clearSpotStatelist

	function cleanSpotStateList() {
		$this->_conn->rawExec("DELETE FROM spotstatelist WHERE download IS NULL AND watch IS NULL AND seen IS NULL");
	} # cleanSpotStateList

	function removeFromSpotStateList($list, $messageid, $ourUserId) {
		SpotTiming::start(__FUNCTION__);
		$verifiedList = $this->verifyListType($list);
		$this->_conn->modify("UPDATE spotstatelist SET " . $verifiedList . " = NULL WHERE messageid = '%s' AND ouruserid = %d LIMIT 1",
				Array($messageid, (int) $ourUserId));
		SpotTiming::stop(__FUNCTION__, array($list, $messageid, $ourUserId));
	} # removeFromSpotStateList

	function verifyListType($list) {
		switch($list) {
			case self::spotstate_Down	: $verifiedList = 'download'; break;
			case self::spotstate_Watch	: $verifiedList = 'watch'; break;
			case self::spotstate_Seen	: $verifiedList = 'seen'; break;
			default						: throw new Exception("Invalid listtype given!");
		} # switch

		return $verifiedList;
	} # verifyListType
	
	
	/* 
	 * Geeft de permissies terug van een bepaalde groep
	 */
	function getGroupPerms($groupId) {
		return $this->_conn->arrayQuery("SELECT permissionid, objectid, deny FROM grouppermissions WHERE groupid = %d",
					Array($groupId));
	} # getgroupPerms
	
	/*
	 * Geeft permissies terug welke user heeft, automatisch in het formaat zoals
	 * SpotSecurity dat heeft (maw - dat de rechtencheck een simpele 'isset' is om 
	 * overhead te voorkomen
	 */
	function getPermissions($userId) {
		$permList = array();
		$tmpList = $this->_conn->arrayQuery('SELECT permissionid, objectid, deny FROM grouppermissions 
												WHERE groupid IN 
													(SELECT groupid FROM usergroups WHERE userid = %d ORDER BY prio)',
											 Array($userId));

		foreach($tmpList as $perm) {
			# Voeg dit permissionid toe aan de lijst met permissies
			if (!isset($permList[$perm['permissionid']])) {
				$permList[$perm['permissionid']] = array();
			} # if
			
			$permList[$perm['permissionid']][$perm['objectid']] = !(boolean) $perm['deny'];
		} # foreach
		
		return $permList;
	} # getPermissions

	/*
	 * Geeft alle gedefinieerde groepen terug
	 */
	function getGroupList($userId) {
		if ($userId == null) {
			return $this->_conn->arrayQuery("SELECT ID,name,0 as \"ismember\" FROM securitygroups");
		} else {
			return $this->_conn->arrayQuery("SELECT sg.id,name,ug.userid IS NOT NULL as \"ismember\" FROM securitygroups sg LEFT JOIN usergroups ug ON (sg.id = ug.groupid) AND (ug.userid = %d)",
										Array($userId));
		} # if
	} # getGroupList
	
	/*
	 * Geef een specifieke security group terug
	 */
	function getSecurityGroup($groupId) {
		return $this->_conn->arrayQuery("SELECT id,name FROM securitygroups WHERE id = %d", Array($groupId));
	} # getSecurityGroup
		
	/*
	 * Wijzigt group membership van een user
	 */
	function setUserGroupList($userId, $groupList) {
		# We wissen eerst huidige group membership
		$this->_conn->modify("DELETE FROM usergroups WHERE userid = %d", array($userId));
		
		foreach($groupList as $groupInfo) {
			$this->_conn->modify("INSERT INTO usergroups(userid,groupid,prio) VALUES(%d, %d, %d)",
						Array($userId, $groupInfo['groupid'], $groupInfo['prio']));
		} # foreach
	} # setUserGroupList

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