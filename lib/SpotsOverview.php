<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_cacheDao;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
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
	 * Geeft een Spotnet avatar image terug
	 */
	function getAvatarImage($md5, $size, $default, $rating) {
		SpotTiming::start(__FUNCTION__);
		$url = 'http://www.gravatar.com/avatar/' . $md5 . "?s=" . $size . "&d=" . $default . "&r=" . $rating;

		list($return_code, $data) = $this->getFromWeb($url, true, 60*60);

		$svc_ImageUtil = new Services_Image_Util();
		$dimensions = $svc_ImageUtil->getImageDimensions($data);

		$data = array('content' => $data);
		$data['metadata'] = $dimensions;
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
