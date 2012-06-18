<?php

interface Dao_Cache {

	function expireCache($expireDays);
	function isCached($resourceid, $cachetype);
	function getCache($resourceid, $cachetype);
	function updateCacheStamp($resourceid, $cachetype);
	function saveCache($resourceid, $cachetype, $metadata, $content);

} # Dao_Cache