<?php

class Services_Providers_SpotImage {
	private $_cacheDao;
	private $_serviceHttp;
	private $_nntpSpotReading;

	private $_spotImage;

	/*
	 * constructor
	 */
	public function __construct(Services_Providers_Http $serviceHttp, 
								Services_Nntp_SpotReading $nntpSpotReading,
								Dao_Cache $cacheDao) {
		$this->_serviceHttp = $serviceHttp;
		$this->_cacheDao = $cacheDao;
		$this->_nntpSpotReading = $nntpSpotReading;

		$this->_spotImage = new SpotImage( new SpotDb(array()) );
	}  # ctor
	
	/*
	 * Fetches an image either from the cache, the web or a
	 * newsgroup depending on where the image is available
	 */
	public function fetchSpotImage($fullSpot) {
		SpotTiming::start(__FUNCTION__);
		$return_code = 0;
		$validImage = false;

		if ($data = $this->_cacheDao->getCachedSpotImage($fullSpot['messageid'])) {
			$this->_cacheDao->updateSpotImageCacheStamp($fullSpot['messageid']);
			return $data;
		} # if

		/*
		 * Determine whether the spot is stored on an NNTP serve or a web resource,
		 * older spots are stored on an HTTP server
		 */
		if (is_array($fullSpot['image'])) {
			try {
				/*
				 * Convert the list of segments to a format
				 * usable for readBinary()
				 */
				$segmentList = array();
				foreach($fullSpot['image']['segment'] as $seg) {
					$segmentList[] = $seg;
				} # foreach

				$data = $this->_spotImage->getImageInfoFromString(
								$this->_nntpSpotReading->readBinary($segmentList, false)
						);

				/*
				 * If this is not a valid image, create a dummy error code, 
				 * else we save it in the cache
				 */
				if ($data !== false) {
					$validImage = true;
					$this->_cacheDao->saveSpotImageCache($fullSpot['messageid'], $data);
				} else {
					$validImage = false;
					$return_code = 998;
				} # if	
			} catch(Exception $x) {
				$validImage = false;
				$return_code = $x->getCode();

				# "No such article" error
				if ($x->getCode() !== 430) {
					throw $x;
				} # else
			} # catch
		} elseif (empty($fullSpot['image'])) {
			/*
			 * Spot did not contain an image (this is illegal?),
			 * create a dummy error message
			 */
			$validImage = false;
			$return_code = 901;
		} elseif (!empty($fullSpot['image'])) {
			/*
			 * We don't want the HTTP layer of this code to cache the image, because
			 * we want to cache / store additional information in the cache for images
			 */
			list($return_code, $data) = $this->_serviceHttp->getFromWeb($fullSpot['image'], false, 0);
			if (($return_code == 200) || ($return_code == 304)) {

				$data = $this->_spotImage->getImageInfoFromString($data['content']);
				if ($data !== false) {
					$validImage = true;
					$this->_cacheDao->saveSpotImageCache($fullSpot['messageid'], $data);
				} else {
					$validImage = false;
					$return_code = 997;
				} # else 

			} else {
				$validImage = false;
			} # else
		} # elseif

		/*
		 * Did we get a return code other than 200 OK and 
		 * other than 304 (Resource Not modified), create
		 * an error code image
		 */
		if (!$validImage) {
			$data = $this->_spotImage->createErrorImage($return_code);
		} # if

		SpotTiming::stop(__FUNCTION__, array($fullSpot, $nntp));
		return $data;
	} # fetchSpotImage
	
} # Services_Providers_SpotImage
