<?php

class Services_Providers_Http {
	private $_cacheDao;

	/*
	 * constructor
	 */
	public function __construct(Dao_Cache $cacheDao) {
		$this->_cacheDao = $cacheDao;
	}  # ctor
	
	/* 
	 * Haalt een url op en cached deze
	 */
	function getFromWeb($url, $storeWhenRedirected, $ttl=900) {
		SpotTiming::start(__FUNCTION__);
		$url_md5 = md5($url);

		/*
		 * Is this URL stored in the cache and is it still valid?
		 */
		$content = $this->_cacheDao->getCachedHttp($url_md5); 
		if ((!$content) || ( (time()-(int) $content['stamp']) > $ttl)) {
			$data = array();

			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0) Gecko/20100101 Firefox/8.0');
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt ($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

			/*
			 * If we already have content stored in our cache, just ask
			 * the server if the content is modified since our last
			 * time this was stored in the cache
			 */
			if ($content) {
				curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
				curl_setopt($ch, CURLOPT_TIMEVALUE, (int) $content['stamp']);
			} # if

			$response = curl_exec($ch);

			/*
			 * Curl returns false on some unspecified errors (eg: a timeout)
			 */
			if ($response !== false) {
				$info = curl_getinfo($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				/* 
				 * Server respnded with 304 (Resource not modified)
				 */
				if ($http_code == 304) {
					$data['content'] = $content['content'];
				} else {
					$data['content'] = substr($response, -$info['download_content_length']);
 				} # else

			} else {
				$http_code = 700; # Curl returned an error
			} # else

			curl_close($ch);

			/*
			 * HTTP return code is other than 200 (OK) and 
			 * other than 304 (Resource not modified),
			 * we have no use for the result
			 */
			if ($http_code != 200 && $http_code != 304) {
				return array($http_code, false);
			} # if

			/* 
			 * A ttl > 0 is specified, meaning we are allowed to
			 * store resources in the cache
			 */
			if ($ttl > 0) {
				switch($http_code) {
					case 304		: {
						/*
						 * Update the timestamp in the database to refresh this
						 * cached resource.
						 */
						$this->_cacheDao->updateHttpCacheStamp($url_md5);
						break;
					} # 304 (resource not modified)

					default 		: {
						/*
						 * Store the retrieved information in the cache
						 */
						if (($storeWhenRedirected) || ($info['redirect_count'] == 0)) {
							$this->_cacheDao->saveHttpCache($url_md5, $data['content']);
						} # if
					} # if
				} # switch

			} # else
		} else {
			$http_code = 304;
			$data = $content;
		} # else

		SpotTiming::stop(__FUNCTION__, array($url, $storeWhenRedirected, $ttl));

		return array($http_code, $data);
	} # getFromWeb

} # Services_Providers_Http
