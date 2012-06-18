<?php

class Dao_Mysql_Cache extends Dao_Base_Cache { 
	private $_maxPacketSize = null;

	/*
	 * MySQL has a maximum size we can send to the server, we query
	 * this because if we send anything larger, it will error out
	 */
	private function getMaxPacketSize() {
		if ($this->_maxPacketSize == null) {
			$packet = $this->_conn->arrayQuery("SHOW VARIABLES LIKE 'max_allowed_packet'"); 
			$this->_maxPacketSize = $packet[0]['Value'];
		} # if
		
		return $this->_maxPacketSize;
	} # getMaxPacketSize

	/*
	 * Returns the resource from the cache table, if we have any
	 */
	function getCache($resourceid, $cachetype) {
		$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata, serialized, UNCOMPRESS(content) AS content FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", array($resourceid, $cachetype));

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

		$this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', serialized = '%s', content = COMPRESS('%s') WHERE resourceid = '%s' AND cachetype = '%s'", 
								Array(time(), $metadata, $this->_conn->bool2dt($serialize), $content, $resourceid, $cachetype));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata,serialized,content) VALUES ('%s', '%s', %d, '%s', '%s', COMPRESS('%s'))", 
								Array($resourceid, $cachetype, time(), $metadata, $this->_conn->bool2dt($serialize), $content));
		} # if
	} # saveCache

} # Dao_Mysql_Cache

