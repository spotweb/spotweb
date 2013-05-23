<?php

class Services_Actions_SpotStateList {
	private $_spotStateListDao;

	/*
	 * constructor
	 */
	public function __construct(Dao_SpotStateList $spotStateListDao) {
		$this->_spotStateListDao = $spotStateListDao;
	}  # ctor
	

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
	

} # Services_Actions_SpotStateList
