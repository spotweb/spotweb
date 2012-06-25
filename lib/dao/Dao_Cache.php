<?php

interface Dao_Cache {

	function expireCache($expireDays);
	function isCached($resourceid, $cachetype);
	function getCache($resourceid, $cachetype);
	function updateCacheStamp($resourceid, $cachetype);
	function saveCache($resourceid, $cachetype, $metadata, $content);

	function getCachedNzb($resourceId);
	function updateNzbCacheStamp($resourceId);
	function saveNzbCache($resourceId, $content);

	function getCachedHttp($resourceId);
	function updateHttpCacheStamp($resourceId);
	function saveHttpCache($resourceId, $content);

	function getCachedSpotImage($resourceId);
	function updateSpotImageCacheStamp($resourceId);
	function saveSpotImageCache($resourceId, $image);

} # Dao_Cache