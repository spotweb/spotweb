<?php

class Dao_Postgresql_Cache extends Dao_Base_Cache { 

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
