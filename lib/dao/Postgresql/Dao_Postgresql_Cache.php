<?php

class Dao_Postgresql_Cache extends Dao_Base_Cache { 

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

		$this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', serialized = '%s', content = '%b' WHERE resourceid = '%s' AND cachetype = '%s'", 
									Array(time(), $metadata, $this->_conn->bool2dt($serialize), $content, $resourceid, $cachetype));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata,serialized,content) VALUES ('%s', '%s', %d, '%s', '%s', '%b')", 
									Array($resourceid, $cachetype, time(), $metadata, $this->_conn->bool2dt($serialize), $content));
		} # if
	} # saveCache
	

	/*
	 * Returns the resource from the cache table, if we have any
	 */
	function getCache($resourceid, $cachetype) {
		$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata, serialized, content FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", array($resourceid, $cachetype));
		if (!empty($tmp)) {
			$tmp[0]['content'] = stream_get_contents($tmp[0]['content']);
		} # if

		if (!empty($tmp)) {
			if ($tmp[0]['serialized'] == 1) {
				$tmp[0]['content'] = unserialize($tmp[0]['content']);
			} # if

			$tmp[0]['metadata'] = unserialize($tmp[0]['metadata']);
			return $tmp[0];
		} # if

		return false;
	} # getCache
	
} # Dao_Postgresql_Cache
