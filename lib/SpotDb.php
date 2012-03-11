<?php
define('SPOTDB_SCHEMA_VERSION', '0.58');

class SpotDb {
	private $_dbsettings = null;
	private $_conn = null;
	private $_maxPacketSize = null;

	/*
	 * Constants used for updating the SpotStateList
	 */
	const spotstate_Down	= 0;
	const spotstate_Watch	= 1;
	const spotstate_Seen	= 2;

	/*
	 * Constants used for updating the black/whitelist
	 */
	const spotterlist_Black = 1;
	const spotterlist_White = 2;

	function __construct($db) {
		$this->_dbsettings = $db;
	} # __ctor
	

	/*
	 * Open connectie naar de database (basically factory), de 'engine' wordt uit de 
	 * settings gehaald die mee worden gegeven in de ctor.
	 */
	function connect() {
		SpotTiming::start(__FUNCTION__);

		/* 
		 * Erase username/password so it won't show up in any stacktrace
		 */

		# SQlite heeft geen username gedefinieerd
		if (isset($this->_dbsettings['user'])) {
			$tmpUser = $this->_dbsettings['user'];
			$this->_dbsettings['user'] = '*FILTERED*';
		} # if
		# en ook geen pass
		if (isset($this->_dbsettings['pass'])) {
			$tmpPass = $this->_dbsettings['pass'];
			$this->_dbsettings['pass'] = '*FILTERED*';
		} # if

		switch ($this->_dbsettings['engine']) {
			case 'mysql'	: $this->_conn = new dbeng_mysql($this->_dbsettings['host'],
												$tmpUser,
												$tmpPass,
												$this->_dbsettings['dbname']); 
							  break;

			case 'pdo_mysql': $this->_conn = new dbeng_pdo_mysql($this->_dbsettings['host'],
												$tmpUser,
												$tmpPass,
												$this->_dbsettings['dbname']);
							  break;
							  
			case 'pdo_pgsql' : $this->_conn = new dbeng_pdo_pgsql($this->_dbsettings['host'],
												$tmpUser,
												$tmpPass,
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

	function getMaxPacketSize() {
		if ($this->_maxPacketSize == null) {
			$packet = -1;
			
			switch ($this->_dbsettings['engine']) {
				case 'mysql'		:
				case 'pdo_mysql'	: $packet = $this->_conn->arrayQuery("SHOW VARIABLES LIKE 'max_allowed_packet'"); 
									  $packet = $packet[0]['Value'];
									  break;
			} # switch
			
			$this->_maxPacketSize = $packet;
		} # if
		
		return $this->_maxPacketSize;
	} # getMaxPacketSize

	/* 
	 * Controleer of een messageid niet al eerder gebruikt is door ons om hier
	 * te posten
	 */
	function isNewSpotMessageIdUnique($messageid) {
		/* 
		 * We use a union between our own messageids and the messageids we already
		 * know to prevent a user from spamming the spotweb system by using existing
		 * but valid spots
		 */
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM commentsposted WHERE messageid = '%s'
												  UNION
											    SELECT messageid FROM spots WHERE messageid = '%s'",
						Array($messageid, $messageid));
		
		return (empty($tmpResult));
	} # isNewSpotMessageIdUnique
	
	/*
	 * Add the posted spot to the database
	 */
	function addPostedSpot($userId, $spot, $fullXml) {
		$this->_conn->modify(
				"INSERT INTO spotsposted(ouruserid, messageid, stamp, title, tag, category, subcats, fullxml) 
					VALUES(%d, '%s', %d, '%s', '%s', %d, '%s', '%s')", 
				Array((int) $userId,
					  $spot['newmessageid'],
					  (int) time(),
					  $spot['title'],
					  $spot['tag'],
					  (int) $spot['category'],
					  implode(',', $spot['subcatlist']),
					  $fullXml));
	} # addPostedSpot
	
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
	 * Controleer of een messageid niet al eerder gebruikt is door ons om hier
	 * te posten
	 */
	function isReportMessageIdUnique($messageid) {
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM reportsposted WHERE messageid = '%s'",
						Array($messageid));
		
		return (empty($tmpResult));
	} # isReportMessageIdUnique

	/*
	 * Controleer of een user reeds een spamreport heeft geplaatst voor de betreffende spot
	 */
	function isReportPlaced($messageid, $userId) {
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM reportsposted WHERE inreplyto = '%s' AND ouruserid = %d", Array($messageid, $userId));
		
		return (!empty($tmpResult));
	} #isReportPlaced
	
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
	 * Sla het gepostte report op van deze user
	 */
	function addPostedReport($userId, $report) {
		$this->_conn->modify(
				"INSERT INTO reportsposted(ouruserid, messageid, inreplyto, randompart, body, stamp)
					VALUES('%d', '%s', '%s', '%s', '%s', %d)", 
				Array((int) $userId,
					  $report['newmessageid'],
					  $report['inreplyto'],
					  $report['randomstr'],
					  $report['body'],
					  (int) time()));
	} # addPostedReport

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
			case 'mysql'		:
			case 'pdo_mysql'	: { 
					$this->_conn->modify("INSERT INTO settings(name,value,serialized) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE value = '%s', serialized = %s",
										Array($name, $value, $this->bool2dt($serialized), $value, $this->bool2dt($serialized)));
					 break;
			} # mysql
			
			default				: {
					$this->_conn->exec("UPDATE settings SET value = '%s', serialized = '%s' WHERE name = '%s'", Array($value, $this->bool2dt($serialized), $name));
					if ($this->_conn->rows() == 0) {
						$this->_conn->modify("INSERT INTO settings(name,value,serialized) VALUES('%s', '%s', '%s')", Array($name, $value, $this->bool2dt($serialized)));
					} # if
					break;
			} # default
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
								s.lasthit as lasthit,
								s.ipaddr as ipaddr
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
				"INSERT INTO sessions(sessionid, userid, hitcount, lasthit, ipaddr) 
					VALUES('%s', %d, %d, %d, '%s')",
				Array($session['sessionid'],
					  (int) $session['userid'],
					  (int) $session['hitcount'],
					  (int) $session['lasthit'],
					  $session['ipaddr']));
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
								s.avatar AS avatar,
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
	 * Retrieve a list of userids and some basic properties
	 */
	function getUserList() {
		SpotTiming::start(__FUNCTION__);
		
		$tmpResult = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								u.firstname AS firstname,
								u.lastname AS lastname,
								u.mail AS mail,
								u.lastlogin AS lastlogin,
								s.otherprefs AS prefs
						 FROM users AS u
						 JOIN usersettings s ON (u.id = s.userid)
						 WHERE (NOT DELETED)");
		if (!empty($tmpResult)) {
			# Other preferences are stored serialized in the database
			$tmpResultCount = count($tmpResult);
			for($i = 0; $i < $tmpResultCount; $i++) {
				$tmpResult[$i]['prefs'] = unserialize($tmpResult[$i]['prefs']);
			} # for
		} # if

		SpotTiming::stop(__FUNCTION__, array());
		return $tmpResult;
	} # getUserList

	/*
	 * Haalt een user op uit de database 
	 */
	function getUserListForDisplay() {
		SpotTiming::start(__FUNCTION__);
		
		$tmpResult = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								MAX(u.firstname) AS firstname,
								MAX(u.lastname) AS lastname,
								MAX(u.mail) AS mail,
								MAX(u.lastlogin) AS lastlogin,
								COALESCE(MAX(ss.lasthit), MAX(u.lastvisit)) AS lastvisit,
								MAX(ipaddr) AS lastipaddr
							FROM users AS u
							LEFT JOIN sessions ss ON (u.id = ss.userid)
							WHERE (DELETED = '%s')
							GROUP BY u.id, u.username", array($this->bool2dt(false)));

		SpotTiming::stop(__FUNCTION__, array());
		return $tmpResult;
	} # getUserListForDisplay

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
								WHERE id = %d", 
				Array($user['firstname'],
					  $user['lastname'],
					  $user['mail'],
					  $user['apikey'],
					  (int) $user['lastlogin'],
					  (int) $user['lastvisit'],
					  (int) $user['lastread'],
					  (int) $user['lastapiusage'],
					  $this->bool2dt($user['deleted']),
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
										VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
								Array($user['username'], 
									  $user['firstname'],
									  $user['lastname'],
									  $user['passhash'],
									  $user['mail'],
									  $user['apikey'],
									  $this->getMaxMessageTime(),
									  $this->bool2dt(false)));

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
		switch ($this->_dbsettings['engine']) {
			case 'mysql'		:
			case 'pdo_mysql'	: { 
					$this->_conn->modify("INSERT INTO nntp(server, maxarticleid) VALUES ('%s', '%s') ON DUPLICATE KEY UPDATE maxarticleid = '%s'",
										Array($server, (int) $maxarticleid, (int) $maxarticleid));
					 break;
			} # mysql
			
			default				: {
					$this->_conn->exec("UPDATE nntp SET maxarticleid = '%s' WHERE server = '%s'", Array((int) $maxarticleid, $server));
					if ($this->_conn->rows() == 0) {
						$this->_conn->modify("INSERT INTO nntp(server, maxarticleid) VALUES('%s', '%s')", Array($server, (int) $maxarticleid));
					} # if
					break;
			} # default
		} # switch
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
		} elseif ($headers == 'comments') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM commentsxover ORDER BY id DESC LIMIT 5000");
		} elseif ($headers == 'reports') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM reportsxover ORDER BY id DESC LIMIT 5000");
		} else {
			throw new Exception("getMaxMessageId() header-type value is unknown");
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
	function createTextQuery($fieldList) {
		$ftsEng = dbfts_abs::Factory($this->_conn);
		return $ftsEng->createTextQuery($fieldList);
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
			case 'mysql'		:
			case 'pdo_mysql' 	: {
				$this->_conn->modify("INSERT INTO nntp (server, nowrunning) VALUES ('%s', %d) ON DUPLICATE KEY UPDATE nowrunning = %d",
								Array($server, (int) $runTime, (int) $runTime));
				break;
			} # mysql
			
			default				: {
				$this->_conn->modify("UPDATE nntp SET nowrunning = %d WHERE server = '%s'", Array((int) $runTime, $server));
				if ($this->_conn->rows() == 0) {
					$this->_conn->modify("INSERT INTO nntp(server, nowrunning) VALUES('%s', %d)", Array($server, (int) $runTime));
				} # if
			} # default
		} # switch
	} # setRetrieverRunning

	/*
	 * Remove extra spots 
	 */
	function removeExtraSpots($messageId) {
		# vraag eerst het id op
		$spot = $this->getSpotHeader($messageId);

		/*
		 * The spot might be empty because - for example, the spot
		 * is moderated (and hence deleted), the highest spot retrieved
		 * might be missing from the database because of the spam cleanup.
		 *
		 * Ignore this error
		 */
		if (empty($spot)) {
			return ;
		} # if

		# en wis nu alles wat 'jonger' is dan deze spot
		switch ($this->_dbsettings['engine']) {
			# geen join delete omdat sqlite dat niet kan
			case 'pdo_pgsql'  : 
			case 'pdo_sqlite' : {
				$this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE id > %d)", Array($spot['id']));
				$this->_conn->modify("DELETE FROM spots WHERE id > %d", Array($spot['id']));
				break;
			} # case

			default			  : {
				$this->_conn->modify("DELETE FROM spots, spotsfull USING spots
										LEFT JOIN spotsfull on spots.messageid=spotsfull.messageid
									  WHERE spots.id > %d", array($spot['id']));
			} # default
		} # switch
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
		$this->_conn->modify("DELETE FROM commentsxover WHERE id > %d", Array($commentId));
	} # removeExtraComments


	/*
	 * Remove extra comments
	 */
	function removeExtraReports($messageId) {
		# vraag eerst het id op
		$reportId = $this->_conn->singleQuery("SELECT id FROM reportsxover WHERE messageid = '%s'", Array($messageId));
		
		# als deze report leeg is, is er iets raars aan de hand
		if (empty($reportId)) {
			throw new Exception("Our highest report is not in the database!?");
		} # if

		# en wis nu alles wat 'jonger' is dan deze spot
		$this->_conn->modify("DELETE FROM reportsxover WHERE id > %d", Array($reportId));
	} # removeExtraReports
	
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
						LEFT JOIN spotstatelist AS l ON s.messageid = l.messageid
						LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND (bl.ouruserid = -1) AND (bl.idtype = 1))
						WHERE " . $sqlFilter . " AND (bl.spotterid IS NULL)";
		} # else
		$cnt = $this->_conn->singleQuery($query);
		SpotTiming::stop(__FUNCTION__, array($sqlFilter));
		if ($cnt == null) {
			return 0;
		} else {
			return $cnt;
		} # if
	} # getSpotCount

	function getSpotCountPerHour($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'	: $rs = $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM to_timestamp(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); break;
			case 'pdo_sqlite'	: $rs = $this->_conn->arrayQuery("SELECT strftime('%H', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); break;
			default				: $rs = $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM FROM_UNIXTIME(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
		} # switch
		return $rs;
	} # getSpotCountPerHour

	function getSpotCountPerWeekday($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'	: $rs = $this->_conn->arrayQuery("SELECT EXTRACT(DOW FROM to_timestamp(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); break;
			case 'pdo_sqlite'	: $rs = $this->_conn->arrayQuery("SELECT strftime('%w', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); break;
			default				: $rs = $this->_conn->arrayQuery("SELECT FROM_UNIXTIME(stamp,'%w') AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
		} # switch
		return $rs;
	} # getSpotCountPerWeekday

	function getSpotCountPerMonth($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'	: $rs = $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM to_timestamp(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); break;
			case 'pdo_sqlite'	: $rs = $this->_conn->arrayQuery("SELECT strftime('%m', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); break;
			default				: $rs = $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM FROM_UNIXTIME(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
		} # switch
		return $rs;
	} # getSpotCountPerMonth

	function getSpotCountPerCategory($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		$rs = $this->_conn->arrayQuery("SELECT category AS data, COUNT(category) AS amount FROM spots " . $filter . " GROUP BY data;");
		return $rs;
	} # getSpotCountPerCategory

	function getOldestSpotTimestamp() {
		$rs = $this->_conn->singleQuery("SELECT MIN(stamp) FROM spots;");
		return $rs;
	} # getOldestSpotTimestamp

	/*
	 * Match set of comments
	 */
	function matchCommentMessageIds($hdrList) {
		# We negeren commentsfull hier een beetje express, als die een 
		# keer ontbreken dan fixen we dat later wel.
		$idList = array('comment' => array(), 'fullcomment' => array());

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
		$rs = $this->_conn->arrayQuery("SELECT messageid AS comment, '' AS fullcomment FROM commentsxover WHERE messageid IN (" . $msgIdList . ")
											UNION
					 				    SELECT '' as comment, messageid AS fullcomment FROM commentsfull WHERE messageid IN (" . $msgIdList . ")");

		# en lossen we het hier op
		foreach($rs as $msgids) {
			if (!empty($msgids['comment'])) {
				$idList['comment'][$msgids['comment']] = 1;
			} # if

			if (!empty($msgids['fullcomment'])) {
				$idList['fullcomment'][$msgids['fullcomment']] = 1;
			} # if
		} # foreach

		return $idList;
	} # matchCommentMessageIds

	/*
	 * Match set of reports
	 */
	function matchReportMessageIds($hdrList) {
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
		$rs = $this->_conn->arrayQuery("SELECT messageid FROM reportsxover WHERE messageid IN (" . $msgIdList . ")");

		# geef hier een array terug die kant en klaar is voor array_search
		foreach($rs as $msgids) {
			$idList[$msgids['messageid']] = 1;
		} # foreach
		
		return $idList;
	} # matchReportMessageIds
	
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
	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch) {
		SpotTiming::start(__FUNCTION__);
		$results = array();
		$offset = (int) $pageNr * (int) $limit;

		# je hebt de zoek criteria (category, titel, etc)
		$criteriaFilter = " WHERE (bl.spotterid IS NULL) ";
		if (!empty($parsedSearch['filter'])) {
			$criteriaFilter .= ' AND ' . $parsedSearch['filter'];
		} # if 

		# er kunnen ook nog additionele velden gevraagd zijn door de filter parser
		# als dat zo is, voeg die dan ook toe
		$extendedFieldList = '';
		foreach($parsedSearch['additionalFields'] as $additionalField) {
			$extendedFieldList = ', ' . $additionalField . $extendedFieldList;
		} # foreach

		# ook additionele tabellen kunnen gevraagd zijn door de filter parser, die 
		# moeten we dan ook toevoegen
		$additionalTableList = '';
		foreach($parsedSearch['additionalTables'] as $additionalTable) {
			$additionalTableList = ', ' . $additionalTable . $additionalTableList;
		} # foreach

		# zelfs additionele joinskunnen gevraagd zijn door de filter parser, die 
		# moeten we dan ook toevoegen
		$additionalJoinList = '';
		foreach($parsedSearch['additionalJoins'] as $additionalJoin) {
			$additionalJoinList = ' ' . $additionalJoin['jointype'] . ' JOIN ' . 
							$additionalJoin['tablename'] . ' AS ' . $additionalJoin['tablealias'] .
							' ON (' . $additionalJoin['joincondition'] . ') ';
		} # foreach
		
		# Nu prepareren we de sorterings lijst
		$sortFields = $parsedSearch['sortFields'];
		$sortList = array();
		foreach($sortFields as $sortValue) {
			if (!empty($sortValue)) {
				# als er gevraagd is om op 'stamp' descending te sorteren, dan draaien we dit
				# om en voeren de query uit reversestamp zodat we een ASCending sort doen. Dit maakt
				# het voor MySQL ISAM een stuk sneller
				if ((strtolower($sortValue['field']) == 's.stamp') && strtolower($sortValue['direction']) == 'desc') {
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
												s.poster AS poster,
												l.download as downloadstamp, 
												l.watch as watchstamp,
												l.seen AS seenstamp,
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
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.reportcount AS reportcount,
												s.spotterid AS spotterid,
												f.verified AS verified,
												COALESCE(bl.idtype, wl.idtype, gwl.idtype) AS idtype
												" . $extendedFieldList . "
									 FROM spots AS s " . 
									 $additionalTableList . 
									 $additionalJoinList . 
								   " LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->safe( (int) $ourUserId) . ")) 
									 LEFT JOIN spotsfull AS f ON (s.messageid = f.messageid) 
									 LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = " . $this->safe( (int) $ourUserId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
									 LEFT JOIN spotteridblacklist as wl on ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = " . $this->safe( (int) $ourUserId) . ")) AND (wl.idtype = 2)) 
									 LEFT JOIN spotteridblacklist as gwl on ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1)) AND (gwl.idtype = 2)) " .
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

		SpotTiming::stop(__FUNCTION__, array($ourUserId, $pageNr, $limit, $criteriaFilter));
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
												s.poster AS poster,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												s.title AS title,
												s.tag AS tag,
												s.stamp AS stamp,
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.reportcount AS reportcount,
												s.moderated AS moderated
											  FROM spots AS s
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
												s.poster AS poster,
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
												s.reportcount AS reportcount,
												s.filesize AS filesize,
												s.spotterid AS spotterid,
												l.download AS downloadstamp,
												l.watch as watchstamp,
												l.seen AS seenstamp,
												f.verified AS verified,
												f.usersignature AS \"user-signature\",
												f.userkey AS \"user-key\",
												f.xmlsignature AS \"xml-signature\",
												f.fullxml AS fullxml,
												COALESCE(bl.idtype, wl.idtype, gwl.idtype) AS listidtype
												FROM spots AS s
												LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->safe( (int) $ourUserId) . "))
												LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = " . $this->safe( (int) $ourUserId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
												LEFT JOIN spotteridblacklist as wl on ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = " . $this->safe( (int) $ourUserId) . ")) AND (wl.idtype = 2)) 
												LEFT JOIN spotteridblacklist as gwl on ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1)) AND (gwl.idtype = 2))
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
	 * Insert commentref, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addComments($comments, $fullComments = array()) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		if ($this->_dbsettings['engine'] == 'pdo_sqlite') {
			$chunks = array_chunk($comments, 1);
		} else {
			$chunks = array_chunk($comments, 100);
		} # else

		foreach($chunks as $comments) {
			$insertArray = array();

			foreach($comments as $comment) {
				$insertArray[] = vsprintf("('%s', '%s', %d, %d)",
						 Array($this->safe($comment['messageid']),
							   $this->safe($comment['nntpref']),
							   $this->safe($comment['rating']),
							   $this->safe($comment['stamp'])));
			} # foreach

			# Actually insert the batch
			if (!empty($insertArray)) {
				$this->_conn->modify("INSERT INTO commentsxover(messageid, nntpref, spotrating, stamp)
									  VALUES " . implode(',', $insertArray), array());
			} # if
		} # foreach
		$this->commitTransaction();

		if (!empty($fullComments)) {
			$this->addFullComments($fullComments);
		} # if
	} # addComments

	/*
	 * Insert commentfull, gaat er van uit dat er al een commentsxover entry is
	 */
	function addFullComments($fullComments) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		if ($this->_dbsettings['engine'] == 'pdo_sqlite') {
			$chunks = array_chunk($fullComments, 1);
		} else {
			$chunks = array_chunk($fullComments, 100);
		} # else

		foreach($chunks as $fullComments) {
			$insertArray = array();

			foreach($fullComments as $comment) {
				# Kap de verschillende strings af op een maximum van 
				# de datastructuur, de unique keys kappen we expres niet af
				$comment['fromhdr'] = substr($comment['fromhdr'], 0, 127);

				$insertArray[] = vsprintf("('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s')",
						Array($this->safe($comment['messageid']),
							  $this->safe($comment['fromhdr']),
							  $this->safe($comment['stamp']),
							  $this->safe($comment['user-signature']),
							  $this->safe(serialize($comment['user-key'])),
							  $this->safe($comment['spotterid']),
							  $this->safe(implode("\r\n", $comment['body'])),
							  $this->bool2dt($comment['verified']),
							  $this->safe($comment['user-avatar'])));
			} # foreach

			# Actually insert the batch
			$this->_conn->modify("INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, spotterid, body, verified, avatar)
								  VALUES " . implode(',', $insertArray), array());
		} # foreach

		$this->commitTransaction();
	} # addFullComments

	/*
	 * Insert addReportRef, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addReportRefs($reportList) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		if ($this->_dbsettings['engine'] == 'pdo_sqlite') {
			$chunks = array_chunk($reportList, 1);
		} else {
			$chunks = array_chunk($reportList, 100);
		} # else

		foreach($chunks as $reportList) {
			$insertArray = array();
			
			foreach($reportList as $report) {
				$insertArray[] = vsprintf("('%s', '%s', '%s', '%s')",
						Array($this->safe($report['messageid']),
							  $this->safe($report['fromhdr']),
							  $this->safe($report['keyword']),
							  $this->safe($report['nntpref'])));
			} # foreach

			# Actually insert the batch
			$this->_conn->modify("INSERT INTO reportsxover(messageid, fromhdr, keyword, nntpref)
									VALUES " . implode(',', $insertArray), array());
		} # foreach

		$this->commitTransaction();
	} # addReportRefs

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
		foreach($spotMsgIdList as $spotMsgId => $v) {
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
		foreach($spotMsgIdList as $spotMsgId => $v) {
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
	 * Update een lijst van messageid's met het aantal niet geverifieerde reports
	 */
	function updateSpotReportCount($spotMsgIdList) {
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($spotMsgIdList as $spotMsgId => $v) {
			$msgIdList .= "'" . $this->_conn->safe($spotMsgId) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);
		
		# en update de spotrating
		$this->_conn->modify("UPDATE spots 
								SET reportcount = 
									(SELECT COUNT(1) as reportcount 
									 FROM reportsxover
									 WHERE 
										spots.messageid = reportsxover.nntpref 
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $msgIdList . ")
						");
	} # updateSpotReportCount

	/*
	 * Vraag de volledige commentaar lijst op, gaat er van uit dat er al een commentsxover entry is
	 */
	function getCommentsFull($userId, $nntpRef) {
		SpotTiming::start(__FUNCTION__);

		# en vraag de comments daadwerkelijk op
		$commentList = $this->_conn->arrayQuery("SELECT c.messageid AS messageid, 
														(f.messageid IS NOT NULL) AS havefull,
														f.fromhdr AS fromhdr, 
														f.stamp AS stamp, 
														f.usersignature AS \"user-signature\", 
														f.userkey AS \"user-key\", 
														f.spotterid AS spotterid, 
														f.body AS body, 
														f.verified AS verified,
														c.spotrating AS spotrating,
														c.moderated AS moderated,
														f.avatar as \"user-avatar\",
														bl.idtype AS idtype
													FROM commentsfull f 
													RIGHT JOIN commentsxover c on (f.messageid = c.messageid)
													LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = f.spotterid) AND (bl.doubled = '%s'))
													WHERE c.nntpref = '%s' AND ((bl.spotterid IS NULL) OR (((bl.ouruserid = " . $this->safe( (int) $userId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 2)))
													ORDER BY c.id", array($this->bool2dt(false), $nntpRef));
		$commentListCount = count($commentList);
		for($i = 0; $i < $commentListCount; $i++) {
			if ($commentList[$i]['havefull']) {
				$commentList[$i]['user-key'] = unserialize($commentList[$i]['user-key']);
				$commentList[$i]['body'] = explode("\r\n", $commentList[$i]['body']);
			} # if
		} # for

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
	 * Removes a comment from the database
	 */
	function removeComment($msgId) {
		$this->_conn->modify("DELETE FROM commentsfull WHERE messageid = '%s'", Array($msgId));
		$this->_conn->modify("DELETE FROM commentsxover WHERE messageid = '%s'", Array($msgId));
	} # removeComment

	/*
	 * Verwijder een spot uit de db
	 */
	function deleteSpot($msgId) {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'  : 
			case 'pdo_sqlite' : {
				$this->_conn->modify("DELETE FROM spots WHERE messageid = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM spotsfull WHERE messageid = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN (SELECT messageid FROM commentsxover WHERE nntpref= '%s')", Array($msgId));
				$this->_conn->modify("DELETE FROM commentsxover WHERE nntpref = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM spotstatelist WHERE messageid = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM reportsxover WHERE nntpref = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM reportsposted WHERE inreplyto = '%s'", Array($msgId));
				$this->_conn->modify("DELETE FROM cache WHERE resourceid = '%s'", Array($msgId));
				break; 
			} # pdo_sqlite
			
			default			: {
				$this->_conn->modify("DELETE FROM spots, spotsfull, commentsxover, reportsxover, spotstatelist, reportsposted, cache USING spots
									LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
									LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
									LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
									LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
									LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
									LEFT JOIN cache ON spots.messageid=cache.resourceid
									WHERE spots.messageid = '%s'", Array($msgId));
			} # default
		} # switch
	} # deleteSpot

	/*
	 * Markeer een spot in de db moderated
	 */
	function markSpotModerated($msgId) {
		$this->_conn->modify("UPDATE spots SET moderated = '%s' WHERE messageid = '%s'", Array($this->bool2dt(true), $msgId));
	} # markSpotModerated

	/*
	 * Markeer een comment in de db moderated
	 */
	function markCommentModerated($msgId) {
		$this->_conn->modify("UPDATE commentsxover SET moderated = '%s' WHERE messageid = '%s'", Array($this->bool2dt(true), $msgId));
	} # markCommentModerated

	/*
	 * Verwijder oude spots uit de db
	 */
	function deleteSpotsRetention($retention) {
		$retention = $retention * 24 * 60 * 60; // omzetten in seconden

		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql' : 
 			case 'pdo_sqlite': {
				$this->_conn->modify("DELETE FROM spots WHERE spots.stamp < " . (time() - $retention) );
				$this->_conn->modify("DELETE FROM spotsfull WHERE spotsfull.messageid not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN 
									(SELECT messageid FROM commentsxover WHERE commentsxover.nntpref not in 
									(SELECT messageid FROM spots))") ;
				$this->_conn->modify("DELETE FROM commentsxover WHERE commentsxover.nntpref not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM reportsxover WHERE reportsxover.nntpref not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM spotstatelist WHERE spotstatelist.messageid not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM reportsposted WHERE reportsposted.inreplyto not in 
									(SELECT messageid FROM spots)") ;
				$this->_conn->modify("DELETE FROM cache WHERE (cache.cachetype = %d OR cache.cachetype = %d) AND cache.resourceid not in 
									(SELECT messageid FROM spots)", Array(SpotCache::SpotImage, SpotCache::SpotNzb)) ;
				break;
			} # pdo_sqlite
			default		: {
				$this->_conn->modify("DELETE FROM spots, spotsfull, commentsxover, reportsxover, spotstatelist, reportsposted, cache USING spots
					LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
					LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
					LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
					LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
					LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
					LEFT JOIN cache ON spots.messageid=cache.resourceid
					WHERE spots.stamp < " . (time() - $retention) );
			} # default
		} # switch
	} # deleteSpotsRetention

	/*
	 * Voeg een reeks met spots toe aan de database
	 */
	function addSpots($spots, $fullSpots = array()) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		if ($this->_dbsettings['engine'] == 'pdo_sqlite') {
			$chunks = array_chunk($spots, 1);
		} else {
			$chunks = array_chunk($spots, 100);
		} # else
		
		foreach($chunks as $spots) {
			$insertArray = array();
			
			foreach($spots as $spot) {
				# we checken hier handmatig of filesize wel numeriek is, dit is omdat printen met %d in sommige PHP
				# versies een verkeerde afronding geeft bij >32bits getallen.
				if (!is_numeric($spot['filesize'])) {
					$spot['filesize'] = 0;
				} # if
				
				# Kap de verschillende strings af op een maximum van 
				# de datastructuur, de unique keys kappen we expres niet af
				$spot['poster'] = substr($spot['poster'], 0, 127);
				$spot['title'] = substr($spot['title'], 0, 127);
				$spot['tag'] = substr($spot['tag'], 0, 127);
				$spot['subcata'] = substr($spot['subcata'], 0, 63);
				$spot['subcatb'] = substr($spot['subcatb'], 0, 63);
				$spot['subcatc'] = substr($spot['subcatc'], 0, 63);
				$spot['subcatd'] = substr($spot['subcatd'], 0, 63);
				
				# Kap de verschillende strings af op een maximum van 
				# de datastructuur, de unique keys en de RSA keys en dergeijke
				# kappen we expres niet af
				$spot['spotterid'] = substr($spot['spotterid'], 0, 31);
				
				$insertArray[] = vsprintf("('%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s')",
						 Array($this->safe($spot['messageid']),
							   $this->safe($spot['poster']),
							   $this->safe($spot['title']),
							   $this->safe($spot['tag']),
							   $this->safe((int) $spot['category']),
							   $this->safe($spot['subcata']),
							   $this->safe($spot['subcatb']),
							   $this->safe($spot['subcatc']),
							   $this->safe($spot['subcatd']),
							   $this->safe($spot['subcatz']),
							   (int) $this->safe($spot['stamp']),
							   (int) $this->safe(($spot['stamp'] * -1)),
							   $this->safe($spot['filesize']),
							   $this->safe($spot['spotterid']))); # Filesize mag niet naar int gecast worden, dan heb je 2GB limiet
			} # foreach

			# Actually insert the batch
			if (!empty($insertArray)) {
				$this->_conn->modify("INSERT INTO spots(messageid, poster, title, tag, category, subcata, 
														subcatb, subcatc, subcatd, subcatz, stamp, reversestamp, filesize, spotterid) 
									  VALUES " . implode(',', $insertArray), array());
			} # if
		} # foreach
		$this->commitTransaction();
		
		if (!empty($fullSpots)) {
			$this->addFullSpots($fullSpots);
		} # if
	} # addSpot()

	/*
	 * Voeg enkel de full spot toe aan de database, niet gebruiken zonder dat er een entry in 'spots' staat
	 * want dan komt deze spot niet in het overzicht te staan.
	 */
	function addFullSpots($fullSpots) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		if ($this->_dbsettings['engine'] == 'pdo_sqlite') {
			$chunks = array_chunk($fullSpots, 1);
		} else {
			$chunks = array_chunk($fullSpots, 100);
		} # else
	
		foreach($chunks as $fullSpots) {
			$insertArray = array();

			# en voeg het aan de database toe
			foreach($fullSpots as $fullSpot) {
				$insertArray[] = vsprintf("('%s', '%s', '%s', '%s', '%s', '%s')",
						Array($this->safe($fullSpot['messageid']),
							  $this->bool2dt($fullSpot['verified']),
							  $this->safe($fullSpot['user-signature']),
							  $this->safe(base64_encode(serialize($fullSpot['user-key']))),
							  $this->safe($fullSpot['xml-signature']),
							  $this->safe($fullSpot['fullxml'])));
			} # foreach

			# Actually insert the batch
			$this->_conn->modify("INSERT INTO spotsfull(messageid, verified, usersignature, userkey, xmlsignature, fullxml)
								  VALUES " . implode(',', $insertArray), array());
		} # foreach

		$this->commitTransaction();
	} # addFullSpot

	function addToSpotStateList($list, $messageId, $ourUserId, $stamp='') {
		SpotTiming::start(__FUNCTION__);
		$verifiedList = $this->verifyListType($list);
		if (empty($stamp)) { $stamp = time(); }

		switch ($this->_dbsettings['engine']) {
			case 'pdo_mysql'	:
			case 'mysql'		:  {
				$this->_conn->modify("INSERT INTO spotstatelist (messageid, ouruserid, " . $verifiedList . ") VALUES ('%s', %d, %d) ON DUPLICATE KEY UPDATE " . $verifiedList . " = %d",
										Array($messageId, (int) $ourUserId, $stamp, $stamp));
				break;
			} # mysql
			
			default				:  {
				$this->_conn->modify("UPDATE spotstatelist SET " . $verifiedList . " = %d WHERE messageid = '%s' AND ouruserid = %d", array($stamp, $messageId, $ourUserId));
				if ($this->_conn->rows() == 0) {
					$this->_conn->modify("INSERT INTO spotstatelist (messageid, ouruserid, " . $verifiedList . ") VALUES ('%s', %d, %d)",
						Array($messageId, (int) $ourUserId, $stamp));
				} # if
			} # default
		} # switch
		SpotTiming::stop(__FUNCTION__, array($list, $messageId, $ourUserId, $stamp));
	} # addToSpotStateList

	function clearSpotStateList($list, $ourUserId) {
		SpotTiming::start(__FUNCTION__);
		$verifiedList = $this->verifyListType($list);
		$this->_conn->modify("UPDATE spotstatelist SET " . $verifiedList . " = NULL WHERE ouruserid = %d", array( (int) $ourUserId));
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
			return $this->_conn->arrayQuery("SELECT id,name,0 as \"ismember\" FROM securitygroups");
		} else {
			return $this->_conn->arrayQuery("SELECT sg.id,name,ug.userid IS NOT NULL as \"ismember\" FROM securitygroups sg LEFT JOIN usergroups ug ON (sg.id = ug.groupid) AND (ug.userid = %d)",
										Array($userId));
		} # if
	} # getGroupList
	
	/*
	 * Verwijdert een permissie uit een security group
	 */
	function removePermFromSecGroup($groupId, $perm) {
		$this->_conn->modify("DELETE FROM grouppermissions WHERE (groupid = %d) AND (permissionid = %d) AND (objectid = '%s')", 
				Array($groupId, $perm['permissionid'], $perm['objectid']));
	} # removePermFromSecGroup

	/*
	 * Zet een permissie op deny in een security group
	 */
	function setDenyForPermFromSecGroup($groupId, $perm) {
		$this->_conn->modify("UPDATE grouppermissions SET deny = '%s' WHERE (groupid = %d) AND (permissionid = %d) AND (objectid = '%s')", 
				Array($this->bool2dt($perm['deny']), $groupId, $perm['permissionid'], $perm['objectid']));
	} # removePermFromSecGroup
	
	/*
	 * Voegt een permissie aan een security group toe
	 */
	function addPermToSecGroup($groupId, $perm) {
		$this->_conn->modify("INSERT INTO grouppermissions(groupid,permissionid,objectid) VALUES (%d, %d, '%s')",
				Array($groupId, $perm['permissionid'], $perm['objectid']));
	} # addPermToSecGroup

	/*
	 * Geef een specifieke security group terug
	 */
	function getSecurityGroup($groupId) {
		return $this->_conn->arrayQuery("SELECT id,name FROM securitygroups WHERE id = %d", Array($groupId));
	} # getSecurityGroup
		
	/*
	 * Geef een specifieke security group terug
	 */
	function setSecurityGroup($group) {
		$this->_conn->modify("UPDATE securitygroups SET name = '%s' WHERE id = %d", Array($group['name'], $group['id']));
	} # setSecurityGroup
	
	/*
	 * Geef een specifieke security group terug
	 */
	function addSecurityGroup($group) {
		$this->_conn->modify("INSERT INTO securitygroups(name) VALUES ('%s')", Array($group['name']));
	} # addSecurityGroup

	/*
	 * Geef een specifieke security group terug
	 */
	function removeSecurityGroup($group) {
		$this->_conn->modify("DELETE FROM securitygroups WHERE id = %d", Array($group['id']));
	} # removeSecurityGroup
	
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
	
	/*
	 * Voegt een nieuwe notificatie toe
	 */
	function addNewNotification($userId, $objectId, $type, $title, $body) {
		$this->_conn->modify("INSERT INTO notifications(userid,stamp,objectid,type,title,body,sent) VALUES(%d, %d, '%s', '%s', '%s', '%s', '%s')",
					Array($userId, (int) time(), $objectId, $type, $title, $body, $this->bool2dt(false)));
	} # addNewNotification
	
	/*
	 * Haalt niet-verzonden notificaties op van een user
	 */
	function getUnsentNotifications($userId) {
		return $this->_conn->arrayQuery("SELECT id,userid,objectid,type,title,body FROM notifications WHERE userid = %d AND NOT SENT;",
					Array($userId));
	} # getUnsentNotifications

	/* 
	 * Een notificatie updaten
	 */
	function updateNotification($msg) {
		$this->_conn->modify("UPDATE notifications SET title = '%s', body = '%s', sent = '%s' WHERE id = %d",
					Array($msg['title'], $msg['body'], $this->bool2dt($msg['sent']), $msg['id']));
	} // updateNotification
	
	/*
	 * Voegt een spotterid toe aan een lijst
	 */
	function addSpotterToList($spotterId, $ourUserId, $origin, $idType) {
		$existInList = $this->_conn->singleQuery("SELECT idtype FROM spotteridblacklist WHERE spotterid = '%s' AND ouruserid = %d", Array($spotterId, (int) $ourUserId));
		if (empty($existInList)) {
			$this->_conn->modify("INSERT INTO spotteridblacklist(spotterid, origin, ouruserid, idtype) VALUES ('%s', '%s', %d, %d)",
						Array($spotterId, $origin, (int) $ourUserId, (int) $idType));
		} else {
			$this->_conn->modify("UPDATE spotteridblacklist SET idtype = %d, origin = '%s' WHERE spotterid = '%s' AND ouruserid = %d", Array( (int) $idType, $origin, $spotterId, (int) $ourUserId));
		}
	} # addSpotterToList

	/*
	 * Removes a specific spotter from the blacklist
	 */
	function removeSpotterFromList($spotterId, $ourUserId) {
		$this->_conn->modify("DELETE FROM spotteridblacklist WHERE ouruserid = %d AND spotterid = '%s'",
					Array((int) $ourUserId, $spotterId));
	} # removeSpotterFromList
	
	/*
	 * Geeft alle blacklisted spotterid's terug
	 */
	function getSpotterList($ourUserId) {
		return $this->_conn->arrayQuery("SELECT spotterid, origin, ouruserid, idtype FROM spotteridblacklist WHERE ouruserid = %d ORDER BY idtype",
					Array((int) $ourUserId));
	} # getSpotterList

	/*
	 * Returns one specific blacklisted record for a given spotterid
	 */
	function getBlacklistForSpotterId($userId, $spotterId) {
		$tmp = $this->_conn->arrayQuery("SELECT spotterid, origin, ouruserid FROM spotteridblacklist WHERE spotterid = '%s' and ouruserid = %d",
					Array($spotterId, $userId));
					
		if (!empty($tmp)) {
			return $tmp[0];
		} else {
			return false;
		} # else
	} # getBlacklistForSpotterId
	
	/*
	 * Verwijder een filter en de children toe (recursive)
	 */
	function deleteFilter($userId, $filterId, $filterType) {
		$filterList = $this->getFilterList($userId, $filterType);
		foreach($filterList as $filter) {
		
			if ($filter['id'] == $filterId) {
				foreach($filter['children'] as $child) {
					$this->deleteFilter($userId, $child['id'], $filterType);
				} # foreach
			} # if
			
			$this->_conn->modify("DELETE FROM filters WHERE userid = %d AND id = %d", 
					Array($userId, $filterId));
		} # foreach
	} # deleteFilter
	
	/*
	 * Voegt een filter en de children toe (recursive)
	 */
	function addFilter($userId, $filter) {
		$this->_conn->modify("INSERT INTO filters(userid, filtertype, title, icon, torder, tparent, tree, valuelist, sorton, sortorder)
								VALUES(%d, '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s')",
							Array((int) $userId,
								  $filter['filtertype'],
								  $filter['title'],
								  $filter['icon'],
								  (int) $filter['torder'],
								  (int) $filter['tparent'],
								  $filter['tree'],
								  implode('&', $filter['valuelist']),
								  $filter['sorton'],
								  $filter['sortorder']));
		$parentId = $this->_conn->lastInsertId('filters');

		foreach($filter['children'] as $tmpFilter) {
			$tmpFilter['tparent'] = $parentId;
			$this->addFilter($userId, $tmpFilter);
		} # foreach
	} # addFilter
	
	/*
	 * Copieert de filterlijst van een user naar een andere user
	 */
	function copyFilterList($srcId, $dstId) {
		$filterList = $this->getFilterList($srcId, '');
		
		foreach($filterList as $filterItems) {
			$this->addFilter($dstId, $filterItems);
		} # foreach
	} # copyFilterList
	
	/*
	 * Verwijdert alle ingestelde filters voor een user
	 */
	function removeAllFilters($userId) {
		$this->_conn->modify("DELETE FROM filters WHERE userid = %d", Array((int) $userId));
	} # removeAllfilters

	/*
	 * Get a specific filter
	 */
	function getFilter($userId, $filterId) {
		/* Haal de lijst met filter values op */
		$tmpResult = $this->_conn->arrayQuery("SELECT id,
													  userid,
													  filtertype,
													  title,
													  icon,
													  torder,
													  tparent,
													  tree,
													  valuelist,
													  sorton,
													  sortorder 
												FROM filters 
												WHERE userid = %d AND id = %d",
					Array((int) $userId, (int) $filterId));
		if (!empty($tmpResult)) {
			return $tmpResult[0];
		} else {
			return false;
		} # else
	} # getFilter

	/*
	 * Get a specific index filter 
	 */
	function getUserIndexFilter($userId) {
		/* Haal de lijst met filter values op */
		$tmpResult = $this->_conn->arrayQuery("SELECT id,
													  userid,
													  filtertype,
													  title,
													  icon,
													  torder,
													  tparent,
													  tree,
													  valuelist,
													  sorton,
													  sortorder 
												FROM filters 
												WHERE userid = %d AND filtertype = 'index_filter'",
					Array((int) $userId));
		if (!empty($tmpResult)) {
			return $tmpResult[0];
		} else {
			return false;
		} # else
	} # getUserIndexFilter
	
	
	/*
	 * Get a specific filter
	 */
	function updateFilter($userId, $filter) {
		/* Haal de lijst met filter values op */
		$tmpResult = $this->_conn->modify("UPDATE filters 
												SET title = '%s',
												    icon = '%s',
													torder = %d,
													tparent = %d
												WHERE userid = %d AND id = %d",
					Array($filter['title'], 
						  $filter['icon'], 
						  (int) $filter['torder'], 
						  (int) $filter['tparent'], 
						  (int) $userId, 
						  (int) $filter['id']));
	} # updateFilter

	/* 
	 * Haalt de filterlijst op als een platte lijst
	 */
	function getPlainFilterList($userId, $filterType) {
		/* willen we een specifiek soort filter hebben? */
		if (empty($filterType)) {
			$filterTypeFilter = '';
		} else {
			$filterTypeFilter = " AND filtertype = 'filter'"; 
		} # else
		
		/* Haal de lijst met filter values op */
		return $this->_conn->arrayQuery("SELECT id,
											  userid,
											  filtertype,
											  title,
											  icon,
											  torder,
											  tparent,
											  tree,
											  valuelist,
											  sorton,
											  sortorder 
										FROM filters 
										WHERE userid = %d " . $filterTypeFilter . "
										ORDER BY tparent,torder", /* was: id, tparent, torder */
				Array($userId));
	} # getPlainFilterList
	
	/*
	 * Haalt de filter lijst op en formatteert die in een boom
	 */
	function getFilterList($userId, $filterType) {
		$tmpResult = $this->getPlainFilterList($userId, $filterType);
		$idMapping = array();
		foreach($tmpResult as &$tmp) {
			$idMapping[$tmp['id']] =& $tmp;
		} # foreach
		
		/* Hier zetten we het om naar een daadwerkelijke boom */
		$tree = array();
		foreach($tmpResult as &$filter) {
			if (!isset($filter['children'])) {
				$filter['children'] = array();
			} # if
			
			# de filter waardes zijn URL encoded opgeslagen 
			# en we gebruiken de & om individuele filterwaardes
			# te onderscheiden
			$filter['valuelist'] = explode('&', $filter['valuelist']);
			
			if ($filter['tparent'] == 0) {
				$tree[$filter['id']] =& $filter;
			} else {
				$idMapping[$filter['tparent']]['children'][] =& $filter;
			} # else
		} # foreach

		return $tree;
	} # getFilterList
	
	/*
	 * Returns a list of all unique filter combinations
	 */
	function getUniqueFilterCombinations() {
		return $this->_conn->arrayQuery("SELECT tree,valuelist FROM filters GROUP BY tree,valuelist ORDER BY tree,valuelist");
	} # getUniqueFilterCombinations

	/*
	 * Add a filter count for a specific SHA1 hash
	 * of a filter for this specific user
	 */
	function setCachedFilterCount($userId, $filterHashes) {
		$maxSpotStamp = $this->getMaxMessageTime();
		
		foreach($filterHashes as $filterHash => $filterCount) {
			/* Remove any existing cached filtercount for this user */		
			$this->_conn->modify("DELETE FROM filtercounts WHERE (userid = %d) AND (filterhash = '%s')",
									Array((int) $userId, $filterHash));
									
			/* and insert our new filtercount hash */
			$this->_conn->modify("INSERT INTO filtercounts(userid, filterhash, currentspotcount, lastvisitspotcount, lastupdate) 
											VALUES(%d, '%s', %d, %d, %d)",
									Array((int) $userId, $filterHash, $filterCount['currentspotcount'], $filterCount['lastvisitspotcount'], 
										  $maxSpotStamp ));
		} # foreach
	} # setCachedFilterCount

	/*
	 * Add a filter count for a specific SHA1 hash
	 * of a filter for this specific user
	 */
	function getNewCountForFilters($userId) {
		$filterHashes = array();
		$tmp = $this->_conn->arrayQuery("SELECT f.filterhash AS filterhash, 
												f.currentspotcount AS currentspotcount, 
												f.lastvisitspotcount AS lastvisitspotcount, 
												f.lastupdate AS lastupdate,
												t.currentspotcount - f.lastvisitspotcount AS newspotcount
										 FROM filtercounts f
										 INNER JOIN filtercounts t ON (t.filterhash = f.filterhash)
										 WHERE t.userid = -1 
										   AND f.userid = %d",
								Array((int) $userId) );
								
		foreach($tmp as $cachedItem) {
			$filterHashes[$cachedItem['filterhash']] = array('currentspotcount' => $cachedItem['currentspotcount'],
															 'lastvisitspotcount' => $cachedItem['lastvisitspotcount'],
															 'newspotcount' => $cachedItem['newspotcount'],
															 'lastupdate' => $cachedItem['lastupdate']);
		} # foreach
		
		return $filterHashes;
	} # getNewCountForFilters
	
	/*
	 * Makes sure all registered users have at least counts
	 * for all existing filters.
	 */
	function createFilterCountsForEveryone() {
		$userIdList = $this->_conn->arrayQuery('SELECT id FROM users WHERE id <> -1');
		
		foreach($userIdList as $user) {
			$userId = $user['id'];
			
			/* We can assume userid -1 (baseline) has all the filters which exist */
			$filterList = $this->getPlainFilterList($userId, '');
			$cachedList = $this->getCachedFilterCount($userId);

			/* We add a dummy entry for 'all new spots' */
			$filterList[] = array('id' => 9999, 'userid' => $userId, 'filtertype' => 'dummyfilter', 
								'title' => 'NewSpots', 'icon' => '', 'torder' => 0, 'tparent' => 0,
								'tree' => '', 'valuelist' => 'New:0', 'sorton' => '', 'sortorder' => '');
			
			foreach($filterList as $filter) {
				$filterHash = sha1($filter['tree'] . '|' . urldecode($filter['valuelist']));
				
				# Do we have a cache entry already for this filter?
				if (!isset($cachedList[$filterHash])) {
					/*
					 * Create the cached count filter
					 */
					$filter['currentspotcount'] = 0;
					$filter['lastvisitspotcount'] = 0;
					
					$this->setCachedFilterCount($userId, array($filterHash => $filter));
				} # if
			} # foreach
		} # foreach
	} # createFilterCountsForEveryone
	
	/*
	 * Retrieves the filtercount for a specific userid
	 */
	function getCachedFilterCount($userId) {
		$filterHashes = array();
		$tmp = $this->_conn->arrayQuery("SELECT filterhash, currentspotcount, lastvisitspotcount, lastupdate FROM filtercounts WHERE userid = %d",
								Array( (int) $userId) );
		
		foreach($tmp as $cachedItem) {
			$filterHashes[$cachedItem['filterhash']] = array('currentspotcount' => $cachedItem['currentspotcount'],
															 'lastvisitspotcount' => $cachedItem['lastvisitspotcount'],
															 'lastupdate' => $cachedItem['lastupdate']);
		} # foreach

		return $filterHashes;
	} # getCachedFilterCount
	
	/*
	 * Resets the unread count for a specific user
	 */
	function resetFilterCountForUser($userId) {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'	: 
			case 'pdo_sqlite'	: {
				$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, filterhash FROM filtercounts WHERE userid = -1", array());
				foreach($filterList as $filter) {
					$this->_conn->modify("UPDATE filtercounts
												SET lastvisitspotcount = currentspotcount,
													currentspotcount = %d
												WHERE (filterhash = '%s') 
												  AND (userid = %d)",
									Array((int) $filter['currentspotcount'], $filter['filterhash'], (int) $userId));
				} # foreach
				
				break;
			} # sqlite

			default				: {
				$this->_conn->modify("UPDATE filtercounts f, filtercounts t
											SET f.lastvisitspotcount = f.currentspotcount,
												f.currentspotcount = t.currentspotcount
											WHERE (f.filterhash = t.filterhash) 
											  AND (t.userid = -1) 
											  AND (f.userid = %d)",
								Array((int) $userId) );
			} # default
		} # switch
	} # resetFilterCountForUser

	/*
	 * Updates the last filtercounts for sessions which are active at the moment
	 */
	function updateCurrentFilterCounts() {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'	: 
			case 'pdo_sqlite'	: {
				/*
  				 * Update the current filter counts if the session
				 * is still active
				 */
				$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, lastupdate, filterhash FROM filtercounts WHERE userid = -1", array());
				foreach($filterList as $filter) {
					$this->_conn->modify("UPDATE filtercounts
												SET currentspotcount = %d,
													lastupdate = %d
												WHERE (filterhash = '%s') 
												  AND (userid IN (SELECT userid FROM sessions WHERE lasthit > lastupdate GROUP BY userid ))",
									Array((int) $filter['currentspotcount'], (int) $filter['lastupdate'], $filter['filterhash']));
				} # foreach
				
				break;
			} # pdo_sqlite
			
			default				: {
				/*
				 * We do this in two parts because MySQL seems to fall over 
				 * when we use a subquery
				 */
				$sessionList = $this->_conn->arrayQuery("SELECT s.userid FROM sessions s 
																   INNER JOIN filtercounts f ON (f.userid = s.userid) 
														 WHERE lasthit > f.lastupdate 
														 GROUP BY s.userid",
														 array());
				
				# bereid de lijst voor met de queries in de where
				$userIdList = '';
				foreach($sessionList as $session) {
					$userIdList .= (int) $this->_conn->safe($session['userid']) . ", ";
				} # foreach
				$userIdList = substr($userIdList, 0, -2);

				/*
  				 * Update the current filter counts if the session
				 * is still active
				 */
				if (!empty($userIdList)) {
					$this->_conn->modify("UPDATE filtercounts f, filtercounts t 
											SET f.currentspotcount = t.currentspotcount,
												f.lastupdate = t.lastupdate
											WHERE (f.filterhash = t.filterhash) 
											  AND (t.userid = -1)
											  AND (f.userid IN (" . $userIdList . "))");
				} # if

				/*
				 * Sometimes retrieve removes some sports, make sure
				 * we do not get confusing results
				 */
				$this->_conn->modify("UPDATE filtercounts f, filtercounts t
										SET f.lastvisitspotcount = t.currentspotcount
										WHERE (f.filterhash = t.filterhash) 
										  AND (f.lastvisitspotcount > t.currentspotcount)
										  AND (t.userid = -1)");
			} # default
		} # switch
	} # updateCurrentFilterCounts

	/*
	 * Mark all filters as read
	 */
	function markFilterCountAsSeen($userId) {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_sqlite'	: 
			case 'pdo_pgsql'	: {
				$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, lastupdate, filterhash FROM filtercounts WHERE userid = -1", array());
				foreach($filterList as $filter) {
					$this->_conn->modify("UPDATE filtercounts
												SET lastvisitspotcount = %d,
													currentspotcount = %d,
													lastupdate = %d
												WHERE (filterhash = '%s') 
												  AND (userid = %d)",
									Array((int) $filter['currentspotcount'],
										  (int) $filter['currentspotcount'],
										  (int) $filter['lastupdate'],
										  $filter['filterhash'], 
										  (int) $userId));
				} # foreach
				
				break;
			} # pdo_sqlite

			default				: {
				 $this->_conn->modify("UPDATE filtercounts f, filtercounts t
										SET f.lastvisitspotcount = t.currentspotcount,
											f.currentspotcount = t.currentspotcount,
											f.lastupdate = t.lastupdate
										WHERE (f.filterhash = t.filterhash) 
										  AND (t.userid = -1) 
										  AND (f.userid = %d)",
							Array( (int) $userId) );
			} # default
		} # switch
	} # markFilterCountAsSeen
	
	/*
	 * Create an entry in the auditlog
	 */
	function addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr) {
		return $this->_conn->modify("INSERT INTO permaudit(stamp, userid, permissionid, objectid, result, ipaddr) 
										VALUES(%d, %d, %d, '%s', '%s', '%s')",
								Array(time(), (int) $userid, (int) $perm, $objectid, $this->bool2dt($allowed), $ipaddr));
	} # addAuditEntry

	/*
	 * Removes items from the cache older than a specific amount of days
	 */
	function expireCache($expireDays) {
		return $this->_conn->modify("DELETE FROM cache WHERE (cachetype = %d OR cachetype = %d OR cachetype = %d) AND stamp < %d", Array(SpotCache::Web, SpotCache::Statistics, SpotCache::StatisticsData,(int) time()-$expireDays*24*60*60));
	} # expireCache

	/*
	 * Removes items from te commentsfull table older than a specific amount of days
	 */
	function expireCommentsFull($expireDays) {
		return $this->_conn->modify("DELETE FROM commentsfull WHERE stamp < %d", Array((int) time() - ($expireDays*24*60*60)));
	} # expireCommentsFull

	/*
	 * Removes items from te commentsfull table older than a specific amount of days
	 */
	function expireSpotsFull($expireDays) {
		return $this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE stamp < %d)", Array((int) time() - ($expireDays*24*60*60)));
	} # expireCommentsFull

	/*
	 * Retrieves wether a specific resourceid is cached
	 */
	function isCached($resourceid, $cachetype) {
		$tmpResult = $this->_conn->singleQuery("SELECT resourceid FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", Array($resourceid, $cachetype));

		return (!empty($tmpResult));
	} # isCached

	/*
	 * Returns the resource from the cache table, if we have any
	 */
	function getCache($resourceid, $cachetype) {
		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql' : {
				$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata, serialized, content FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", array($resourceid, $cachetype));
				if (!empty($tmp)) {
					$tmp[0]['content'] = stream_get_contents($tmp[0]['content']);
				} # if
				
				break;
			} # case 'pdo_pgsql'

			case 'mysql'		:
			case 'pdo_mysql'	: { 
				$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata, serialized, UNCOMPRESS(content) AS content FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", array($resourceid, $cachetype));
				break;
			} # mysql

			default		: {
				$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata, serialized, content FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", array($resourceid, $cachetype));
			} # default
		} # switch

		if (!empty($tmp)) {
			if ($tmp[0]['serialized'] == 1) {
				$tmp[0]['content'] = unserialize($tmp[0]['content']);
			} # if

			$tmp[0]['metadata'] = unserialize($tmp[0]['metadata']);
			return $tmp[0];
		} # if

		return false;
	} # getCache

	/*
	 * Refreshen the cache timestamp to prevent it from being stale
	 */
	function updateCacheStamp($resourceid, $cachetype) {
		$this->_conn->exec("UPDATE cache SET stamp = %d WHERE resourceid = '%s' AND cachetype = '%s'", Array(time(), $resourceid, $cachetype));
	} # updateCacheStamp

	/*
	 * Add a resource to the cache
	 */
	function saveCache($resourceid, $cachetype, $metadata, $content) {
		if (is_array($content)) {
			$serialize = true;
			$content = serialize($content);
		} else {
			$serialize = false;
		} # else

		if ($metadata) {
			$metadata = serialize($metadata);
		} # if

		if ($this->getMaxPacketsize() > 0 && (strlen($content)*1.15)+115 > $this->getMaxPacketSize()) {
			return;
		} # if

		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'	: {
					$this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', serialized = '%s', content = '%b' WHERE resourceid = '%s' AND cachetype = '%s'", Array(time(), $metadata, $this->bool2dt($serialize), $content, $resourceid, $cachetype));
					if ($this->_conn->rows() == 0) {
						$this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata,serialized,content) VALUES ('%s', '%s', %d, '%s', '%s', '%b')", Array($resourceid, $cachetype, time(), $metadata, $this->bool2dt($serialize), $content));
					} # if
					break;
			} # pgsql

			case 'mysql'		:
			case 'pdo_mysql'	: { 
					$this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', serialized = '%s', content = COMPRESS('%s') WHERE resourceid = '%s' AND cachetype = '%s'", Array(time(), $metadata, $this->bool2dt($serialize), $content, $resourceid, $cachetype));
					if ($this->_conn->rows() == 0) {
						$this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata,serialized,content) VALUES ('%s', '%s', %d, '%s', '%s', COMPRESS('%s'))", Array($resourceid, $cachetype, time(), $metadata, $this->bool2dt($serialize), $content));
					} # if
					break;
			} # mysql

			default				: {
					$this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', serialized = '%s', content = '%s' WHERE resourceid = '%s' AND cachetype = '%s'", Array(time(), $metadata, $this->bool2dt($serialize), $content, $resourceid, $cachetype));
					if ($this->_conn->rows() == 0) {
						$this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata,serialized,content) VALUES ('%s', '%s', %d, '%s', '%s', '%s')", Array($resourceid, $cachetype, time(), $metadata, $this->bool2dt($serialize), $content));
					} # if
			} # default
		} # switch
	} # saveCache
	
	/*
	 * Updates a users' setting with an base64 encoded image
	 */
	function setUserAvatar($userId, $imageEncoded) {
		$this->_conn->modify("UPDATE usersettings SET avatar = '%s' WHERE userid = %d", Array( $imageEncoded, (int) $userId));
	} # setUserAvatar

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

	/*
	 * Converts a boolean value to a string
	 * for usage by the database
	 */
	function bool2dt($b) {
		return $this->_conn->bool2dt($b);
	} # bool2dt
		
	function removeOldList($listUrl,$idtype) {
		$this->_conn->modify("DELETE FROM spotteridblacklist WHERE (ouruserid = -1) AND (origin = 'external') AND (idtype = %d)",Array((int) $idtype));
		$this->_conn->modify("DELETE FROM cache WHERE (resourceid = '%s') AND (cachetype = '%s')", Array(md5($listUrl), SpotCache::Web));
	} # removeOldList
	
	function updateExternalList($newlist,$idtype) {
		$updatelist = array();
		$updskipped = 0;
		$countnewlistspotterid = 0;
		$countdellistspotterid = 0;
		/* Haal de oude lijst op*/
		$oldlist = $this->_conn->arrayQuery("SELECT spotterid,idtype
												FROM spotteridblacklist 
												WHERE ouruserid = -1 AND origin = 'external'");
		foreach ($oldlist as $obl) {
			$islisted = (($obl['idtype'] == $idtype) > 0);
			$updatelist[$obl['spotterid']] = 3 - $islisted;  # 'oude' spotterid eerst op verwijderen zetten.
		}
		/* verwerk de nieuwe lijst */
		foreach ($newlist as $nwl) {
			$nwl = trim($nwl);									# Enters en eventuele spaties wegfilteren
			if ((strlen($nwl) >= 3) && (strlen($nwl) <= 6)) {	# de lengte van een spotterid is tussen 3 en 6 karakters groot (tot op heden)
				if (empty($updatelist[$nwl])) {
					$updatelist[$nwl] = 1;						# nieuwe spoterids toevoegen 
				} elseif ($updatelist[$nwl] == 2) {
					$updatelist[$nwl] = 5;						# spotterid staat al op dezelfde lijst, niet verwijderen.
				} elseif ($updatelist[$nwl] == 3) {
					if ($idtype == 1) {
						$updatelist[$nwl] = 4;					# spotterid staat op een andere lijst, idtype veranderen.
					} else {
						$updskipped++;							# spotterid staat al op de blacklist, niet veranderen.
						$updatelist[$nwl] = 5;
					}
				} else {
					$updskipped++;								# dubbel spotterid in xxxxxlist.txt.
				}
			} else {
				$updskipped++;									# er is iets mis met het spotterid (bijvoorbeeld een lege regel in xxxxxlist.txt)
			}
		}
		$updlist = array_keys($updatelist);
		foreach ($updlist as $updl) {
			if ($updatelist[$updl] == 1) {
				# voeg nieuwe spotterid's toe aan de lijst
				$countnewlistspotterid++;
				$this->_conn->modify("INSERT INTO spotteridblacklist (spotterid,ouruserid,idtype,origin) VALUES ('%s','-1',%d,'external')", Array($updl, (int) $idtype));
				$this->_conn->modify("UPDATE spotteridblacklist SET doubled = '%s' WHERE spotterid = '%s'AND ouruserid != -1  AND idtype = %d ", Array($this->bool2dt(true), $updl, (int) $idtype));
			} elseif ($updatelist[$updl] == 2) {
				# verwijder spotterid's die niet meer op de lijst staan
				$countdellistspotterid++;
				$this->_conn->modify("DELETE FROM spotteridblacklist WHERE (spotterid = '%s') AND (ouruserid = -1) AND (origin = 'external')", Array($updl));
				$this->_conn->modify("UPDATE spotteridblacklist SET doubled = '%s' WHERE spotterid = '%s' AND ouruserid != -1 AND idtype = %d ", Array($this->bool2dt(true), $updl, (int) $idtype));
			} elseif ($updatelist[$updl] == 4) {
				$countnewlistspotterid++;
				$this->_conn->modify("UPDATE spotteridblacklist SET idtype = 1 WHERE (spotterid = '%s') AND (ouruserid = -1) AND (origin = 'external')", Array($updl));
				$this->_conn->modify("UPDATE spotteridblacklist SET doubled = (idtype = 1) WHERE spotterid = '%s' AND ouruserid != -1", Array($updl));
			}

		}
		return array('added' => $countnewlistspotterid,'removed' => $countdellistspotterid,'skipped' => $updskipped);
	} # updateExternallist
	
} # class db
