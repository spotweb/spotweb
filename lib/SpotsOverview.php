<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_cache;
	private $_cacheDao;
	private $_settings;
	private $_activeRetriever;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_cache = new SpotCache($db);
		$this->_spotImage = new SpotImage($db);
		$this->_cacheDao = $db->_cacheDao;
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
		$data = $this->_spotImage->getImageInfoFromString($data['content']);
		$data['expire'] = true;
		SpotTiming::stop(__FUNCTION__, array($md5, $size, $default, $rating));
		return $data;
	} # getAvatarImage

	/* 
	 * Haalt een url op en cached deze
	 */
	function getFromWeb($url, $storeWhenRedirected, $ttl=900) {
		$x = new Services_Providers_Http($this->_db->_cacheDao);
		return $x->getFromWeb($url, $storeWhenRedirected, $ttl);
	} # getFromWeb

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
