<?php

class Dao_Base_Cache implements Dao_Cache {
    private     $_cachePath     = '';
    protected   $_conn;

	/*
	 * constructs a new Dao_Base_Cache object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn, $cachePath) {
		$this->_conn = $conn;
        $this->_cachePath = $cachePath;

        if (empty($this->_cachePath)) {
            throw new NotImplementedException("Cache path is null?");
        } # if
	} # ctor
	
	/*
	 * Removes items from the cache older than a specific amount of days
	 */
	function expireCache($expireDays) {
        /*
         * Calculate the filepath so we can remove the file from disk, we
         * ignore any error which might be thrown because we cannot do
         * anything about it anyway.
         */
        $expiredList = $this->_conn->arrayQuery("SELECT resourceid, cachetype, metadata FROM cache WHERE stamp < %d OR ((ttl + stamp) < %d)",
                    Array((int) time() - $expireDays*24*60*60,
                          (int) time()));

        foreach($expiredList as $cacheItem) {
            if (!empty($cacheItem)) {
                $cacheItem['metadata'] = unserialize($cacheItem['metadata']);
            } # if

            /*
             * Always remove the item from the database and filesystem, this way if the on-disk
             * deletion fails, we won't keep trying it.
             */
            $this->removeCacheItem($cacheItem['resourceid'],$cacheItem['cachetype'], $cacheItem['metadata']);
        } # cacheItem
	} # expireCache

	/*
	 * Retrieves wether a specific resourceid is cached
	 */
	protected function isCached($resourceid, $cachetype) {
		$tmpResult = $this->_conn->arrayQuery("SELECT 1 FROM cache WHERE resourceid = '%s' AND cachetype = '%s' AND (ttl + stamp) < %d",
                    Array($resourceid, $cachetype, time()));

		return (!empty($tmpResult));
	} # isCached

    /*
     * Removes an item from the cache
     */
    protected function removeCacheItem($resourceId, $cachetype, $metaData) {
        $this->_conn->exec("DELETE FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", Array($resourceId, $cachetype));

        /*
         * Remove the item from disk and ignore any errors
         */
        $filePath = $this->calculateFilePath($resourceId, $cachetype, $metaData);
        @unlink($filePath);
    } # removeCacheItem

    /*
     * Calculates the exact filepath given a storageid
     */
    protected function calculateFilePath($resourceId, $cacheType, $metadata) {
        $filePath = $this->_cachePath . DIRECTORY_SEPARATOR;

        switch ($cacheType) {
            case Dao_Cache::SpotImage           : $filePath .= 'image' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::SpotNzb             : $filePath .= 'nzb' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::Statistics          : $filePath .= 'stats' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::Web                 : $filePath .= 'web' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::TranslaterToken     : $filePath .= 'translatertoken' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::TranslatedComments  : $filePath .= 'translatedcomments' . DIRECTORY_SEPARATOR; break;

            default                         : throw new NotImplementedException("Undefined Cachetype: " . $cacheType);
        } # switch

        /*
         * We calculate SHA1 of it, to make sure we only use
         * filesystem save and unique characters in the filename,
         * a bit useless because the resourceId is already unique for
         * the given storage type
         */
        $storageId = sha1($resourceId);
        for($i = 0; $i < (strlen($storageId) - 4); $i += 3) {
            $filePath .= substr($storageId, $i, 3) . DIRECTORY_SEPARATOR;
        } # for
        $filePath .= substr($storageId, strlen($storageId) - 4);

        /*
         * And create an extension, because thats nicer.
         */
        if ($cacheType == Dao_Cache::SpotImage) {
            switch($metadata['imagetype']) {
                case IMAGETYPE_GIF          : $filePath .= '.gif'; break;
                case IMAGETYPE_JPEG         : $filePath .= '.jpg'; break;
                case IMAGETYPE_PNG          : $filePath .= '.png'; break;

                default                     : $filePath .= '.image.' . $metadata['imagetype']; break;
            } # switch
        } else {
            switch ($cacheType) {
                case Dao_Cache::SpotNzb             : $filePath .= '.nzb'; break;
                case Dao_Cache::Statistics          : $filePath .= '.stats'; break;
                case Dao_Cache::Web                 : $filePath .= '.http'; break;
                case Dao_Cache::TranslaterToken     : $filePath .= '.token'; break;
                case Dao_Cache::TranslatedComments  : $filePath .= '.translatedcomments'; break;

                default                         : throw new NotImplementedException("Undefined Cachetype: " . $cacheType);
            } # switch
        } # else

        return $filePath;
    } # calculateFilePath

    /*
     * Returns the actual contents for a given resourceid
     */
    protected function getCacheContent($resourceId, $cacheType, $metaData) {
        /*
         * Get the unique filepath
         */
        $filePath = $this->calculateFilePath($resourceId, $cacheType, $metaData);

        if (!file_exists($filePath)) {
            $this->removeCacheItem($resourceId, $cacheType, $metaData);

            throw new CacheIsCorruptException('Cache is corrupt, could not find on-disk resource for: '. $resourceId);
        } # if

        return file_get_contents($filePath);
    } # getCacheContent

    /*
     * Stores the actual contenst for a given resourceid
     */
    public function putCacheContent($resourceId, $cacheType, $content, $metaData) {
        /*
           * Get the unique filepath
           */
        $filePath = $this->calculateFilePath($resourceId, $cacheType, $metaData);

        /*
         * Create the directory
         */
        $success = true;
        if (!is_writable(dirname($filePath))) {
            $success = @mkdir(dirname($filePath), 0777, true);
        } # if

        if ($success) {
            $success = (file_put_contents($filePath, $content) === strlen($content));

            if ($success) {
                @chmod($filePath, 0777);
            } # if
        } # if

        if (!$success) {
            /*
             * Gather some diagnostics information to allow the operator to
             * troubleshoot this easier.
             */
            $filePerms = fileperms(dirname($filePath));
            $fileOwner = fileowner(dirname($filePath));
            $fileGroup = filegroup(dirname($filePath));
            $phpUser = get_current_user(); // appears to work for windows


            if (function_exists('posix_getpwuid')) {
                $fileGroup = posix_getgrgid($fileGroup);
                $fileGroup = $fileGroup['name'];

                $fileOwner = posix_getpwuid($fileOwner);
                $fileOwner = $fileOwner['name'];

                $phpUser = posix_getpwuid(posix_geteuid());
                $phpUser = $phpUser['name'];
            } # if

            error_log('Unable to write to cache directory (' . $filePath . '), ' .
                            ' owner=' . $fileOwner . ', ' .
                            ' group=' . $fileGroup . ', ' .
                            ' thisUser=' . $phpUser . ', ' .
                            ' perms= ' . substr(decoct($filePerms), 2) );
        } # if

        return $success;
    } # putCacheContent

	/*
	 * Returns the resource from the cache table, if we have any
	 */
	protected function getCache($resourceid, $cachetype) {
		$tmp = $this->_conn->arrayQuery("SELECT stamp, ttl, metadata FROM cache WHERE resourceid = '%s' AND cachetype = '%s'",
                        array($resourceid, $cachetype));

		if (!empty($tmp)) {
            /*
             * Make sure the entry is not expired
             */
            if ($tmp[0]['ttl'] > 0) {
                if (($tmp[0]['stamp'] + $tmp[0]['ttl']) < time()) {
                    $this->removeCacheItem($resourceid, $cachetype, unserialize($tmp[0]['metadata']));

                    return false;
                } # if
            } # if

            $tmp[0]['metadata'] = unserialize($tmp[0]['metadata']);
            $tmp[0]['content'] = $this->getCacheContent($resourceid, $cachetype, $tmp[0]['metadata']);
			return $tmp[0];
		} # if

        // echo 'Cache miss for resourceid: ' . $resourceid . PHP_EOL;

		return false;
	} # getCache

	/*
	 * Add a resource to the cache
	 */
	protected function saveCache($resourceid, $cachetype, $metadata, $ttl, $content) {
        if ($metadata) {
            $serializedMetadata = serialize($metadata);
        } else {
            $serializedMetadata = false;
        } # else

        $this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s', ttl = %d WHERE resourceid = '%s' AND cachetype = '%s'",
            Array(time(), $serializedMetadata, $ttl, $resourceid, $cachetype));

        if ($this->_conn->rows() == 0) {
            $this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,ttl,metadata) VALUES ('%s', '%s', %d, %d, '%s')",
                Array($resourceid, $cachetype, time(), $ttl, $serializedMetadata));
        } # if

        /*
         * Actually store the contents on disk
         */
        if (!$this->putCacheContent($resourceid, $cachetype, $content, $metadata)) {
            /*
             * If we couldn't store the cache result, we have to actually remove the
             * cache record again
             */
            $this->_conn->exec("DELETE FROM cache WHERE resourceid = '%s' AND cachetype = '%s'",
                                        Array($resourceid, $cachetype));

            return false;
        } else {
            return true;
        }
	} # saveCache

	/*
	 * Refreshen the cache timestamp to prevent it from being stale
	 */
	protected function updateCacheStamp($resourceid, $cachetype) {
        /*
         * We do not want to update the cache timestamp of items where
         * expiration is set as this could extend the lifetime of those items
         */
		$this->_conn->exec("UPDATE cache SET stamp = %d WHERE resourceid = '%s' AND cachetype = '%s' AND ttl = 0", Array(time(), $resourceid, $cachetype));
	} # updateCacheStamp

	/*
	 * Retrieve a NZB from the cache
	 */
	function getCachedNzb($resourceId) {
		return $this->getCache($resourceId, $this::SpotNzb);
	} # getCachedNzb

    /*
     * Check if we have a NZB from the cache
     */
    function hasCachedNzb($resourceId) {
        return $this->isCached($resourceId, $this::SpotNzb);
    } # hasCachedNzb

    /*
     * Update an NZB file from the cache
     */
	function updateNzbCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::SpotNzb);
	} # updateNzbCacheStamp

	/*
	 * Save an NZB file into the cache
	 */
	function saveNzbCache($resourceId, $content, $performExpire) {
        if ($performExpire) {
            $ttl = 7 * 24 * 60 * 60;
        } else {
            $ttl = 0;
        } # else

		return $this->saveCache($resourceId, $this::SpotNzb, false, $ttl, $content);
	} # saveNzbCache

	/*
	 * Retrieve a HTTP resource from the cache
	 */
	function getCachedHttp($resourceId) {
		return $this->getCache($resourceId, $this::Web);
	} # getCachedHttp

    /*
     * Check if we have a HTTP resource from the cache
     */
    function hasCachedHttp($resourceId) {
        return $this->isCached($resourceId, $this::Web);
    } # hasCachedHttp

    /*
     * Update an HTTP resource from the cache
     */
	function updateHttpCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::Web);
	} # updateHttpCacheStamp

	/*
	 * Save an HTTP resource into the cache
	 */
	function saveHttpcache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::Web, false, 0, $content);
	} # saveHttpcache

	/*
	 * Retrieve a image resource from the cache
	 */
	function getCachedSpotImage($resourceId) {
		return $this->getCache($resourceId, $this::SpotImage);
	} # getCachedSpotImage

    /*
     * Check if we have an image resource from the cache
     */
    function hasCachedSpotImage($resourceId) {
        return $this->isCached($resourceId, $this::SpotImage);
    } # getCachedSpotImage

    /*
     * Update an image resource from the cache
     */
	function updateSpotImageCacheStamp($resourceId, $metadata) {
        return $this->updateCacheStamp($resourceId, $this::SpotImage);
	} # updateSpotImageCacheStamp

	/*
	 * Save an image resource into the cache
	 */
	function saveSpotImageCache($resourceId, $metadata, $content, $performExpire) {
        if ($performExpire) {
            $ttl = 7 * 24 * 60 * 60;
        } else {
            $ttl = 0;
        } # else

		return $this->saveCache($resourceId, $this::SpotImage, $metadata, $ttl, $content);
	} # saveSpotImagecache

	/*
	 * Retrieve a statistics count from the cache
	 */
	function getCachedStats($resourceId) {
        $tmpStats = $this->getCache($resourceId, $this::Statistics);
        if ($tmpStats !== false) {
            $tmpStats['content'] = $tmpStats['content'];
            return $tmpStats;
        } else {
            return false;
        } # if
	} # getCachedStats

    /*
     * Checks if we have statistics count from the cache
     */
    function hasCachedStats($resourceId) {
        return $this->isCached($resourceId, $this::Statistics);
    } # hasCachedStats

	/*
	 * Update an statistics resource from the cache
	 */
	function updateStatsCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::Statistics);
	} # updateStatsCacheStamp

	/*
	 * Save an statistics resource into the cache
	 */
	function saveStatsCache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::Statistics, false, 0, serialize($content));
	} # saveStatsCache

    /*
     * Returns an translater token from the cache
     */
    function getCachedTranslaterToken($resourceId) {
        $tmpCache = $this->getCache($resourceId, $this::TranslaterToken);

        if ($tmpCache === false) {
            return false;
        } # iuf

        /*
         * Make sure we don't return an translator token if its expired
         */
        if ($tmpCache['metadata'] < time()) {
            return false;
        } # if

        return $tmpCache;
    } # getCachedTranslaterToken

    /*
     * Saves an translater token into the cache
     */
    function saveTranslaterTokenCache($resourceId, $expireTime, $content) {
        return $this->saveCache($resourceId, $this::TranslaterToken, $expireTime, 0,$content);
    } # saveTranslaterTokenCache

    /*
    * Retrieve a translated comment resource from the cache
     */
    function getCachedTranslatedComments($resourceId, $language) {
        $tmpTranslations = $this->getCache($resourceId . '_' . $language, $this::TranslatedComments);
        if ($tmpTranslations !== false) {
            return unserialize($tmpTranslations['content']);
        } else {
            return false;
        } # if
    } # getCachedTranslatedComments

    /*
     * Save an translated comment resource into the cache
     */
    function saveTranslatedCommentCache($resourceId, $language, $translations) {
        return $this->saveCache($resourceId . '_' . $language,
                                $this::TranslatedComments,
                                false,
                                0,
                                serialize($translations));
    } # saveTranslatedCommentCache

    /**
     * Returns an array with per resourceid whether is
     * has a valid cache record. This can significantly
     * reduce the amount of queries we fire during retrieve
     *
     * @param $resourceIdList
     * @return array
     */
    function getMassCacheRecords($resourceIdList) {
        if (count($resourceIdList) < 1) {
            return array();
        } # if

        # Prepare a list of values
        $idList = array();
        $msgIdList = $this->_conn->arrayValToInOffset($resourceIdList, 'Message-ID', 1, -1);

        $rs = $this->_conn->arrayQuery("SELECT resourceid, cachetype
                                            FROM cache
                                            WHERE resourceid IN (" . $msgIdList . ")
                                            AND (ttl + stamp) < %d",
                                array(time()));

        foreach($rs as $msgids) {
            $idList[$msgids['cachetype']][$msgids['resourceid']] = 1;
        } # foreach

        return $idList;
    } # getMassCacheRecords

} # Dao_Base_Cache

