<?php

interface Dao_Cache {
	const SpotImage			= 1;
	const SpotNzb			= 2;
	const Web				= 3;
	const Statistics		= 4;
    const TranslaterToken   = 5;
    const TranslatedComments= 6;

	function expireCache($expireDays);

    function getMassCacheRecords($resourceIdList);

	function getCachedNzb($resourceId);
    function hasCachedNzb($resourceId);
	function updateNzbCacheStamp($resourceId);
	function saveNzbCache($resourceId, $content, $performExpire);

	function getCachedHttp($resourceId);
    function hasCachedHttp($resourceId);
	function updateHttpCacheStamp($resourceId);
	function saveHttpCache($resourceId, $content);

	function getCachedSpotImage($resourceId);
    function hasCachedSpotImage($resourceId);
	function updateSpotImageCacheStamp($resourceId, $metadata);
	function saveSpotImageCache($resourceId, $metadata, $content, $performExpire);

	function getCachedStats($resourceId);
    function hasCachedStats($resourceId);
	function updateStatsCacheStamp($resourceId);
	function saveStatsCache($resourceId, $content);

    function getCachedTranslaterToken($resourceId);
    function saveTranslaterTokenCache($resourceId, $expireTime, $content);

    function getCachedTranslatedComments($resourceId, $language);
    function saveTranslatedCommentCache($resourceId, $language, $content);
} # Dao_Cache