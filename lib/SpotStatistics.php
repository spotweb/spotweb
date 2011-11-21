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
		$rs = $this->_cache->getCache($resourceid, SpotCache::StatisticsData);
		if (!$rs || (int) $rs['stamp'] < $lastUpdate) {
			$data = $this->_db->getSpotCountPerHour($limit);
			$this->_cache->saveCache($resourceid, SpotCache::StatisticsData, '', $data);
		} else {
			$data = $rs['content'];
		} # else

		return $data;
	} # getSpotCountPerHour

	function getSpotCountPerWeekday($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotsperweekday', $limit);
		$rs = $this->_cache->getCache($resourceid, SpotCache::StatisticsData);
		if (!$rs || (int) $rs['stamp'] < $lastUpdate) {
			$data = $this->_db->getSpotCountPerWeekday($limit);
			$this->_cache->saveCache($resourceid, SpotCache::StatisticsData, '', $data);
		} else {
			$data = $rs['content'];
		} # else

		return $data;
	} # getSpotCountPerWeekday

	function getSpotCountPerMonth($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotspermonth', $limit);
		$rs = $this->_cache->getCache($resourceid, SpotCache::StatisticsData);
		if (!$rs || (int) $rs['stamp'] < $lastUpdate) {
			$data = $this->_db->getSpotCountPerMonth($limit);
			$this->_cache->saveCache($resourceid, SpotCache::StatisticsData, '', $data);
		} else {
			$data = $rs['content'];
		} # else

		return $data;
	} # getSpotCountPerMonth

	function getSpotCountPerCategory($limit, $lastUpdate) {
		$resourceid = $this->getResourceid('spotspercategory', $limit);
		$rs = $this->_cache->getCache($resourceid, SpotCache::StatisticsData);
		if (!$rs || (int) $rs['stamp'] < $lastUpdate) {
			$data = $this->_db->getSpotCountPerCategory($limit);
			$this->_cache->saveCache($resourceid, SpotCache::StatisticsData, '', $data);
		} else {
			$data = $rs['content'];
		} # else

		return $data;
	} # getSpotCountPerCategory

	function getResourceid($name, $limit, $language=false) {
		if ($limit == '') {
			$resourceid = $name . '.all';
		} else {
			$resourceid = $name . '.' . $limit;
		} # else

		if ($language) {
			$resourceid .= '.' . $language;
		} # if

		return $resourceid;
	} # getResourceid

} # class SpotStatistics
