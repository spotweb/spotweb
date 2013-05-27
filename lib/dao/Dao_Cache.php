<?php

interface Dao_Cache {
	const SpotImage			= 1;
	const SpotNzb			= 2;
	const Web				= 3;
	const Statistics		= 4;

	function expireCache($expireDays);

	function getCachedNzb($resourceId);
    function hasCachedNzb($resourceId);
	function updateNzbCacheStamp($resourceId);
	function saveNzbCache($resourceId, $content);

	function getCachedHttp($resourceId);
    function hasCachedHttp($resourceId);
	function updateHttpCacheStamp($resourceId);
	function saveHttpCache($resourceId, $content);

	function getCachedSpotImage($resourceId);
    function hasCachedSpotImage($resourceId);
	function updateSpotImageCacheStamp($resourceId);
	function saveSpotImageCache($resourceId, $metadata, $content);

	function getCachedStats($resourceId);
    function hasCachedStats($resourceId);
	function updateStatsCacheStamp($resourceId);
	function saveStatsCache($resourceId, $content);

} # Dao_Cache