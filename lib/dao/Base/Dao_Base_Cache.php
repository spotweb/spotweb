<?php

class Dao_Base_Cache implements Dao_Cache {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Cache object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor
	
	/*
	 * Removes items from the cache older than a specific amount of days
	 */
	function expireCache($expireDays) {
		return $this->_conn->modify("DELETE FROM cache WHERE (cachetype = %d OR cachetype = %d OR cachetype = %d) AND stamp < %d", 
					Array($this::Web, $this::Statistics, $this::StatisticsData,(int) time()-$expireDays*24*60*60));
	} # expireCache

	/*
	 * Retrieves wether a specific resourceid is cached
	 */
	protected function isCached($resourceid, $cachetype) {
		$tmpResult = $this->_conn->singleQuery("SELECT resourceid FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", Array($resourceid, $cachetype));

		return (!empty($tmpResult));
	} # isCached

	/*
	 * Returns the resource from the cache table, if we have any
	 */
	protected function getCache($resourceid, $cachetype) {
		$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata, serialized, content FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", array($resourceid, $cachetype));
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
	 * Add a resource to the cache
	 */
	protected function saveCache($resourceid, $cachetype, $metadata, $content) {
		throw new NotImplementedException();
	} # saveCache

	/*
	 * Refreshen the cache timestamp to prevent it from being stale
	 */
	protected function updateCacheStamp($resourceid, $cachetype) {
		$this->_conn->exec("UPDATE cache SET stamp = %d WHERE resourceid = '%s' AND cachetype = '%s'", Array(time(), $resourceid, $cachetype));
	} # updateCacheStamp

	/*
	 * Retrieve a NZB from the cache
	 */
	function getCachedNzb($resourceId) {
		return $this->getCache($resourceId, $this::SpotNzb);
	} # getCachedNzb

	/*
	 * Update an NZB file from the cache
	 */
	function updateNzbCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::SpotNzb);
	} # updateNzbCacheStamp

	/*
	 * Save an NZB file into the cache
	 */
	function saveNzbCache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::SpotNzb, false, $content);
	} # saveNzbCache

	/*
	 * Retrieve a HTTP resource from the cache
	 */
	function getCachedHttp($resourceId) {
		return $this->getCache($resourceId, $this::Web);
	} # getCachedHttp

	/*
	 * Update an HTTP resource from the cache
	 */
	function updateHttpCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::Web);
	} # updateHttpCacheStamp

	/*
	 * Save an HTTP resource into the cache
	 */
	function saveHttpcache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::Web, false, $content);
	} # saveHttpcache

	/*
	 * Retrieve a image resource from the cache
	 */
	function getCachedSpotImage($resourceId) {
		return $this->getCache($resourceId, $this::SpotImage);
	} # getCachedSpotImage

	/*
	 * Update an image resource from the cache
	 */
	function updateSpotImageCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::SpotImage);
	} # updateSpotImageCacheStamp

	/*
	 * Save an image resource into the cache
	 */
	function saveSpotImagecache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::SpotImage, $content['metadata'], $content['content']);
	} # saveSpotImagecache

	/*
	 * Retrieve a statistics count from the cache
	 */
	function getCachedStats($resourceId) {
		return $this->getCache($resourceId, $this::Statistics);
	} # getCachedStats

	/*
	 * Update an HTTP resource from the cache
	 */
	function updateStatsCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::Statistics);
	} # updateStatsCacheStamp

	/*
	 * Save an HTTP resource into the cache
	 */
	function saveStatsCache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::Statistics, false, $content);
	} # saveStatsCache


} # Dao_Base_Cache
