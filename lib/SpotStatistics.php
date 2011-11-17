<?php

class SpotStatistics {
	protected $_db;
	private $_cache;

	function __construct(SpotDb $db) {
		$this->_db = $db;
		$this->_cache = new SpotCache($db);
	} # ctor

	function getSpotCountPerHour($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotsperhour', $limit);
		return $this->getData($resourceid, $limit, $lastUpdate);
	} # getSpotCountPerHour

	function getSpotCountPerWeekday($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotsperweekday', $limit);
		return $this->getData($resourceid, $limit, $lastUpdate);
	} # getSpotCountPerWeekday

	function getSpotCountPerMonth($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotspermonth', $limit);
		return $this->getData($resourceid, $limit, $lastUpdate);
	} # getSpotCountPerMonth

	function getSpotCountPerCategory($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotspercategory', $limit);
		return $this->getData($resourceid, $limit, $lastUpdate);
	} # getSpotCountPerCategory

	function getData($resourceid, $limit, $lastUpdate) {
		$data = $this->_cache->getCache($resourceid, SpotCache::StatisticsData);
		if (!$data || (int) $data['stamp'] < $lastUpdate) {
			$data = $this->_db->getSpotCountPerHour($limit);
			$this->_cache->saveCache($resourceid, SpotCache::StatisticsData, '', serialize($data), true);
		} else {
			$data = unserialize($data['content']);
		} # else
		
		return $data;
	} # getDataFromCache

	function getResourceid($name, $limit) {
		if ($limit == '') {
			return $name . '.all';
		} else {
			return $name . '.' . $limit;
		} # else
	} # getResourceid

} # class SpotStatistics
