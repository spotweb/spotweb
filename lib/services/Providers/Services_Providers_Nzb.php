<?php

class Services_Providers_Nzb {
	private $_cacheDao;
	private $_nntpSpotReading;

	/*
	 * constructor
	 */
	public function __construct(Dao_Cache $cacheDao, Services_Nntp_SpotReading $nntpSpotReading) {
		$this->_cacheDao = $cacheDao;
		$this->_nntpSpotReading = $nntpSpotReading;
	}  # ctor

    /*
     * Returns if we have the nzb file already cached
     */
    function hasCachedNzb($messageId) {
        return $this->_cacheDao->hasCachedNzb($messageId);
    } # hasCachedNzb

	/* 
	 * Returns the NZB file
	 */
	function fetchNzb($fullSpot) {
		SpotTiming::start(__FUNCTION__);

		/*
		 * Retrieve the NZB from the cache
		 */
		$nzb = $this->_cacheDao->getCachedNzb($fullSpot['messageid']);
		if (!empty($nzb)) {
			/*
			 * NZB file is alread in the cache, update the cache timestamp
			 */
			$nzb = $nzb['content'];
			$this->_cacheDao->updateNzbCacheStamp($fullSpot['messageid']);
		} else {
            SpotTiming::start(__FUNCTION__ . '::cacheMiss');
			/*
			 * File is not in the cache yet, retrieve it from the appropriate store, and
			 * store it in the cache
			 */
            $nzb = null;

            // Search for alternate download urls
            $alternateDownload = new Services_Providers_HttpNzb($fullSpot, $this->_cacheDao);

            // Only return an alternate if there is one.
            if ($alternateDownload->hasNzb()) {
                $nzb = $alternateDownload->getNzb();
            } else {
                $nzb = $this->_nntpSpotReading->readBinary($fullSpot['nzb'], true);
            } # else

            /*
             * If the returned NZB is empty, lets create a dummy (invalid) NZB file
             * we can store. This way, we prevent hitting the usenet or HTTP server
             * over and over again for invalid NZB files.
             */
            $mustExpire = false;
            if (empty($nzb)) {
                $nzb = '<xml><error>Invalid NZB file, unable to retrieve correct NZB file</error></xml>';
                $mustExpire = true;
            } # if

            if (!$this->_cacheDao->saveNzbCache($fullSpot['messageid'], $nzb, $mustExpire)) {
                error_log('Spotweb: Unable to save NZB file to cache, is cache directory writable?');
            } # if

            SpotTiming::stop(__FUNCTION__ . '::cacheMiss');
		} # else

		SpotTiming::stop(__FUNCTION__, array($fullSpot));

		return $nzb;
	} # fetchNzb
	

} # Services_Providers_Nzb
