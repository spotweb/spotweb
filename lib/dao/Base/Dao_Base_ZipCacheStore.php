<?php

class Dao_Base_ZipCacheStore implements Dao_CacheStore {
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
        list($zipName, $fileName, $subFolder) = $this->calculateFilePath($cacheId, $cachetype, $metaData);

	$zip = new ZipArchive();
	if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
		throw new CacheIsCorruptException('Unable to open ZIP file');
	} // if
	$zip->deleteName($fileName);
	$zip->close();
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
	$zipName = $filePath;

        /*
         * We want to store at most 1000 file in one directory,
         * so we use this.
         */
        if (floor($cacheId/ 1000) > 1000) {
	    $cacheSeperated = str_split($cacheId, 3);

	    $filePath .= implode(DIRECTORY_SEPARATOR, $cacheSeperated);

	    $cacheSeperated = array_splice($cacheSeperated, 0, -1);
            $zipName .= implode(DIRECTORY_SEPARATOR, $cacheSeperated) . '.zip';
        } else {
            $filePath .= floor($cacheId / 1000);
	    $zipName .= floor($cacheId / 1000) . '.zip';
        } # else

        /*
         * And create an extension, because thats nicer.
         */
	$extension = '';
        if ($cacheType == Dao_Cache::SpotImage) {
            /*
             * We need to 'migrate' the older cache format to this one
             */
            if (($metadata !== false) && (!isset($metadata['dimensions']))) {
                $metadata = array('dimensions' => $metadata, 'isErrorImage' => false);
            } // if

            switch($metadata['dimensions']['imagetype']) {
                case IMAGETYPE_GIF          : $extension = '.gif'; break;
                case IMAGETYPE_JPEG         : $extension = '.jpg'; break;
                case IMAGETYPE_PNG          : $extension = '.png'; break;

                default                     : $extension = '.image.' . $metadata['dimensions']['imagetype']; break;
            } # switch
        } else {
            switch ($cacheType) {
                case Dao_Cache::SpotNzb             : $extension = '.nzb'; break;
                case Dao_Cache::Statistics          : $extension = '.stats'; break;
                case Dao_Cache::Web                 : $extension = '.http'; break;
                case Dao_Cache::TranslaterToken     : $extension = '.token'; break;
                case Dao_Cache::TranslatedComments  : $extension = '.translatedcomments'; break;

                default                         : throw new NotImplementedException("Undefined Cachetype: " . $cacheType);
            } # switch
        } # else

	// 1st element is ZIP name, 2nd is file to include in it
        return [$zipName, $cacheId . $extension, $filePath];
    } # calculateFilePath

    /*
     * Returns the actual contents for a given resourceid
     */
    public function getCacheContent($cacheId, $cacheType, $metaData) {
        /*
         * Get the unique filepath
         */
        list($zipName, $fileName, $subFolder) = $this->calculateFilePath($cacheId, $cacheType, $metaData);

        $zip = new ZipArchive();
        if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
                throw new CacheIsCorruptException('Unable to open ZIP file');
        } // if
        $cacheContent = $zip->getFromName($fileName);
        $zip->close();

	if ($cacheContent === false) {
		// try to see if we have the original file, ... ?
		if (is_readable($subFolder . DIRECTORY_SEPARATOR . $fileName)) {
			$cacheContent = file_get_contents($subFolder . DIRECTORY_SEPARATOR . $fileName);

			// write it to the ZIP file
			$this->putCacheContent($cacheId, $cacheType, $cacheContent, $metaData);

			// and remove it from disk
			@unlink($subFolder . DIRECTORY_SEPARATOR . $fileName);
		} else {
            		$this->removeCacheItem($cacheId, $cacheType, $metaData);
            		throw new CacheIsCorruptException('ZipCacheStore: Cache is corrupt, could not find on-disk resource for: ' . $cacheId . ' -> ' . $subFolder . DIRECTORY_SEPARATOR . $fileName);
		} // else
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
        list($zipName, $fileName, $subFolder) = $this->calculateFilePath($cacheId, $cacheType, $metaData);

        /*
	 * Create the directory
         */
        $success = true;
        if (!is_writable(dirname($zipName))) {
            $success = @mkdir(dirname($zipName), 0777, true);
            @chmod(dirname($zipName), 0777); // mkdir's chmod is masked with umask()
        } # if

        $zip = new ZipArchive();
        if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
                throw new CacheIsCorruptException('Unable to open ZIP file');
        } // if
        $zip->addFromString($fileName, $content);
        $zip->close();

        @chmod($zipName, 0777); // mkdir's chmod is masked with umask()

        return true;
    } # putCacheContent


}  # Dao_Base_CacheStore
