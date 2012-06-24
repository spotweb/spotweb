<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_cache;
	private $_settings;
	private $_activeRetriever;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_cache = new SpotCache($db);
		$this->_spotImage = new SpotImage($db);
	} # ctor

	function getFullSpot($msgId, $ourUserId, $nntp) {
		$x = new Services_Providers_FullSpot($this->_db->_spotDao, new Services_Nntp_SpotReading($nntp));
		return $x->fetchFullSpot($msgId, $ourUserId);
	} # getFullSpot

	function getSpotComments($userId, $msgId, $nntp, $start, $length) {
		$x = new Services_Providers_Comments($this->_db->_commentDao, new Services_Nntp_SpotReading($nntp));
		return $x->fetchSpotComments($msgId, $userId, $start, $length);
	} # getSpotComments	


	function cacheNewSpotCount() {
		$x = new Services_Providers_CacheNewSpotCount($this->_db->_userFilterCountDao, 
							$this->_db->_userFilterDao,
							$this->_db->_spotDao,
						new Services_Search_QueryParser($this->_db->getDbHandle()));
		return $x->cacheNewSpotCount();
	} # cacheNewSpotCount
	
	function getNzb($fullSpot, $nntp) {
		$x = new Services_Providers_Nzb($this->_db->_spotDao,
										$this->_db->_cacheDao,
										new Services_Nntp_SpotReading($nntp));
		return $x->getNzb($fullSpot);
	} # getNzb

	/* 
	 * Geef de image file terug
	 */
	function getImage($fullSpot, $nntp) {
		SpotTiming::start(__FUNCTION__);
		$return_code = false;

		if (is_array($fullSpot['image'])) {
			if ($this->_activeRetriever && $this->_cache->isCached($fullSpot['messageid'], SpotCache::SpotNzb)) {
				$data = true;
			} elseif ($data = $this->_cache->getCache($fullSpot['messageid'], SpotCache::SpotImage)) {
				$this->_cache->updateCacheStamp($fullSpot['messageid'], SpotCache::SpotImage);
			} else {
				try {
					$img = $nntp->getImage($fullSpot);

					if ($data = $this->_spotImage->getImageInfoFromString($img)) {
						$this->_cache->saveCache($fullSpot['messageid'], SpotCache::SpotImage, $data['metadata'], $data['content']);
					} # if	
				}
				catch(ParseSpotXmlException $x) {
					$return_code = 900;
				}
				catch(Exception $x) {
					# "No such article" error
					if ($x->getCode() == 430) {
						$return_code = 430;
					} 
					# als de XML niet te parsen is, niets aan te doen
					elseif ($x->getMessage() == 'String could not be parsed as XML') {
						$return_code = 900;
					} else {
						throw $x;
					} # else
				} # catch
			} # if
		} elseif (!empty($fullSpot['image'])) {
			list($return_code, $data) = $this->getFromWeb($fullSpot['image'], false, 24*60*60);
		} # else

		# bij een error toch een image serveren
		if (!$this->_activeRetriever) {
			if ($return_code && $return_code != 200 && $return_code != 304) {
				$data = $this->_spotImage->createErrorImage($return_code);
			} elseif (empty($fullSpot['image'])) {
				$data = $this->_spotImage->createErrorImage(901);
			} elseif ($return_code && !$data['metadata']) {
				$data = $this->_spotImage->createErrorImage($return_code);
			} elseif ($return_code && !$data) {
				$data = $this->_spotImage->createErrorImage($return_code);
			} elseif (!$data) {
				$data = $this->_spotImage->createErrorImage(999);
			} # elseif
		} elseif (!isset($data)) {
			$data = false;
		} # elseif

		SpotTiming::stop(__FUNCTION__, array($fullSpot, $nntp));
		return $data;
	} # getImage

	/* 
	 * Geef een statistics image file terug
	 */
	function getStatisticsImage($graph, $limit, $nntp, $language) {
		SpotTiming::start(__FUNCTION__);
		$spotStatistics = new SpotStatistics($this->_db);

		if (!array_key_exists($graph, $this->_spotImage->getValidStatisticsGraphs()) || !array_key_exists($limit, $this->_spotImage->getValidStatisticsLimits())) {
			$data = $this->_spotImage->createErrorImage(400);
			SpotTiming::stop(__FUNCTION__, array($graph, $limit, $nntp));
			return $data;
		} # if

		$lastUpdate = $this->_db->getLastUpdate($nntp['host']);
		$resourceid = $spotStatistics->getResourceid($graph, $limit, $language);
		$data = $this->_cache->getCache($resourceid, SpotCache::Statistics);
		if (!$data || $this->_activeRetriever || (!$this->_settings->get('prepare_statistics') && (int) $data['stamp'] < $lastUpdate)) {
			$data = $this->_spotImage->createStatistics($graph, $limit, $lastUpdate, $language);
			$this->_cache->saveCache($resourceid, SpotCache::Statistics, $data['metadata'], $data['content']);
		} # if

		$data['expire'] = true;
		SpotTiming::stop(__FUNCTION__, array($graph, $limit, $nntp));
		return $data;
	} # getStatisticsImage

	/*
	 * Geeft een Spotnet avatar image terug
	 */
	function getAvatarImage($md5, $size, $default, $rating) {
		SpotTiming::start(__FUNCTION__);
		$url = 'http://www.gravatar.com/avatar/' . $md5 . "?s=" . $size . "&d=" . $default . "&r=" . $rating;

		list($return_code, $data) = $this->getFromWeb($url, true, 60*60);
		$data['expire'] = true;
		SpotTiming::stop(__FUNCTION__, array($md5, $size, $default, $rating));
		return $data;
	} # getAvatarImage

	/* 
	 * Haalt een url op en cached deze
	 */
	function getFromWeb($url, $storeWhenRedirected, $ttl=900) {
		SpotTiming::start(__FUNCTION__);
		$url_md5 = md5($url);

		if ($this->_activeRetriever && $this->_cache->isCached($url_md5, SpotCache::Web)) {
			return array(200, true);
		} # if

		$content = $this->_cache->getCache($url_md5, SpotCache::Web);
		if (!$content || time()-(int) $content['stamp'] > $ttl) {
			$data = array();

			SpotTiming::start(__FUNCTION__ . ':curl');
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
			if ($content) {
				curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
				curl_setopt($ch, CURLOPT_TIMEVALUE, (int) $content['stamp']);
			} # if
			$response = curl_exec($ch);
			SpotTiming::stop(__FUNCTION__ . ':curl', array($response));

			/*
			 * Curl returns false on some unspecified errors (eg: a timeout)
			 */
			if ($response !== false) {
				$info = curl_getinfo($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$data['content'] = ($http_code == 304) ? $content['content'] : substr($response, -$info['download_content_length']);
			} else {
				$http_code = 700; # Curl returned an error
			} # else
			curl_close($ch);

			if ($http_code != 200 && $http_code != 304) {
				return array($http_code, false);
			} elseif ($ttl > 0) {
				if ($imageData = $this->_spotImage->getImageInfoFromString($data['content'])) {
					$data['metadata'] = $imageData['metadata'];
				} else {
					$data['metadata'] = '';
				} # else

				switch($http_code) {
					case 304:	if (!$this->_activeRetriever) {
									$this->_cache->updateCacheStamp($url_md5, SpotCache::Web);
								} # if
								break;
					default:	if ($info['redirect_count'] == 0 || ($info['redirect_count'] > 0 && $storeWhenRedirected)) {
									$this->_cache->saveCache($url_md5, SpotCache::Web, $data['metadata'], $data['content']);
								} # if
				} # switch
			} # else
		} else {
			$http_code = 304;
			$data = $content;
		} # else

		SpotTiming::stop(__FUNCTION__, array($url, $storeWhenRedirected, $ttl));

		return array($http_code, $data);
	} # getUrl

	/*
	 * Laad de spots van af positie $start, maximaal $limit spots.
	 *
	 * $parsedSearch is een array met velden, filters en sorteringen die 
	 * alles bevat waarmee SpotWeb kan filteren. 
	 */
	function loadSpots($ourUserId, $start, $limit, $parsedSearch) {
		SpotTiming::start(__FUNCTION__);
		
		# en haal de daadwerkelijke spots op
		$spotResults = $this->_db->getSpots($ourUserId, $start, $limit, $parsedSearch, false);

		$spotCnt = count($spotResults['list']);
		for ($i = 0; $i < $spotCnt; $i++) {
			# We forceren category naar een integer, sqlite kan namelijk een lege
			# string terug ipv een category nummer
			$spotResults['list'][$i]['category'] = (int) $spotResults['list'][$i]['category'];
			
			# We trekken de lijst van subcategorieen uitelkaar 
			$spotResults['list'][$i]['subcatlist'] = explode("|", 
							$spotResults['list'][$i]['subcata'] . 
							$spotResults['list'][$i]['subcatb'] . 
							$spotResults['list'][$i]['subcatc'] . 
							$spotResults['list'][$i]['subcatd'] . 
							$spotResults['list'][$i]['subcatz']);
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($spotResults));
		return $spotResults;
	} # loadSpots()

	
	public function setActiveRetriever($b) {
		$this->_activeRetriever = $b;
	} # setActiveRetriever

	public function prepareCategorySelection($dynaList) {
		$x = new Services_Search_QueryParser($this->_db->getDbHandle());
		return $x->prepareCategorySelection($dynaList);
	}
	public function compressCategorySelection($categoryList, $strongNotList) {
		$x = new Services_Search_QueryParser($this->_db->getDbHandle());
		return $x->compressCategorySelection($categoryList, $strongNotList);
	}
	public function filterToQuery($search, $sort, $currentSession, $indexFilter) {
		$x = new Services_Search_QueryParser($this->_db->getDbHandle());
		return $x->filterToQuery($search, $sort, $currentSession, $indexFilter);
	}

} # class SpotsOverview
