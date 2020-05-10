<?php

interface Dao_Cache
{
    const SpotImage = 1;
    const SpotNzb = 2;
    const Web = 3;
    const Statistics = 4;
    const TranslaterToken = 5;
    const TranslatedComments = 6;

    public function expireCache($expireDays);

    public function getMassCacheRecords($resourceIdList);

    public function getCachedNzb($resourceId);

    public function hasCachedNzb($resourceId);

    public function updateNzbCacheStamp($resourceId);

    public function saveNzbCache($resourceId, $content, $performExpire);

    public function getCachedHttp($resourceId);

    public function hasCachedHttp($resourceId);

    public function updateHttpCacheStamp($resourceId);

    public function saveHttpCache($resourceId, $content);

    public function getCachedSpotImage($resourceId);

    public function hasCachedSpotImage($resourceId);

    public function updateSpotImageCacheStamp($resourceId, $metadata);

    public function saveSpotImageCache($resourceId, $metadata, $content, $performExpire);

    public function getCachedStats($resourceId);

    public function hasCachedStats($resourceId);

    public function updateStatsCacheStamp($resourceId);

    public function saveStatsCache($resourceId, $content);

    public function getCachedTranslaterToken($resourceId);

    public function saveTranslaterTokenCache($resourceId, $expireTime, $content);

    public function getCachedTranslatedComments($resourceId, $language);

    public function saveTranslatedCommentCache($resourceId, $language, $content);
} // Dao_Cache
