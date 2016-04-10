<?php

class Dao_Base_FlatCacheStore implements Dao_CacheStore {
	private     $_cachePath     = '';

    public function __construct($cachePath) {
	$this->_cachePath = $cachePath;

        if (empty($this->_cachePath)) {
            throw new NotImplementedException("Cache path is null?");
        } # if
    } # ctor

    /*
     * Removes an item from the cache
     */
    public function removeCacheItem($cacheId, $cachetype, $metaData) {
        $filePath = $this->calculateFilePath($cacheId, $cachetype, $metaData);
        @unlink($filePath);
    } # removeCacheItem


    /*
     * Calculates the exact filepath given a storageid
     */
    protected function calculateFilePath($cacheId, $cacheType, $metadata) {
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
         * We want to store at most 1000 file in one directory,
         * so we use this.
         */
        if (floor($cacheId/ 1000) > 1000) {
            $filePath .= implode(DIRECTORY_SEPARATOR, str_split($cacheId, 3)) . DIRECTORY_SEPARATOR . $cacheId;
        } else {
            $filePath .= floor($cacheId / 1000) . DIRECTORY_SEPARATOR . $cacheId;
        } # else

        /*
         * And create an extension, because thats nicer.
         */
        if ($cacheType == Dao_Cache::SpotImage) {
            /*
             * We need to 'migrate' the older cache format to this one
             */
            if (($metadata !== false) && (!isset($metadata['dimensions']))) {
                $metadata = array('dimensions' => $metadata, 'isErrorImage' => false);
            } // if

            switch($metadata['dimensions']['imagetype']) {
                case IMAGETYPE_GIF          : $filePath .= '.gif'; break;
                case IMAGETYPE_JPEG         : $filePath .= '.jpg'; break;
                case IMAGETYPE_PNG          : $filePath .= '.png'; break;

                default                     : $filePath .= '.image.' . $metadata['dimensions']['imagetype']; break;
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
    public function getCacheContent($cacheId, $cacheType, $metaData) {
        /*
         * Get the unique filepath
         */
        $filePath = $this->calculateFilePath($cacheId, $cacheType, $metaData);
        $cacheContent = @file_get_contents($filePath);

        if ($cacheContent === false) {
                $this->removeCacheItem($cacheId, $cacheType, $metaData);

                throw new CacheIsCorruptException('Cache is corrupt, could not find on-disk resource for: ' . $cacheId . ' -> ' . $filePath);
        } # if

        return $cacheContent;
    } # getCacheContent

    /*
     * Stores the actual content for a given resourceid
     */
    public function putCacheContent($cacheId, $cacheType, $content, $metaData) {
        /*
           * Get the unique filepath
           */
        $filePath = $this->calculateFilePath($cacheId, $cacheType, $metaData);

        /*
         * Create the directory
         */
        $success = true;
        if (!is_writable(dirname($filePath))) {
            $success = @mkdir(dirname($filePath), 0777, true);
            @chmod(dirname($filePath), 0777); // mkdir's chmod is masked with umask()
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


}  # Dao_Base_CacheStore
