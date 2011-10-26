<?php

class SpotCache {
	protected $_db;
	
	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function getCache($url) {
		if ($data = $this->_db->getCache($url)) {
			return $data;
		} else {
			return false;
		} # else
	} # getCache

	function saveCache($messageid, $url, $headers, $content, $compress=false) {
		$this->_db->saveCache($messageid, $url, trim($headers), $content, $compress);
	} # saveCache

	function updateCacheStamp($url) {
		$this->_db->updateCacheStamp($url);
	}
	
} # class
