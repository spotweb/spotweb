<?php

class SpotCache {
	protected $_db;
	
	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function getCache($url) {
		SpotTiming::start(__FUNCTION__);
		if ($data = $this->_db->getCache($url)) {
			SpotTiming::stop(__FUNCTION__, array($data));
			return $data;
		} else {
			SpotTiming::stop(__FUNCTION__, array(false));
			return false;
		} # else
	} # getCache

	function saveCache($messageid, $url, $headers, $content, $compress=false) {
		SpotTiming::start(__FUNCTION__);
		$this->_db->saveCache($messageid, $url, trim($headers), $content, $compress);
		SpotTiming::stop(__FUNCTION__, array($messageid, $url, $headers, $content, $compress));
	} # saveCache

	function updateCacheStamp($url, $headers) {
		SpotTiming::start(__FUNCTION__);
		$this->_db->updateCacheStamp($url, trim($headers));
		SpotTiming::stop(__FUNCTION__, array($url, $headers));
	} # updateCacheStamp
	
} # class SpotCache
