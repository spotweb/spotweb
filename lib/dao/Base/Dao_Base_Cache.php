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
        $expiredList = $this->_conn->arrayQuery("SELECT resourceid, cachetype, metadata FROM cache WHERE stamp < %d",
                    Array((int) time() - $expireDays*24*60*60));

        foreach($expiredList as $cacheItem) {
            if (!empty($cacheItem)) {
                $cacheItem['metadata'] = unserialize($cacheItem['metadata']);
            } # if

            /*
             * Always remove the item from the database, this way if the on-disk
             * deletion fails, we won't keep trying it.
             */
            $this->removeCacheItem($cacheItem['resourceid'],$cacheItem['cachetype'], $cacheItem['metadata']);

            $filePath = $this->calculateFilePath($cacheItem['resourceid'],$cacheItem['cachetype'], $cacheItem['metadata']);
            if (@unlink($filePath) === false) {
                throw new CacheIsCorruptException('Cache is corrupt, could not found on-disk resource for: '. $cacheItem['resourceid']);
            } # if
        } # cacheItem
	} # expireCache

	/*
	 * Retrieves wether a specific resourceid is cached
	 */
	protected function isCached($resourceid, $cachetype) {
		$tmpResult = $this->_conn->singleQuery("SELECT 1 FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", Array($resourceid, $cachetype));

		return (!empty($tmpResult));
	} # isCached

    /*
     * Retrieves wether a specific resourceid is cached
    */
    protected function removeCacheItem($resourceId, $cachetype, $metaData) {
        $this->_conn->exec("DELETE FROM cache WHERE resourceid = '%s' AND cachetype = '%s'", Array($resourceId, $cachetype));
    } # removeCacheItem

    /*
     * Calculates the exact filepath given a storageid
     */
    protected function calculateFilePath($resourceId, $cacheType, $metadata) {
        $filePath = $this->_cachePath . DIRECTORY_SEPARATOR;

        switch ($cacheType) {
            case Dao_Cache::SpotImage       : $filePath .= 'image' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::SpotNzb         : $filePath .= 'nzb' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::Statistics      : $filePath .= 'stats' . DIRECTORY_SEPARATOR; break;
            case Dao_Cache::Web             : $filePath .= 'web' . DIRECTORY_SEPARATOR; break;

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
                case Dao_Cache::SpotNzb         : $filePath .= '.nzb'; break;
                case Dao_Cache::Statistics      : $filePath .= '.stats'; break;
                case Dao_Cache::Web             : $filePath .= '.http'; break;

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

            throw new CacheIsCorruptException('Cache is corrupt, could not found on-disk resource for: '. $resourceId);
        } # if

        return file_get_contents($filePath);
    } # getCacheContent

    /*
     * Stores the actual contenst for a given resourceid
     */
    private function putCacheContent($resourceId, $cacheType, $content, $metaData) {
        /*
           * Get the unique filepath
           */
        $filePath = $this->calculateFilePath($resourceId, $cacheType, $metaData);

        /*
         * Create the directory
         */
        $success = false;
        if (!is_writable(dirname($filePath))) {
            $success = @mkdir(dirname($filePath), 0777, true);
        } # if

        if ($success) {
            return (file_put_contents($filePath, $content) == strlen($content));
        } else {
            return false;
        } # else
    } # putCacheContent

	/*
	 * Returns the resource from the cache table, if we have any
	 */
	protected function getCache($resourceid, $cachetype) {
		$tmp = $this->_conn->arrayQuery("SELECT stamp, metadata FROM cache WHERE resourceid = '%s' AND cachetype = '%s'",
                        array($resourceid, $cachetype));

		if (!empty($tmp)) {
            $tmp[0]['metadata'] = unserialize($tmp[0]['metadata']);
            $tmp[0]['content'] = $this->getCacheContent($resourceid, $cachetype, $tmp[0]['metadata']);
			return $tmp[0];
		} # if

        echo 'Cache miss for resourceid: ' . $resourceid . PHP_EOL;

		return false;
	} # getCache

	/*
	 * Add a resource to the cache
	 */
	protected function saveCache($resourceid, $cachetype, $metadata, $content) {
        if ($metadata) {
            $serializedMetadata = serialize($metadata);
        } else {
            $serializedMetadata = false;
        } # else

        $this->_conn->exec("UPDATE cache SET stamp = %d, metadata = '%s' WHERE resourceid = '%s' AND cachetype = '%s'",
            Array(time(), $serializedMetadata, $resourceid, $cachetype));

        if ($this->_conn->rows() == 0) {
            $this->_conn->modify("INSERT INTO cache(resourceid,cachetype,stamp,metadata) VALUES ('%s', '%s', %d, '%s')",
                Array($resourceid, $cachetype, time(), $serializedMetadata));
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
		$this->_conn->exec("UPDATE cache SET stamp = %d WHERE resourceid = '%s' AND cachetype = '%s'", Array(time(), $resourceid, $cachetype));
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
	function saveNzbCache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::SpotNzb, false, $content);
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
		return $this->saveCache($resourceId, $this::Web, false, $content);
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
	function updateSpotImageCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::SpotImage);
	} # updateSpotImageCacheStamp

	/*
	 * Save an image resource into the cache
	 */
	function saveSpotImageCache($resourceId, $metadata, $content) {
		return $this->saveCache($resourceId, $this::SpotImage, $metadata, $content);
	} # saveSpotImagecache

	/*
	 * Retrieve a statistics count from the cache
	 */
	function getCachedStats($resourceId) {
		return $this->getCache($resourceId, $this::Statistics);
	} # getCachedStats

    /*
     * Checks if we have statistics count from the cache
     */
    function hasCachedStats($resourceId) {
        return $this->isCached($resourceId, $this::Statistics);
    } # hasCachedStats

	/*
	 * Update an HTTP resource from the cache
	 */
	function updateStatsCacheStamp($resourceId) {
		return $this->updateCacheStamp($resourceId, $this::Statistics);
	} # updateStatsCacheStamp

	/*
	 * Save an HTTP resource into the cache
	 */
	function saveStatsCache($resourceId, $content) {
		return $this->saveCache($resourceId, $this::Statistics, false, $content);
	} # saveStatsCache


} # Dao_Base_Cache
