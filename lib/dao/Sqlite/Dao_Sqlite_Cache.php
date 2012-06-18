<?php

class Dao_Sqlite_Cache extends Dao_Base_Cache { 

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
	

} # Dao_Sqlite_Cache