<?php
define('SPOTDB_SCHEMA_VERSION', '0.58');

class SpotDb {
	private $_auditDao;
	private $_blackWhiteListDao;
	private $_cacheDao;
	private $_commentDao;
	private $_notificationDao;
	private $_sessionDao;
	private $_settingDao;
	private $_spotReportDao;

	private $_dbsettings = null;
	private $_conn = null;

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
							  $daoFactory = Dao_Factory::getDAOFactory("mysql");
							  break;

			case 'pdo_mysql': $this->_conn = new dbeng_pdo_mysql($this->_dbsettings['host'],
												$tmpUser,
												$tmpPass,
												$this->_dbsettings['dbname']);
							  $daoFactory = Dao_Factory::getDAOFactory("mysql");
							  break;
							  
			case 'pdo_pgsql' : $this->_conn = new dbeng_pdo_pgsql($this->_dbsettings['host'],
												$tmpUser,
												$tmpPass,
												$this->_dbsettings['dbname']);
							  $daoFactory = Dao_Factory::getDAOFactory("postgresql");
							  break;
							
			case 'pdo_sqlite': $this->_conn = new dbeng_pdo_sqlite($this->_dbsettings['path']);
							  $daoFactory = Dao_Factory::getDAOFactory("sqlite");
							   break;

			default			: throw new Exception('Unknown DB engine specified (' . $this->_dbsettings['engine'] . ', please choose pdo_pgsql, mysql or pdo_mysql');
		} # switch

		$daoFactory->setConnection($this->_conn);
		$this->_auditDao = $daoFactory->getAuditDao();
		$this->_blackWhiteListDao = $daoFactory->getBlackWhiteListDao();
		$this->_cacheDao = $daoFactory->getCacheDao();
		$this->_commentDao = $daoFactory->getCommentDao();
		$this->_notificationDao = $daoFactory->getNotificationDao();
		$this->_sessionDao = $daoFactory->getSessionDao();
		$this->_settingDao = $daoFactory->getSettingDao();
		$this->_spotReportDao = $daoFactory->getSpotReportDao();

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
	 * Checkt of een username al bestaat
	 */
	function findUserIdForName($username) {
		return $this->_conn->singleQuery("SELECT id FROM users WHERE username = '%s'", Array($username));
	} # findUserIdForName

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
							LEFT JOIN (SELECT userid, lasthit, ipaddr, devicetype FROM sessions WHERE sessions.userid = userid ORDER BY lasthit) AS ss ON (u.id = ss.userid)
							WHERE (deleted = '%s')
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
		SpotTiming::start('SpotDb::' . __FUNCTION__);
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

		SpotTiming::stop('SpotDb::' . __FUNCTION__, array($messageId, $ourUserId));
		return $tmpArray;		
	} # getFullSpot()



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
	 * Verwijder een spot uit de db
	 */
	function removeSpots($spotMsgIdList) {
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($spotMsgIdList as $spotMsgId) {
			$msgIdList .= "'" . $this->_conn->safe($spotMsgId) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);

		switch ($this->_dbsettings['engine']) {
			case 'pdo_pgsql'  : 
			case 'pdo_sqlite' : {
				$this->_conn->modify("DELETE FROM spots WHERE messageid IN (" . $msgIdList . ")");
				$this->_conn->modify("DELETE FROM spotsfull WHERE messageid  IN (" . $msgIdList . ")");
				$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN (SELECT messageid FROM commentsxover WHERE nntpref IN (" . $msgIdList . "))");
				$this->_conn->modify("DELETE FROM commentsxover WHERE nntpref  IN (" . $msgIdList . ")");
				$this->_conn->modify("DELETE FROM spotstatelist WHERE messageid  IN (" . $msgIdList . ")");
				$this->_conn->modify("DELETE FROM reportsxover WHERE nntpref  IN (" . $msgIdList . ")");
				$this->_conn->modify("DELETE FROM reportsposted WHERE inreplyto  IN (" . $msgIdList . ")");
				$this->_conn->modify("DELETE FROM cache WHERE resourceid  IN (" . $msgIdList . ")");
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
									WHERE spots.messageid  IN (" . $msgIdList . ")");
			} # default
		} # switch
	} # removeSpots

	/*
	 * Markeer een spot in de db moderated
	 */
	function markSpotsModerated($spotMsgIdList) {
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($spotMsgIdList as $spotMsgId) {
			$msgIdList .= "'" . $this->_conn->safe($spotMsgId) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);

		$this->_conn->modify("UPDATE spots SET moderated = '%s' WHERE messageid IN (" . $msgIdList . ")", Array($this->bool2dt(true)));
	} # markSpotsModerated

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
	 * Update the spots table with some information contained in the 
	 * fullspots information. 
	 *
	 * Some information in the fullspot is more reliable because 
	 * more fidelity encoding.
	 */
	function updateSpotInfoFromFull($fullSpot) {
		$this->_conn->modify("UPDATE spots SET title = '%s', spotterid = '%s' WHERE messageid = '%s'",
							Array($fullSpot['title'], $fullSpot['spotterid'], $fullSpot['messageid']));
	} # updateSpotInfoFromFull

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
		if (empty($stamp)) { $stamp = time(); }

		switch($list) {
			case self::spotstate_Down	: $verifiedList = 'download'; break;
			case self::spotstate_Watch	: $verifiedList = 'watch'; break;
			case self::spotstate_Seen	: $verifiedList = 'seen'; break;
			default						: throw new Exception("Invalid listtype given!");
		} # switch

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

	/*
	 * Mark all as read can perform different functions, 
	 * depending on the state of the system. 
	 *
	 * If only 'seen' is kept, in the statelist, we just set
	 * seen to NULL to mark it as not explicitly seen.
	 *
	 * If either 'download' or 'watch' is also set, we update
	 * the seen timestamp, this allows us to show any new
	 * comments from the last time the spot was viewed
	 */
	function markAllAsRead($ourUserId) {
		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spotstatelist SET seen = NULL WHERE (ouruserid = %d) AND (download IS NULL) AND (watch IS NULL) ", array( (int) $ourUserId));
		$this->_conn->modify("UPDATE spotstatelist SET seen = %d WHERE (ouruserid = %d) AND (download IS NOT NULL) OR (watch IS NOT NULL) ", array( (int) time(), (int) $ourUserId));
		SpotTiming::stop(__FUNCTION__, array($list, $ourUserId));
	} # markAllAsRead

	function clearDownloadList($ourUserId) {
		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spotstatelist SET download = NULL WHERE ouruserid = %d", array( (int) $ourUserId));
		SpotTiming::stop(__FUNCTION__, array($list, $ourUserId));
	} # clearDownloadList

	function cleanSpotStateList() {
		$this->_conn->rawExec("DELETE FROM spotstatelist WHERE download IS NULL AND watch IS NULL AND seen IS NULL");
	} # cleanSpotStateList

	function removeFromWatchList($messageid, $ourUserId) {
		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spotstatelist SET watch = NULL WHERE messageid = '%s' AND ouruserid = %d LIMIT 1",
				Array($messageid, (int) $ourUserId));
		SpotTiming::stop(__FUNCTION__, array($list, $messageid, $ourUserId));
	} # removeFromWatchList

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
		$this->_conn->modify("INSERT INTO filters(userid, filtertype, title, icon, torder, tparent, tree, valuelist, sorton, sortorder, enablenotify)
								VALUES(%d, '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s')",
							Array((int) $userId,
								  $filter['filtertype'],
								  $filter['title'],
								  $filter['icon'],
								  (int) $filter['torder'],
								  (int) $filter['tparent'],
								  $filter['tree'],
								  implode('&', $filter['valuelist']),
								  $filter['sorton'],
								  $filter['sortorder'],
								  $this->bool2dt($filter['enablenotify'])));
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
													  sortorder,
													  enablenotify 
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
													  sortorder,
													  enablenotify 
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
													tparent = %d,
													enablenotify = '%s'
												WHERE userid = %d AND id = %d",
					Array($filter['title'], 
						  $filter['icon'], 
						  (int) $filter['torder'], 
						  (int) $filter['tparent'], 
						  $this->bool2dt($filter['enablenotify']),
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
											  sortorder,
											  enablenotify 
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
	 * Returns the user ids for this filter combination
	 */
	function getUsersForFilter($tree, $valuelist) {
		return $this->_conn->arrayQuery("SELECT title, userid, enablenotify FROM filters WHERE tree = '%s' AND valuelist = '%s'",
				 Array($tree, $valuelist));
	} # getUsersForFilter

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
								'tree' => '', 'valuelist' => 'New:0', 'sorton' => '', 'sortorder' => '',
								'enablenotify' => false);
			
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
			case 'pdo_sqlite'	: {
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

			case 'pdo_pgsql'	: {
				$this->_conn->modify("UPDATE filtercounts AS f
											SET lastvisitspotcount = o.currentspotcount,
												currentspotcount = o.currentspotcount,
												lastupdate = o.lastupdate
											FROM filtercounts AS o
											WHERE (f.filterhash = o.filterhash) 
											  AND (f.userid = %d) AND (o.userid = %d)",
								Array((int) $userId, (int) $userid));

				break;
			} # pdo_pgsql

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
	 * Removes items from te commentsfull table older than a specific amount of days
	 */
	function expireSpotsFull($expireDays) {
		return $this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE stamp < %d)", Array((int) time() - ($expireDays*24*60*60)));
	} # expireCommentsFull

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
		

	/* --------------------------- */
	function addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr) {
		return $this->_auditDao->addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr);
	} # addAuditEntry

	function removeOldList($listUrl,$idtype) {
		return $this->_blackWhiteListDao->removeOldList($listUrl,$idtype);
	}
	function updateExternalList($newlist,$idtype) {
		return $this->_blackWhiteListDao->updateExternalList($newlist,$idtype);
	}
	function addSpotterToList($spotterId, $ourUserId, $origin, $idType)
	{
		return $this->_blackWhiteListDao->addSpotterToList($spotterId, $ourUserId, $origin, $idType);
	}
	function removeSpotterFromList($spotterId, $ourUserId) {
		return $this->_blackWhiteListDao->removeSpotterFromList($spotterId, $ourUserId);
	}
	function getSpotterList($ourUserId) {
		return $this->_blackWhiteListDao->getSpotterList($ourUserId);
	}
	function getBlacklistForSpotterId($userId, $spotterId) {
		return $this->_blackWhiteListDao->getBlacklistForSpotterId($userId, $spotterId);
	}	
	function expireCache($expireDays) {
		return $this->_cacheDao->expireCache($expireDays);
	}
	function isCached($resourceid, $cachetype) {
		return $this->_cacheDao->isCached($resourceid, $cachetype);
	}
	function getCache($resourceid, $cachetype) {
		return $this->_cacheDao->getCache($resourceid, $cachetype);
	}
	function updateCacheStamp($resourceid, $cachetype) {
		return $this->_cacheDao->updateCacheStamp($resourceid, $cachetype);
	}
	function saveCache($resourceid, $cachetype, $metadata, $content) {
		return $this->_cacheDao->saveCache($resourceid, $cachetype, $metadata, $content);
	}
	function isCommentMessageIdUnique($messageid) {
		return $this->_commentDao->isCommentMessageIdUnique($messageid);
	}
	function removeExtraComments($messageId) {
		return $this->_commentDao->removeExtraComments($messageId);
	}
	function addPostedComment($userId, $comment) {
		return $this->_commentDao->addPostedComment($userId, $comment);
	}
	function matchCommentMessageIds($hdrList) {
		return $this->_commentDao->matchCommentMessageIds($hdrList);
	}
	function addComments($comments, $fullComments = array()) {
		return $this->_commentDao->addComments($comments, $fullComments);
	}
	function addFullComments($fullComments) {
		return $this->_commentDao->addFullComments($fullComments);
	}
	function getCommentsFull($userId, $nntpRef) {
		return $this->_commentDao->getCommentsFull($userId, $nntpRef);
	}
	function getNewCommentCountFor($nntpRefList, $ourUserId) {
		return $this->_commentDao->getNewCommentCountFor($nntpRefList, $ourUserId);
	}
	function markCommentsModerated($commentMsgIdList) {
		return $this->_commentDao->markCommentsModerated($commentMsgIdList);
	}
	function removeComments($commentMsgIdList) {
		return $this->_commentDao->removeComments($commentMsgIdList);
	}
	function expireCommentsFull($expireDays) {
		return $this->_commentDao->expireCommentsFull($expireDays);
	}
	function addNewNotification($userId, $objectId, $type, $title, $body) {
		return $this->_notificationDao->addNewNotification($userId, $objectId, $type, $title, $body);
	}
	function getUnsentNotifications($userId) {
		return $this->_notificationDao->getUnsentNotifications($userId);
	}
	function updateNotification($msg) {
		return $this->_notificationDao->updateNotification($msg);
	}
	function getSession($sessionid, $userid) {
		return $this->_sessionDao->getSession($sessionid, $userid);
	}
	function addSession($session) {
		return $this->_sessionDao->addSession($session);
	}
	function deleteSession($sessionid) {
		return $this->_sessionDao->deleteSession($sessionid);
	}
	function deleteAllUserSessions($userid) {
		return $this->_sessionDao->deleteAllUserSessions($userid);
	}
	function deleteExpiredSessions($maxLifeTime) {
		return $this->_sessionDao->deleteExpiredSessions($maxLifeTime);
	}
	function hitSession($sessionid) {
		return $this->_sessionDao->hitSession($sessionid);
	}
	public function getAllSettings() {
		return $this->_settingDao->getAllSettings();
	}
	public function removeSetting($name) {
		return $this->_settingDao->removeSetting($name);
	}
	public function updateSetting($name, $value) {
		return $this->_settingDao->updateSetting($name, $value);
	}
	public function getSchemaVer() {
		return $this->_settingDao->getSchemaVer();
	}
	function removeExtraReports($messageId) {
		return $this->_spotReportDao->removeExtraReports($messageId);
	}
	function matchReportMessageIds($hdrList) {
		return $this->_spotReportDao->matchReportMessageIds($hdrList);
	}
	function addReportRefs($reportList) {
		return $this->_spotReportDao->addReportRefs($reportList);
	}

} # class db
