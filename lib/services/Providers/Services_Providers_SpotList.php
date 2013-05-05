<?php

class Services_Providers_SpotList {
	private $_spotDao;

	/*
	 * constructor
	 */
	public function __construct(Dao_Spot $spotDao) {
		$this->_spotDao = $spotDao;
	}  # ctor
	

	/*
	 * Returns a list of spots
	 */
	function fetchSpotList($ourUserId, $start, $limit, $parsedSearch) {
		SpotTiming::start(__FUNCTION__);
		
		/*
		 * Actually fetch the spots from the database
		 */
		$spotResults = $this->_spotDao->getSpots($ourUserId, $start, $limit, $parsedSearch);

		$spotCnt = count($spotResults['list']);
		for ($i = 0; $i < $spotCnt; $i++) {
			/*
			 * We get our subcategories concatenated with an | symbol,
			 * we explode them so all subcategories are within their
			 * own array item
			 */
			$spotResults['list'][$i]['subcatlist'] = explode("|", 
							$spotResults['list'][$i]['subcata'] . 
							$spotResults['list'][$i]['subcatb'] . 
							$spotResults['list'][$i]['subcatc'] . 
							$spotResults['list'][$i]['subcatd'] . 
							$spotResults['list'][$i]['subcatz']);
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($spotCnt));
		return $spotResults;
	} # fetchSpotList()
	
} # Services_Providers_SpotList
