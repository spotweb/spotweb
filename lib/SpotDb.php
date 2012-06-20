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
	private $_userFilterCountDao;
	private $_userFilterDao;
	private $_userDao;
	private $_spotDao;
	private $_spotStateListDao;
	private $_nntpDao;

	private $_dbsettings = null;
	private $_conn = null;

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
		$this->_userFilterCountDao = $daoFactory->getUserFilterCountDao();
		$this->_userFilterDao = $daoFactory->getUserFilterDao();
		$this->_userDao = $daoFactory->getUserDao();
		$this->_spotDao = $daoFactory->getSpotdao();
		$this->_spotStateListDao = $daoFactory->getSpotStateListDao();
		$this->_nntpDao = $daoFactory->getNntpDao();

		$this->_conn->connect();
		SpotTiming::stop(__FUNCTION__);
	} # connect

	/*
	 * Geeft het database connectie object terug
	 */
	function getDbHandle() {
		return $this->_conn;
	} # getDbHandle


	function safe($x) {
		return $this->_conn->safe($x);
	}

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
	function setCachedFilterCount($userId, $filterHashes) {
		return $this->_userFilterCountDao->setCachedFilterCount($userId, $filterHashes);
	}
	function getNewCountForFilters($userId) {
		return $this->_userFilterCountDao->getNewCountForFilters($userId);
	}
	function createFilterCountsForEveryone() {
		return $this->_userFilterCountDao->createFilterCountsForEveryone();
	}
	function getCachedFilterCount($userId) {
		return $this->_userFilterCountDao->getCachedFilterCount($userId);
	}
	function resetFilterCountForUser($userId) {
		return $this->_userFilterCountDao->resetFilterCountForUser($userId);
	}
	function updateCurrentFilterCounts() {
		return $this->_userFilterCountDao->updateCurrentFilterCounts();
	}
	function markFilterCountAsSeen($userId) {
		return $this->_userFilterCountDao->markFilterCountAsSeen($userId);
	}
	function deleteFilter($userId, $filterId, $filterType) {
		return $this->_userFilterDao->deleteFilter($userId, $filterId, $filterType);
	}
	function addFilter($userId, $filter) {
		return $this->_userFilterDao->addFilter($userId, $filter);
	}
	function copyFilterList($srcId, $dstId) {
		return $this->_userFilterDao->copyFilterList($srcId, $dstId);
	}
	function removeAllFilters($userId) {
		return $this->_userFilterDao->removeAllFilters($userId);
	}
	function getFilter($userId, $filterId) {
		return $this->_userFilterDao->getFilter($userId, $filterId);
	}
	function getUserIndexFilter($userId) {
		return $this->_userFilterDao->getUserIndexFilter($userId);
	}
	function updateFilter($userId, $filter) {
		return $this->_userFilterDao->updateFilter($userId, $filter);
	}	
	function getPlainFilterList($userId, $filterType) {
		return $this->_userFilterDao->getPlainFilterList($userId, $filterType);
	}
	function getFilterList($userId, $filterType) {
		return $this->_userFilterDao->getFilterList($userId, $filterType);
	}
	function getUniqueFilterCombinations() {
		return $this->_userFilterDao->getUniqueFilterCombinations();
	}
	function getUsersForFilter($tree, $valuelist) {
		return $this->_userFilterDao->getUsersForFilter($tree, $valuelist);
	}
	function findUserIdForName($username) {
		return $this->_userDao->findUserIdForName($username);
	}
	function userEmailExists($mail) {
		return $this->_userDao->userEmailExists($mail);
	}
	function getUser($userid) {
		return $this->_userDao->getUser($userid);
	}
	function getUserList() {
		return $this->_userDao->getUserList();
	}
	function getUserListForDisplay() {
		return $this->_userDao->getUserListForDisplay();
	}
	function deleteUser($userid) {
		return $this->_userDao->deleteUser($userid);
	}
	function setUser($user) {
		return $this->_userDao->setUser($user);
	}
	function setUserPassword($user) {
		return $this->_userDao->setUserPassword($user);
	}
	function setUserRsaKeys($userId, $publicKey, $privateKey) {
		return $this->_userDao->setUserRsaKeys($userId, $publicKey, $privateKey);
	}
	function getUserPrivateRsaKey($userId) {
		return $this->_userDao->getUserPrivateRsaKey($userId);
	}
	function addUser($user) {
		return $this->_userDao->addUser($user);
	}
	function authUser($username, $passhash) {
		return $this->_userDao->authUser($username, $passhash);
	}
	function setUserAvatar($userId, $imageEncoded) {
		return $this->_userDao->setUserAvatar($userId, $imageEncoded);
	}
	function getGroupPerms($groupId) {
		return $this->_userDao->getGroupPerms($groupId);
	}
	function getPermissions($userId) {
		return $this->_userDao->getPermissions($userId);
	}
	function getGroupList($userId) {
		return $this->_userDao->getGroupList($userId);
	}
	function removePermFromSecGroup($groupId, $perm) {
		return $this->_userDao->removePermFromSecGroup($groupId, $perm);
	}
	function setDenyForPermFromSecGroup($groupId, $perm) {
		return $this->_userDao->setDenyForPermFromSecGroup($groupId, $perm);
	}
	function addPermToSecGroup($groupId, $perm) {
		return $this->_userDao->addPermToSecGroup($groupId, $perm);
	}
	function getSecurityGroup($groupId) {
		return $this->_userDao->getSecurityGroup($groupId);
	}
	function setSecurityGroup($group) {
		return $this->_userDao->setSecurityGroup($group);
	}
	function addSecurityGroup($group) {
		return $this->_userDao->addSecurityGroup($group);
	}
	function removeSecurityGroup($group) {
		return $this->_userDao->removeSecurityGroup($group);
	}
	function setUserGroupList($userId, $groupList) {
		return $this->_userDao->setUserGroupList($userId, $groupList);
	}
	function isReportPlaced($messageid, $userId) {
		return $this->_spotReportDao->isReportPlaced($messageid, $userId);
	}
	function isReportMessageIdUnique($messageid) {
		return $this->_spotReportDao->isReportMessageIdUnique($messageid);
	}
	function addPostedReport($userId, $report) {
		return $this->_spotReportDao->addPostedReport($userId, $report);
	}
	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch) {
		return $this->_spotDao->getSpots($ourUserId, $pageNr, $limit, $parsedSearch);
	}
	function getSpotHeader($msgId) {
		return $this->_spotDao->getSpotHeader($msgId);
	}
	function getFullSpot($messageId, $ourUserId) {
		return $this->_spotDao->getFullSpot($messageId, $ourUserId);
	}
	function updateSpotRating($spotMsgIdList) {
		return $this->_spotDao->updateSpotRating($spotMsgIdList);
	}
	function updateSpotCommentCount($spotMsgIdList) {
		return $this->_spotDao->updateSpotCommentCount($spotMsgIdList);
	}
	function updateSpotReportCount($spotMsgIdList) {
		return $this->_spotDao->updateSpotReportCount($spotMsgIdList);
	}
	function removeSpots($spotMsgIdList) {
		return $this->_spotDao->removeSpots($spotMsgIdList);
	}
	function markSpotsModerated($spotMsgIdList) {
		return $this->_spotDao->markSpotsModerated($spotMsgIdList);
	}
	function deleteSpotsRetention($retention) {
		return $this->_spotDao->deleteSpotsRetention($retention);
	}
	function addSpots($spots, $fullSpots = array()) {
		return $this->_spotDao->addSpots($spots, $fullSpots);
	}
	function updateSpotInfoFromFull($fullSpot) {
		return $this->_spotDao->updateSpotInfoFromFull($fullSpot);
	}
	function addFullSpots($fullSpots) {
		return $this->_spotDao->addFullSpots($fullSpots);
	}
	function getOldestSpotTimestamp() {
		return $this->_spotDao->getOldestSpotTimestamp();
	}
	function matchSpotMessageIds($hdrList) {
		return $this->_spotDao->matchSpotMessageIds($hdrList);
	}
	function getSpotCount($sqlFilter) {
		return $this->_spotDao->getSpotCount($sqlFilter);
	}
	function getSpotCountPerHour($limit) {
		return $this->_spotDao->getSpotCountPerHour($limit);
	}
	function getSpotCountPerWeekday($limit) {
		return $this->_spotDao->getSpotCountPerWeekday($limit);
	}
	function getSpotCountPerMonth($limit) {
		return $this->_spotDao->getSpotCountPerMonth($limit);
	}
	function getSpotCountPerCategory($limit) {
		return $this->_spotDao->getSpotCountPerCategory($limit);
	}
	function removeExtraSpots($messageId) {
		return $this->_spotDao->removeExtraSpots($messageId);
	}
	function addPostedSpot($userId, $spot, $fullXml) {
		return $this->_spotDao->addPostedSpot($userId, $spot, $fullXml);
	}
	function expireSpotsFull($expireDays) {
		return $this->_spotDao->expireSpotsFull($expireDays);
	}
	function markAllAsRead($ourUserId) {
		return $this->_spotStateListDao->markAllAsRead($ourUserId);
	}
	function clearDownloadList($ourUserId) {
		return $this->_spotStateListDao->clearDownloadList($ourUserId);
	}
	function cleanSpotStateList() {
		return $this->_spotStateListDao->cleanSpotStateList();
	}
	function removeFromWatchList($messageid, $ourUserId) {
		return $this->_spotStateListDao->removeFromWatchList($messageid, $ourUserId);
	}
	function addToWatchList($messageid, $ourUserId) {
		return $this->_spotStateListDao->addToWatchList($messageid, $ourUserId);
	}
	function addToSeenList($messageid, $ourUserId) {
		return $this->_spotStateListDao->addToSeenList($messageid, $ourUserId);
	}
	function addToDownloadList($messageid, $ourUserId) {
		return $this->_spotStateListDao->addToDownloadList($messageid, $ourUserId);
	}
	function isNewSpotMessageIdUnique($messageid) {
		return $this->_spotDao->isNewSpotMessageIdUnique($messageid);
	}
	function getMaxMessageTime() {
		return $this->_spotDao->getMaxMessageTime();
	}
	function getMaxMessageId($headers) {
		return $this->_spotDao->getMaxMessageId($headers);
	}
	function setMaxArticleId($server, $maxarticleid) {
		return $this->_nntpDao->setMaxArticleId($server, $maxarticleid);
	}
	function getMaxArticleId($server) {
		return $this->_nntpDao->getMaxArticleId($server);
	}
	function isRetrieverRunning($server) {
		return $this->_nntpDao->isRetrieverRunning($server);
	}
	function setRetrieverRunning($server, $isRunning) {
		return $this->_nntpDao->setRetrieverRunning($server, $isRunning);
	}
	function setLastUpdate($server) {
		return $this->_nntpDao->setLastUpdate($server);
	}
	function getLastUpdate($server) {
		return $this->_nntpDao->getLastUpdate($server);
	}
} # class db
