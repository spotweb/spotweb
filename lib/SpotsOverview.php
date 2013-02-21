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
	
	/*
	 * Geef een volledig Spot array terug
	 */
	function getFullSpot($msgId, $ourUserId, $nntp) {
		SpotTiming::start('SpotsOverview::' . __FUNCTION__);

		$fullSpot = $this->_db->getFullSpot($msgId, $ourUserId);
		
		if (empty($fullSpot)) {
			/*
			 * Retrieve a full loaded spot from the NNTP server
			 */
			$newFullSpot = $nntp->getFullSpot($msgId);
			$this->_db->addFullSpots( array($newFullSpot) );
			
			/*
			 * If the current spotterid is empty, we probably now
			 * have a spotterid because we have the fullspot.
			 */
			if ((empty($fullSpot['spotterid'])) && ($newFullSpot['verified'])) {
				$spotSigning = Services_Signing_Base::newServiceSigning();
				$newFullSpot['spotterid'] = $spotSigning->calculateSpotterId($newFullSpot['user-key']['modulo']);

				/* 
				 * Update the spotterid in the spots table so it can be filtered later on
				 */
				$this->_db->updateSpotInfoFromFull($newFullSpot);
			} # if
			
			/*
			 * We ask our DB to retrieve the fullspot again, this ensures
			 * us all information is present and in always the same format
			 */
			$fullSpot = $this->_db->getFullSpot($msgId, $ourUserId);
		} # if

		/**
		 * We'll overwrite our spot info from the database with some information we parse from the 
		 * XML. This is necessary because the XML contains better encoding.
		 *
		 * For example take the titel from spot bdZZdJ3gPxTAmSE%40spot.net.
		 *
		 * We cannot use all information from the XML because because some information just
		 * isn't present in the XML file
		 */
		$spotParser = new SpotParser();
		$parsedXml = $spotParser->parseFull($fullSpot['fullxml']);
		$fullSpot = array_merge($parsedXml, $fullSpot);
		$fullSpot['title'] = $parsedXml['title'];
		/*
		 * When we retrieve a fullspot entry but there is no spot entry the join in our DB query
		 * causes us to never get the spot, hence we throw this exception
		 */
		if (empty($fullSpot)) {
			throw new Exception("Spot is not in our Spotweb database");
		} # if
		SpotTiming::stop('SpotsOverview::' . __FUNCTION__, array($msgId, $ourUserId, $nntp, $fullSpot));
		
		return $fullSpot;
	} # getFullSpot

	/*
	 * Callback functie om enkel verified 'iets' terug te geven
	 */
	function cbVerifiedOnly($x) {
		return $x['verified'];
	} # cbVerifiedOnly
	
	/*
	 * Geef de lijst met comments terug 
	 */
	function getSpotComments($userId, $msgId, $nntp, $start, $length) {
		if (!$this->_settings->get('retrieve_comments')) {
			return array();
		} # if
	
		# Bereken wat waardes zodat we dat niet steeds moeten doen
		$totalCommentsNeeded = ($start + $length);
		
		SpotTiming::start(__FUNCTION__);

		# vraag een lijst op met comments welke in de database zitten en
		# als er een fullcomment voor bestaat, vraag die ook meteen op
		$fullComments = $this->_db->getCommentsFull($userId, $msgId);
		
		# Nu gaan we op zoek naar het eerste comment dat nog volledig opgehaald
		# moet worden. Niet verified comments negeren we.
		$haveFullCount = 0;
		$lastHaveFullOffset = -1;
		$retrievedVerified = 0;
		$fullCommentsCount = count($fullComments);
		for ($i = 0; $i < $fullCommentsCount; $i++) {
			if ($fullComments[$i]['havefull']) {
				$haveFullCount++;
				$lastHaveFullOffset = $i;
				
				if ($fullComments[$i]['verified']) {
					$retrievedVerified++;
				} # if
			} # if
		} # for
		
		# en haal de overgebleven comments op van de NNTP server
		if ($retrievedVerified < $totalCommentsNeeded) {
			# Als we de comments maar in delen moeten ophalen, gaan we loopen tot we
			# net genoeg comments hebben. We moeten wel loopen omdat we niet weten 
			# welke comments verified zijn tot we ze opgehaald hebben
			if (($start > 0) || ($length > 0)) {
				$newComments = array();
			
				# en ga ze ophalen
				while (($retrievedVerified < $totalCommentsNeeded) && ( ($lastHaveFullOffset) < count($fullComments) )) {
					SpotTiming::start(__FUNCTION__. ':nntp:getComments()');
					$tempList = $nntp->getComments(array_slice($fullComments, $lastHaveFullOffset + 1, $length));
					SpotTiming::stop(__FUNCTION__ . ':nntp:getComments()', array(array_slice($fullComments, $lastHaveFullOffset + 1, $length), $start, $length));
				
					$lastHaveFullOffset += $length;
					foreach($tempList as $comment) {
						$newComments[] = $comment;
						if ($comment['verified']) {
							$retrievedVerified++;
						} # if
					} # foreach
				} # while
			} else {
				$newComments = $nntp->getComments(array_slice($fullComments, $lastHaveFullOffset + 1, count($fullComments)));
			} # else
			
			# voeg ze aan de database toe
			$this->_db->addFullComments($newComments);
			
			# en voeg de oude en de nieuwe comments samen
			$fullComments = $this->_db->getCommentsFull($userId, $msgId);
		} # foreach
		
		# filter de comments op enkel geverifieerde comments
		$fullComments = array_filter($fullComments, array($this, 'cbVerifiedOnly'));

		# geef enkel die comments terug die gevraagd zijn. We vragen wel alles op
		# zodat we weten welke we moeten negeren.
		if (($start > 0) || ($length > 0)) {
			$fullComments = array_slice($fullComments , $start, $length);
		} # if
		
		# omdat we soms array elementen unsetten, is de array niet meer
		# volledig oplopend. We laten daarom de array hernummeren
		SpotTiming::stop(__FUNCTION__, array($msgId, $start, $length));
		return $fullComments;
	} # getSpotComments()

	/*
	 * Pre-calculates the amount of new spots
	 */
	function cacheNewSpotCount() {
		$statisticsUpdate = array();

		/*
		 * Update the filter counts for the users.
		 *
		 * Basically it compares the lasthit of the session with the lastupdate
		 * of the filters. If lasthit>lastupdate, it will store the lastupdate as
		 * last counters read, hence we need to do it here and not at the end.
		 */
		$this->_db->updateCurrentFilterCounts();
		
		/*
		 * First we want a unique list of all currently
		 * created filter combinations so we can determine
		 * its' spotcount
		 */
		$filterList = $this->_db->getUniqueFilterCombinations();

		/* We add a dummy entry for 'all new spots' */
		$filterList[] = array('id' => 9999, 'userid' => -1, 'filtertype' => 'dummyfilter', 
							'title' => 'NewSpots', 'icon' => '', 'torder' => 0, 'tparent' => 0,
							'tree' => '', 'valuelist' => 'New:0', 'sorton' => '', 'sortorder' => '');
		
		/*
		 * Now get the current number of spotcounts for all
		 * filters. This allows us to add to the current number
		 * which is a lot faster than just asking for the complete
		 * count
		 */
		$cachedList = $this->_db->getCachedFilterCount(-1);

		/*
		 * Loop throug each unique filter and try to calculate the
		 * total amount of spots
		 */
		foreach($filterList as $filter) {
			# Reset the PHP timeout timer
			set_time_limit(960);
			
			# Calculate the filter hash
			$filter['filterhash'] = sha1($filter['tree'] . '|' . urldecode($filter['valuelist']));
			$filter['userid'] = -1;

			#echo 'Calculating hash for: "' . $filter['tree'] . '|' . $filter['valuelist'] . '"' . PHP_EOL;
			#echo '         ==> ' . $filter['filterhash'] . PHP_EOL;
			
			# Check to see if this hash is already in the database
			if (isset($cachedList[$filter['filterhash']])) {
				$filter['lastupdate'] = $cachedList[$filter['filterhash']]['lastupdate'];
				$filter['lastvisitspotcount'] = $cachedList[$filter['filterhash']]['currentspotcount'];
				$filter['currentspotcount'] = $cachedList[$filter['filterhash']]['currentspotcount'];
			} else {
				# Apparently a totally new filter
				$filter['lastupdate'] = 0;
				$filter['lastvisitspotcount'] = 0;
				$filter['currentspotcount'] = 0;
			} # else

			/*
			 * Now we have to simulate a search. Because we want to 
			 * utilize existing infrastructure, we convert the filter to
			 * a format which can be used in this system
			 */
			$strFilter = '&amp;search[tree]=' . $filter['tree'];

			$valueArray = explode('&', $filter['valuelist']);
			if (!empty($valueArray)) {
				foreach($valueArray as $value) {
					$strFilter .= '&amp;search[value][]=' . $value;
				} # foreach
			} # if

			/*
			 * Now we will artifficially add the 'stamp' column to the
			 * list of parameters. Basically this tells the query
			 * system to only query for spots newer than the last
			 * update of the filter
			 */
			$strFilter .= '&amp;search[value][]=stamp:>:' . $filter['lastupdate'];
			
			# Now parse it to an array as we would get when called from a webpage
			parse_str(html_entity_decode($strFilter), $query_params);

			/*
			 * Create a fake session
			 */
			$userSession = array();
			$userSession['user'] = array('lastread' => $filter['lastupdate']);
			$userSession['user']['prefs'] = array('auto_markasread' => false);
			
			/*
			 * And convert the parsed system to an SQL statement and actually run it
			 */
			$parsedSearch = $this->filterToQuery($query_params['search'], array(), $userSession, array());
			$spotCount = $this->_db->getSpotCount($parsedSearch['filter']);

			/*
			 * Because we only ask for new spots, just increase the current
			 * amount of spots. This has a slight chance of sometimes missing
			 * a spot but it's sufficiently accurate for this kind of importance
			 */
			$filter['currentspotcount'] += $spotCount;
			
			$this->_db->setCachedFilterCount(-1, array($filter['filterhash'] => $filter));

			/*
			 * Now determine the users wich actually have this filter
			 */
			$usersWithThisFilter = $this->_db->getUsersForFilter($filter['tree'], $filter['valuelist']);
			foreach($usersWithThisFilter as $thisFilter) {
				$statisticsUpdate[$thisFilter['userid']][] = array('title' => $thisFilter['title'],
				  											   'newcount' => $spotCount,
				  											   'enablenotify' => $thisFilter['enablenotify']);
			} # foreach
		} # foreach

		/*
		 * We want to make sure all filtercounts are available for all
		 * users, hence we make sure all these records do exist
		 */
		$this->_db->createFilterCountsForEveryone();

		return $statisticsUpdate;
	} # cacheNewSpotCount
	
	/* 
	 * Geef de NZB file terug
	 */
	function getNzb($fullSpot, $nntp) {
		SpotTiming::start(__FUNCTION__);

		if ($this->_activeRetriever && $this->_cache->isCached($fullSpot['messageid'], SpotCache::SpotNzb)) {
			$nzb = true;
		} elseif ($nzb = $this->_cache->getCache($fullSpot['messageid'], SpotCache::SpotNzb)) {
			$this->_cache->updateCacheStamp($fullSpot['messageid'], SpotCache::SpotNzb);
			$nzb = $nzb['content'];
		} else {
			$nzb = $nntp->getNzb($fullSpot['nzb']);
			$this->_cache->saveCache($fullSpot['messageid'], SpotCache::SpotNzb, false, $nzb);
		} # else

	  $alternateDownload = new SpotAlternateDownload($fullSpot);
	  if ($alternateDownload->hasNzb()) {
	    $nzb = $alternateDownload->getNzb();
	  }
		
		SpotTiming::stop(__FUNCTION__, array($fullSpot, $nntp));

		return $nzb;
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
