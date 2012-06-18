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
		return $this->_conn->modify("DELETE FROM cache WHERE (cachetype = %d OR cachetype = %d OR cachetype = %d) AND stamp < %d", Array(SpotCache::Web, SpotCache::Statistics, SpotCache::StatisticsData,(int) time()-$expireDays*24*60*60));
	} # expireCache

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

		$this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', serialized = '%s', content = '%s' WHERE resourceid = '%s' AND cachetype = '%s'", 
									Array(time(), $metadata, $this->_conn->bool2dt($serialize), $content, $resourceid, $cachetype));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata,serialized,content) VALUES ('%s', '%s', %d, '%s', '%s', '%s')", 
									Array($resourceid, $cachetype, time(), $metadata, $this->_conn->bool2dt($serialize), $content));
		} # if
	} # saveCache
	

} # Dao_Base_Cache