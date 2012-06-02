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
				$spotSigning = new SpotSigning($this->_db, $this->_settings);
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

	/*
	 * When passed an array with categories, this array is expanded \
	 * to contain the fully qualified categories and subcategories.
	 */
	public function prepareCategorySelection($dynaList) {
		$strongNotList = array();
		$categoryList = array();
		
		/*
		 * The Dynatree jquery widget which we use, retrieves its data from ?page=catsjson,
		 * for each node in the dynatree we provide a key. The tree could be seen as follows,
		 * with the unique key within parenthesis.
		 *
		 * - Image (cat0)
		 * +-- Film (cat0_z0)
		 * +--- Format (cat0_z0_a)
		 * +----- DivX (cat0_z0_a0)
		 * +----- WMV (cat0_z0_a1)
		 * +-- Series (cat0_z1)
		 * +--- Format (cat0_z1_a)
		 *+----- DivX (cat0_z1_a0)
		 * +----- WMV (cat0_z1_a1)
		 * +--- Source (cat0_z1_b)
		 * - Applications (cat3)
		 * +-- Format (cat1_zz_a / cat1_a)
		 *
		 * Basially, you have a headcategory number, then you have a categorytype, then a subcategorytype (a,b,c,d, ...)
		 * then the subcategorynumber follows.
		 *
		 * When you want to select, in above example, a Film in DivX, the keyvalue is simply cat0_z0_a0.
		 * However, when you want to select the whole of 'Image', keyvalue 'cat0' would suffice. 
		 *
		 * If users would select categories manually (for example a manually constructed search), it would
		 * be more convienent for them to be able to provide shorthands, allowing one to select common category
		 * groups more easily. Spotweb wil expand those category selection items to contain the full selection.
		 *
		 * The following shorthands are allowed:
		 *		 
		 * cat0						- Will be expanded to all subcategoies of category 0
		 * cat0_z0_a				- Will be expanded to subcategory A of category 0, but the type must be z0
		 * !cat0_z0_a1				- Will remove cat0_z0_a1 from the list (order in the list is important)
		 * ~cat0_z0_a1				- 'Forbids' cat0_z0_a1 to be in the list (a NOT will be applied to it)
		 * cat0_a					- Select everything from subcategory A in category 0 (all z-types)
		 *
		 */
		$newTreeQuery = '';
		
		/*
		 * Process each item in the list, and expand it where necessary
		 */
		$dynaListCount = count($dynaList);
		for($i = 0; $i < $dynaListCount; $i++) {
			/*
			 * The given category can be one of the following four types:
			 *    cat1_z0_a			==> Everything of cat1, type z0, and then everything of subcategory a
			 *    cat1_z0			==> Everything of cat1, type z0
			 * 	  cat1_a			==> Everything of cat1 which is of 'subcategory a'
			 * 	  cat1				==> Select the whole of cat1
			 *
			 */
			if ((strlen($dynaList[$i]) > 0) && ($dynaList[$i][0] == 'c')) {
				$hCat = (int) substr($dynaList[$i], 3, 1);
				
				# Was a type + global subcategory selected? (cat1_z0_a)
				if (strlen($dynaList[$i]) == 9) {
					$typeSelected = substr($dynaList[$i], 5, 2);
					$subCatSelected = substr($dynaList[$i], 8);
				# Was only as category selected (cat1)
				} elseif (strlen($dynaList[$i]) == 4) {
					$typeSelected = '*';
					$subCatSelected = '*';
				# Was a category and type selected (cat1_z0)
				} elseif ((strlen($dynaList[$i]) == 7) && ($dynaList[$i][5] === 'z')) {
					$typeSelected = substr($dynaList[$i], 5, 2);
					$subCatSelected = '*';
				# Was a category and subcateory specified, old stype? (cat1_a3)
				} elseif (((strlen($dynaList[$i]) == 7) || (strlen($dynaList[$i]) == 8)) && ($dynaList[$i][5] !== 'z')) {
					# Convert the old style to explicit categories (cat1_z0_a3, cat1_z1_a3, cat1_z2_a3, ... )
					foreach(SpotCategories::$_categories[$hCat]['z'] as $typeKey => $typeValue) {
						$newTreeQuery .= "," . substr($dynaList[$i], 0, 4) . '_z' . $typeKey . '_' . substr($dynaList[$i], 5);
					} # foreach
					
					$typeSelected = '';
					$subCatSelected = '';
				# was a subcategory specified? (cat1_a)
				} elseif (strlen($dynaList[$i]) == 6) {
					$typeSelected = '*';
					$subCatSelected = substr($dynaList[$i], 5, 1);
				} else {
					$newTreeQuery .= "," . $dynaList[$i];
					
					$typeSelected = '';
					$subCatSelected = '';
				} # else

				/*
				 * Createa a string containing all subcategories.
				 *
				 * We always loop through all subcategories so we can reuse this bit of code
				 * both for complete category selection as subcategory selection.
				 */
				$tmpStr = '';
				foreach(SpotCategories::$_categories[$hCat] as $subCat => $subcatValues) {

					/*
					 * There are four possible cases:
					 *
					 *   $subcatSelected contains an empty string, it matches to nothing.
					 *   $subcatSelected contains an asterisk, it matches all subcategories.
					 *   $typeSelected contains an empty string, it matches nothing.
					 *   $typeSelected contains an asterisk, it matches all types.
					 */				
					if ($subCatSelected == '*') {
						foreach(SpotCategories::$_categories[$hCat]['z'] as $typeKey => $typeValue) {
							$typeKey = 'z' . $typeKey;
							if (($typeKey == $typeSelected) || ($typeSelected == '*')) {
								$tmpStr .= ',sub' . $hCat . '_' . $typeKey;
							} # if
						} # foreach
					} elseif (($subCat == $subCatSelected) && ($subCat !== 'z')) {
						foreach(SpotCategories::$_categories[$hCat]['z'] as $typeKey => $typeValue) {
							$typeKey = 'z' . $typeKey;
							if (($typeKey == $typeSelected) || ($typeSelected == '*')) {
							
								foreach(SpotCategories::$_categories[$hCat][$subCat] as $x => $y) {
									if (in_array($typeKey, $y[2])) {
										$tmpStr .= ",cat" . $hCat . "_" . $typeKey . '_' . $subCat . $x;
									} # if
								} # foreach
							} # if
						} # foreach
					} # if
				} # foreach

				$newTreeQuery .= $tmpStr;
			} elseif (substr($dynaList[$i], 0, 1) == '!') {
				# For a not, we just remove / exclude it from the list.
				$newTreeQuery = str_replace(',' . substr($dynaList[$i], 1), "", $newTreeQuery);
			} elseif (substr($dynaList[$i], 0, 1) == '~') {
				/*
				 * For a STRONG NOT, we cannot remove it from the list because want to explicitly
				 * remove those results from the query and we have to pass it in other URL's and the 
				 * likes
				 */
				$newTreeQuery .= "," . $dynaList[$i];
				
				# and add it to the strongNotList array for usage later on
				$strongNotTmp = explode("_", $dynaList[$i], 2);

				/* To deny a whole category, we have to take an other shortcut */
				if (count($strongNotTmp) == 1) {
					$strongNotList[(int) substr($strongNotTmp[0], 4)][] = '';
				} else {
					$strongNotList[(int) substr($strongNotTmp[0], 4)][] = $strongNotTmp[1];
				} # else
			} else {
				$newTreeQuery .= "," . $dynaList[$i];
			} # else
		} # for
		if ((!empty($newTreeQuery)) && ($newTreeQuery[0] == ",")) { 
			$newTreeQuery = substr($newTreeQuery, 1); 
		} # if

		/*
		 * 
		 * Starting from here, we have a prepared list - meaning, a list with all
		 * categories fully expanded.
		 *
		 * We now translate this list to an nested list of elements which is easier
		 * to convert to SQL. The format of the array is fairly typical:
		 *
		 * list['cat']
		 *            [cat]							 -> Head category, eg: 0 for Images
		 *                 [type]					 -> Type, eg: 0 for z0 
		 *                       [subcattype]		 -> Subcategory type, eg: a 
		 *                                   = value -> eg 1 for in total cat0_z0_a1
		 */     
		$dynaList = explode(',', $newTreeQuery);

		foreach($dynaList as $val) {
			if (substr($val, 0, 3) == 'cat') {
				# 0 element is headcategory
				# 1st element is type
				# 2ndelement is category
				$val = explode('_', (substr($val, 3) . '_'));

				$catVal = $val[0];
				$typeVal = $val[1];
				$subCatIdx = substr($val[2], 0, 1);
				$subCatVal = substr($val[2], 1);

				if (count($val) >= 4) {
					$categoryList['cat'][$catVal][$typeVal][$subCatIdx][] = $subCatVal;
				} # if
			} elseif (substr($val, 0, 3) == 'sub') {
				# 0 element is headcategory
				# 1st element is type
				$val = explode('_', (substr($val, 3) . '_'));

				$catVal = $val[0];
				$typeVal = $val[1];

				# Create the z-category in the categorylist
				if (count($val) == 3) {
					if (!isset($categoryList['cat'][$catVal][$typeVal])) {
						$categoryList['cat'][$catVal][$typeVal] = array();
					} # if
				} # if
			} # elseif
		} # foreach
		
		return array($categoryList, $strongNotList);
	} # prepareCategorySelection

	/*
	 * Converts a list of categories to an SQL filter
	 */
	private function categoryListToSql($categoryList) {
		$categorySql = array();

		# Make sure we were passed a valid filter
		if ((!isset($categoryList['cat'])) || (!is_array($categoryList['cat']))) {
			return $categorySql;
		} # if

		/*
		 * We have to translate the list of sub- and headcategories to an SQL WHERE statement in 
		 * multiple steps, where the 'category' is the basis for our filter.
		 *
		 * A testste for filters could be the following:
		 *   cat0_z0_a9,cat0_z1_a9,cat0_z3_a9, ==> HD beeld
		 *   cat0_z0_a9,cat0_z0_b3,cat0_z0_c1,cat0_z0_c2,cat0_z0_c6,cat0_z0_c11,~cat0_z1,~cat0_z2,~cat0_z3 ==> Nederlands ondertitelde films
		 *   cat0_a9 ==> Alles in x264HD
		 *   cat1_z0,cat1_z1,cat1_z2,cat1_z3 ==> Alle muziek, maar soms heeft muziek geen genre ingevuld!
		 * 
		 * The category list structure is:
		 *
		 *	array(1) {
		 *	  ["cat"]=>
		 *	  array(1) {								
		 *		[1]=>									<== Headcategory number (cat1)
		 *		array(4) {
		 *		  ["z0"]=>								<== Type (subcatz) number (cat1_z0)
		 *		  array(4) {
		 *			["a"]=>								<== Subcategorylist (cat1_z0_a)
		 *			array(9) {
		 *			  [0]=>								
		 *			  string(1) "0"						<== Selected subcategory (so: cat1_z0_a0)
		 *			}
		 *			["b"]=>
		 *			array(7) {
		 *			  [0]=>
		 *			  string(1) "0"
		 *
		 */

		foreach($categoryList['cat'] as $catid => $cat) {
			/*
			 * Each category we have, we try to procss all subcategories
			 * and convert it to a filter
			 */
			if ((is_array($cat)) && (!empty($cat))) {

				foreach($cat as $type => $typeValues) {
					$catid = (int) $catid;
					$tmpStr = "((s.category = " . (int) $catid . ")";
					
					# dont filter the zz types (games/apps)
					if ($type[1] !== 'z') {
						$tmpStr .= " AND (s.subcatz = '" . $type . "|')";
					} # if

					$subcatItems = array();
					foreach($typeValues as $subcat => $subcatItem) {
						$subcatValues = array();
						
						foreach($subcatItem as $subcatValue) {
							/*
							 * A spot can only contain one 'A' and 'Z' subcategory value, so we
							 * can perform an equality filter instead of a LIKE
							 */
							if ($subcat == 'a')  {
								$subcatValues[] = "(s.subcata = '" . $subcat . $subcatValue . "|') ";
							} elseif (in_array($subcat, array('b', 'c', 'd'))) {
								$subcatValues[] = "(s.subcat" . $subcat . " LIKE '%" . $subcat . $subcatValue . "|%') ";
							} # if
						} # foreach

						/*
						 *
						 * We add all subactegories within the same subcategory together (for example all
						 * formats of a movie) with an OR. This means you can pick between DivX and WMV as 
						 * a format
						 *
						 */
						if (count($subcatValues) > 0) {
							$subcatItems[] = " (" . join(" OR ", $subcatValues) . ") ";
						} # if
					} # foreach subcat

					/*
					 * After this, same headcategory and type (Image + Movie, Sound) filters for
					 * subcategories are merged together with an AND.
					 * 
					 * This results in a filter like:
					 * 
					 * (((category = 0) AND ( ((subcata = 'a0|') ) AND ((subcatd LIKE '%d0|%')
					 *
					 * This makes sure you are able to pick multiple genres within the same category/subcategory,
					 * but you will not get unpredictable results by getting an 'Action' game for Linux when you
					 * accidentally asked for either 'Action' or 'Romance'.
					 */
					if (count($subcatItems) > 0) {
						$tmpStr .= " AND (" . join(" AND ", $subcatItems) . ") ";
					} # if
					
					# Finish of the query
					$tmpStr .= ")";
					$categorySql[] = $tmpStr;
				} # foreach type

			} # if
		} # foreach

		return $categorySql;
	} # categoryListToSql 
	
	/*
	 * Converts a list of "strong nots" to the corresponding
	 * SQL statements
	 */
	private function strongNotListToSql($strongNotList) {
		$strongNotSql = array();
		
		if (empty($strongNotList)) {
			return array();
		} # if

		/*
		 * Each STRONG NOT is to be converted individually to a NOT 
		 * SQL WHERE filter
		 */
		foreach(array_keys($strongNotList) as $strongNotCat) {
			foreach($strongNotList[$strongNotCat] as $strongNotSubcat) {
				/*
				 * When the strongnot is for a whole category (eg: cat0), we can
				 * make the NOT even simpler
				 */
				if (empty($strongNotSubcat)) {
					$strongNotSql[] = "(NOT (s.Category = " . (int) $strongNotCat . "))";
				} else {
					$subcats = explode('_', $strongNotSubcat);

					/*
					 * A spot can only contain one 'A' and 'Z' subcategory value, so we
					 * can perform an equality filter instead of a LIKE
					 */
					if (count($subcats) == 1) {
						if (in_array($subcats[0][0], array('a', 'z'))) { 
							$strongNotSql[] = "(NOT ((s.Category = " . (int) $strongNotCat . ") AND (s.subcat" . $subcats[0][0] . " = '" . $this->_db->safe($subcats[0]) . "|')))";
						} elseif (in_array($subcats[0][0], array('b', 'c', 'd'))) { 
							$strongNotSql[] = "(NOT ((s.Category = " . (int) $strongNotCat . ") AND (s.subcat" . $subcats[0][0] . " LIKE '%" . $this->_db->safe($subcats[0]) . "|%')))";
						} # if
					} elseif (count($subcats) == 2) {
						if (in_array($subcats[1][0], array('a', 'z'))) { 
							$strongNotSql[] = "(NOT ((s.Category = " . (int) $strongNotCat . ") AND (s.subcatz = '" . $subcats[0] . "|') AND (subcat" . $subcats[1][0] . " = '" . $this->_db->safe($subcats[1]) . "|')))";
						} elseif (in_array($subcats[1][0], array('b', 'c', 'd'))) { 
							$strongNotSql[] = "(NOT ((s.Category = " . (int) $strongNotCat . ") AND (s.subcatz = '" . $subcats[0] . "|') AND (subcat" . $subcats[1][0] . " LIKE '%" . $this->_db->safe($subcats[1]) . "|%')))";
						} # if
					} # else
				} # else not whole subcat
			} # foreach				
		} # forEach

		return $strongNotSql;
	} # strongNotListToSql

	/*
	 * Prepareert de filter values naar een altijd juist formaat 
	 */
	private function prepareFilterValues($search) {
		$filterValueList = array();

		/*
		 * We have drie kinds of filters:
		 *		- Old type where you have a search[type] with the values stamp,title,tag and an search[text]
		 *		  containing the value to search for. This limits you to a maximum of one filter which is not
		 *		  sufficient.
		 *
		 *		  We automatically convert these kind of searches to the new type.
		 *
		 *
		 *		- New type where there is a search[value] array, which contain values in the following shape:
		 *		  type:operator:value. 
		 *        For example, tag:=:spotweb. A shorthand is also available when the operator is left out (eg: tag:spotweb),
		 *		  we assume the EQ operator was intended.
		 *
		 *		- Special kind of lists, there are a few values with a special meaning:
		 * 				New:0 					(new spots)
		 * 				Downloaded:0 			(spots which are downloaded by this account)
		 * 				Watch:0 				(spots on the watchlist of this account)
		 * 				Seen:0 					(spots which have already been opened by this account)
		 * 				MyPostedSpots:0 		(spots posted by this account)
		 * 				WhitelistedSpotters:0   (spots posted by a whitelisted spotter)
		 * 				
		 */
		if (isset($search['type'])) {
			if (!isset($search['text'])) {
				$search['text'] = '';
			} # if
		
			/*
			 * We can be provided a set of old and new filters, we don't want to
			 * overwrite the regular filters, so we take care to append to them
			 */	
			if ((!isset($search['value'])) || (!is_array($search['value']))) {
				$search['value'] = array();
			} # if
			$search['value'][] = $search['type'] . ':=:' . $search['text'];
			unset($search['type']);
		} # if

		# Make sure that we always have something to iterate through
		if ((!isset($search['value'])) || (!is_array($search['value']))) {
			$search['value'] = array();
		} # if

		# Now we transform the new query (field:operator:value pair) to an exploded array for easier iteration
		foreach($search['value'] as $value) {
			if (!empty($value)) {
				$tmpFilter = explode(':', $value);

				# Default to an '=' operator when none is given				
				if (count($tmpFilter) < 3) {
					$tmpFilter = array($tmpFilter[0],
									   '=',
									   $tmpFilter[1]);
				} # if
				
				/*
				 * Create the actual filter, we add the array_slice part to
				 * allow for an ':' in the actual search value.
				 */
				$filterValueTemp = Array('fieldname' => $tmpFilter[0],
										 'operator' => $tmpFilter[1],
										 'value' => join(":", array_slice($tmpFilter, 2)));
										 
				/*
				 * and create the actual filter list. Before appending it,
				 * we want to make sure no identical filter is already
				 * in the list, because this might make MySQL very slow.
				 */
				if (!in_array($filterValueTemp, $filterValueList)) {
					$filterValueList[] = $filterValueTemp;
				} # if
			} # if
		} # for
		
		return $filterValueList;
	} # prepareFilterValues

	/*
	 * Converts one or multiple userprovided txt filters to SQL statements
	 */
	private function filterValuesToSql($filterValueList, $currentSession) {
		# Add a list of possible text searches
		$filterValueSql = array('OR' => array(), 'AND' => array());
		$additionalFields = array();
		$additionalTables = array();
		$additionalJoins = array();
		
		$sortFields = array();
		$textSearchFields = array();
		
		# Lookp table from 'friendly' name to fully qualified one
		$filterFieldMapping = array('filesize' => 's.filesize',
								  'date' => 's.stamp',
								  'stamp' => 's.stamp',
								  'userid' => 's.spotterid',
								  'spotterid' => 's.spotterid',
								  'moderated' => 's.moderated',
								  'poster' => 's.poster',
								  'titel' => 's.title',
								  'title' => 's.title',
								  'tag' => 's.tag',
								  'new' => 'new',
								  'reportcount' => 's.reportcount',
								  'commentcount' => 's.commentcount',
								  'downloaded' => 'downloaded', 
								  'mypostedspots' => 'mypostedspots',
								  'whitelistedspotters' => 'whitelistedspotters',
								  'watch' => 'watch', 
								  'seen' => 'seen');

		foreach($filterValueList as $filterRecord) {
			$tmpFilterFieldname = strtolower($filterRecord['fieldname']);
			$tmpFilterOperator = $filterRecord['operator'];
			$tmpFilterValue = $filterRecord['value'];

			# When no match for friendly name -> column name is found, ignore the search
			if (!isset($filterFieldMapping[$tmpFilterFieldname])) {
				break;
			} # if

			# make sure the operators are valid
			if (!in_array($tmpFilterOperator, array('>', '<', '>=', '<=', '=', '!='))) {
				break;
			} # if

			/* 
			 * Ignore empty searches. We cannot use the empty() operator, 
			 * because empty(0) evaluates to true but is an valid 
			 * value to search for
			 */
			if (strlen($tmpFilterValue) == 0) {
				continue;
			} # if

			/*
			 * When the search is pure textsearch, it might be able to be optimized
			 * by utilizing the fulltext search (engine). If so, we take this path
			 * to gain the most performance.
			 */
			if (in_array($tmpFilterFieldname, array('tag', 'poster', 'titel'))) {
				/*
				 * Some databases (sqlite for example), want to have all their fulltext
				 * searches available in one SQL function call. 
				 *
				 * To be able to do this, we append all fulltext searches for now, so we
				 * can create the actual fulltext search later on.
				 */
				if (!isset($textSearchFields[$filterFieldMapping[$tmpFilterFieldname]])) {
					$textSearchFields[$filterFieldMapping[$tmpFilterFieldname]] = array();
				} # if
				$textSearchFields[$filterFieldMapping[$tmpFilterFieldname]][] = array('fieldname' => $filterFieldMapping[$tmpFilterFieldname], 'value' => $tmpFilterValue);
			} elseif (in_array($tmpFilterFieldname, array('new', 'downloaded', 'watch', 'seen', 'mypostedspots', 'whitelistedspotters'))) {
				/*
				 * Some fieldnames are mere dummy fields which map to actual
				 * functionality. Those dummiefields are processed below
				 */
				switch($tmpFilterFieldname) {
					case 'new' : {
							$tmpFilterValue = ' ((s.stamp > ' . (int) $this->_db->safe($currentSession['user']['lastread']) . ')';
							$tmpFilterValue .= ' AND (l.seen IS NULL))';
							
							break;
					} # case 'new' 
					case 'whitelistedspotters' : {
						$tmpFilterValue = ' (wl.spotterid IS NOT NULL)';

						break;
					} # case 'whitelistedspotters'
					case 'mypostedspots' : {
						$additionalFields[] = '1 AS mypostedspot';
						$additionalJoins[] = array('tablename' => 'spotsposted',
												   'tablealias' => 'spost',
												   'jointype' => 'LEFT',
												   'joincondition' => 'spost.messageid = s.messageid');
						$tmpFilterValue = ' (spost.ouruserid = ' . (int) $this->_db->safe($currentSession['user']['userid']) . ') '; 	
						$sortFields[] = array('field' => 'spost.stamp',
											  'direction' => 'DESC',
											  'autoadded' => true,
											  'friendlyname' => null);
						break;
					} # case 'mypostedspots'
					case 'downloaded' : { 
						$tmpFilterValue = ' (l.download IS NOT NULL)'; 	
						$sortFields[] = array('field' => 'downloadstamp',
											  'direction' => 'DESC',
											  'autoadded' => true,
											  'friendlyname' => null);
						break;
					} # case 'downloaded'
					case 'watch' 	  : { 
						$additionalFields[] = '1 AS mywatchedspot';
						$tmpFilterValue = ' (l.watch IS NOT NULL)'; break;
						$sortFields[] = array('field' => 'watchstamp',
											  'direction' => 'DESC',
											  'autoadded' => true,
											  'friendlyname' => null);
					} # case 'watch'
					case 'seen' 	  : {
						$tmpFilterValue = ' (l.seen IS NOT NULL)'; 	break;
						$sortFields[] = array('field' => 'seenstamp',
											  'direction' => 'DESC',
											  'autoadded' => true,
											  'friendlyname' => null);
					} # case 'seen'
				} # switch
				
				# append the created query string to be an AND filter
				$filterValueSql['AND'][] = $tmpFilterValue;
			} else {
				/*
				 * No FTS, no dummyfield, it must be some sort of comparison then.
				 *
				 * First we want to extract the field we are filtering on.
				 */
				if ($tmpFilterFieldname == 'date') {
					$tmpFilterValue = date("U",  strtotime($tmpFilterValue));
				} elseif ($tmpFilterFieldname == 'stamp') {
					$tmpFilterValue = (int) $tmpFilterValue;
				} elseif (($tmpFilterFieldname == 'filesize') && (is_numeric($tmpFilterValue) === false)) {
					# Explicitly cast to float to workaroun a rounding bug in PHP on x86
					$val = (float) trim(substr($tmpFilterValue, 0, -1));
					$last = strtolower($tmpFilterValue[strlen($tmpFilterValue) - 1]);
					switch($last) {
						case 'g': $val *= (float) 1024;
						case 'm': $val *= (float) 1024;
						case 'k': $val *= (float) 1024;
					} # switch
					$tmpFilterValue = round($val, 0);
				} # if
					
				/*
				 * add quotes around it when not numeric. We cannot blankly always add quotes
				 * as postgresql doesn't like that of course
				 */
				if (!is_numeric($tmpFilterValue)) {
					$tmpFilterValue = "'" . $this->_db->safe($tmpFilterValue) . "'";
				} else {
					$tmpFilterValue = $this->_db->safe($tmpFilterValue);
				} # if

				# depending on the type of search, we either add the filter as an AND or an OR
				if (in_array($tmpFilterFieldname, array('spotterid', 'userid'))) {
					$filterValueSql['OR'][] = ' (' . $filterFieldMapping[$tmpFilterFieldname] . ' ' . $tmpFilterOperator . ' '  . $tmpFilterValue . ') ';
				} else {
					$filterValueSql['AND'][] = ' (' . $filterFieldMapping[$tmpFilterFieldname] . ' ' . $tmpFilterOperator . ' '  . $tmpFilterValue . ') ';
				} # else
			} # if
		} # foreach

		/*
		 * When all filters are processed, we want to check wether we actually
		 * have to process any of the $textSearchFields for which we could run
		 * the db specific FTS engine.
		 *
		 * If so, ask the FTS engin to process the query.
		 */
		if (!empty($textSearchFields)) {
			foreach($textSearchFields as $searchField => $searches) {
				$parsedTextQueryResult = $this->_db->createTextQuery($searches);

				if (in_array($tmpFilterFieldname, array('poster', 'tag'))) {
					$filterValueSql['AND'][] = ' (' . implode(' OR ', $parsedTextQueryResult['filterValueSql']) . ') ';
				} else {
					$filterValueSql['AND'][] = ' (' . implode(' AND ', $parsedTextQueryResult['filterValueSql']) . ') ';
				} # if

				$additionalTables = array_merge($additionalTables, $parsedTextQueryResult['additionalTables']);
				$additionalFields = array_merge($additionalFields, $parsedTextQueryResult['additionalFields']);
				$sortFields = array_merge($sortFields, $parsedTextQueryResult['sortFields']);
			} # foreach
		} # if

		
		return array($filterValueSql, $additionalFields, $additionalTables, $additionalJoins, $sortFields);
	} # filterValuesToSql

	/*
	 * Converts the sorting as asked to an intermediate format ready for processing
	 */
	private function prepareSortFields($sort, $sortFields) {
		$VALID_SORT_FIELDS = array('category' => 1, 
								   'poster' => 1, 
								   'title' => 1, 
								   'filesize' => 1, 
								   'stamp' => 1, 
								   'subcata' => 1, 
								   'spotrating' => 1, 
								   'commentcount' => 1);

		if ((!isset($sort['field'])) || (!isset($VALID_SORT_FIELDS[$sort['field']]))) {
			/*
			 * Add an extra sort on stamp. It might be that a FTS engine or something else,
			 * has added a requested sorting as well, so make sure we add it to the end of
			 * sortfields.
			 */
			$sortFields[] = array('field' => 's.stamp', 'direction' => 'DESC', 'autoadded' => true, 'friendlyname' => null);
		} else {
			if (strtoupper($sort['direction']) != 'ASC') {
				$sort['direction'] = 'DESC';
			} # if

			/*
			 * Explicit requested sorts, are prepended to the beginning of the array, so
			 * the user requested sorting always is preferred above any other sorting
			 */			
			array_unshift($sortFields, array('field' => 's.' . $sort['field'], 
											 'direction' => $sort['direction'], 
											 'autoadded' => false, 
											 'friendlyname' => $sort['field']));
		} # else
		
		return $sortFields;
	} # prepareSortFields
	
	
	/*
	 * "Compresses" an expanded category list. It tries to search for the smallest
	 * (in string length) match which contains the same information.
	 *
	 * This function, for example, will translate cat0_z0_a1,cat0_z0_a2,... to a 
	 * simple cat0_z0_a string and other nifty tricks.
	 *
	 * This is wanted to get cleaner urls, to be more efficient when parsing and
	 * to be able to lessen the change we will hit the GET HTTP url limit.
	 *
	 */
	function compressCategorySelection($categoryList, $strongNotList) {
		SpotTiming::start(__FUNCTION__);
		$compressedList = '';

		/*
		 * We process each category, and the matching subcategories, to make sure all
		 * required elments are set. If so, we remove the individual elements and
		 * add the shorthand for it.
		 */
		foreach(SpotCategories::$_head_categories as $headCatNumber => $headCatValue) {
			$subcatsMissing = array();

			# match each subcategory
			if (isset($categoryList['cat'][$headCatNumber])) {
				$subcatsMissing[$headCatNumber] = array();

				foreach($categoryList['cat'][$headCatNumber] as $subCatType => $subCatValues) {
					$subcatsMissing[$headCatNumber][$subCatType] = array();
	
					foreach(SpotCategories::$_categories[$headCatNumber] as $subCat => $subcatValues) {
						if ($subCat !== 'z') {
							if (isset($categoryList['cat'][$headCatNumber][$subCatType][$subCat])) {
								# process all subcategory values to see if any are missing
								foreach(SpotCategories::$_categories[$headCatNumber][$subCat] as $subcatValue => $subcatDescription) {
									# Make sure the subcategory is actually avaialble for this type
									if (in_array($subCatType, $subcatDescription[2])) {
										# and if the subcat element is missing, add it to the missing list
										if (array_search($subcatValue, $categoryList['cat'][$headCatNumber][$subCatType][$subCat]) === false) {
											$subcatsMissing[$headCatNumber][$subCatType][$subCat][$subcatValue] = 1;
										} # if
									} # if
								} # foreach
							} else {
								// $subcatsMissing[$headCatNumber][$subCatType][$subCat] = array();
							} # if
						} # if
					} # foreach
					
				} # foreach

//var_dump($categoryList);
//var_dump(expression)($subcatsMissing);
//die();

				/*
				 * If not the complete headcategory has been selected, we have to
				 * do a tiny bit more work to get the exact match
				 */
				if (!empty($subcatsMissing[$headCatNumber])) {
					/*
					 * There are three possible situations:
					 *
					 * - the subcategory does not exist at all, we select the complete subcategory
					 * - the subcategory exists, but is empty. It means we do not want anything out of it
					 * - the subcategory exists, and is not empty. The items in it, are the items we do not want
					 */
					foreach($categoryList['cat'][$headCatNumber] as $subType => $subTypeValue) {
						/*
						 * Check wether the complete headcat+subtype (cat0_z0, cat0_z1) is selected
						 */
						if (!empty($subcatsMissing[$headCatNumber][$subType])) {
							foreach(SpotCategories::$_subcat_descriptions[$headCatNumber] as $subCatKey => $subCatValue) {
								if ($subCatKey !== 'z') {
									if (!isset($subcatsMissing[$headCatNumber][$subType][$subCatKey])) {
										// $compressedList .= 'cat' . $headCatNumber . '_' . $subType . '_' . $subCatKey . ',';
									} elseif (empty($subcatsMissing[$headCatNumber][$subType][$subCatKey])) {
										/*
										 * If the subcategory is completely empty, the user doesn't
										 * want anything from it
										 */
									} else {
										/*
										 * The subcategory does exist, but contains only items
										 * the user doesn't want or need. We deselected them here.
										 *
										 * We can either add the whole category, and add a few 
										 * "NOT"'s (!cat0_z0_a1) or just selected the individual 
										 * items. We determine this whether the majority is 
										 * selected or excluded.
										 */
										$moreFalseThanTrue = (count(@$subcatsMissing[$headCatNumber][$subType][$subCatKey]) > (count(@SpotCategories::$_categories[$headCatNumber][$subCatKey][$subCatValue]) / 2));
										foreach(SpotCategories::$_categories[$headCatNumber][$subCatKey] as $subCatValue => $subCatDesc) {
											if (in_array($subType, $subCatDesc[2])) {
												if ($moreFalseThanTrue) {
													if (!isset($subcatsMissing[$headCatNumber][$subType][$subCatKey][$subCatValue])) {
														$compressedList .= 'cat' . $headCatNumber . '_' . $subType . '_' . $subCatKey . $subCatValue . ',';
													} # if
												} else {
													if (isset($subcatsMissing[$headCatNumber][$subType][$subCatKey][$subCatValue])) {
														/*
														 * We have to make sure the whole category is selected, so we perform an
														 * extra check for it
														 */
														if (strpos(',' . $compressedList . ',', ',cat' . $headCatNumber . '_' . $subType . '_' . $subCatKey . ',') === false) {
															$compressedList .= 'cat' . $headCatNumber . '_' . $subType . '_' . $subCatKey . ',';
														} # if
														
														# and start deselecting the subcategories
														$compressedList .= '!cat' . $headCatNumber . '_' . $subType . '_' . $subCatKey . $subCatValue . ',';
													} # if
												} # if
											} # if
										} # foreach
									} # else
								} # if
								
							} # foreach
						} else {
							$compressedList .= 'cat' . $headCatNumber . '_' . $subType . ',';
						} # if
					} # foreach
				} else {
					$compressedList .= 'cat' . $headCatNumber . ',';
				} # else
			} # if
		} # foreach

		# and of course, add the strong not list
		if (!empty($strongNotList)) {
			foreach($strongNotList as $headCat => $subcatList) {
				foreach($subcatList as $subcatValue) {
					$compressedList .= '~cat' . $headCat . '_' . $subcatValue . ',';
				} # foreach
			} # foreach
		} # if

		SpotTiming::stop(__FUNCTION__, array($compressedList));

		return $compressedList;
	} # compressCategorySelection

	/*
	 * Converts an array with search terms (tree, type, valus) to an SQL statement
	 * to be glued to an SQL WHERE query
	 */
	function filterToQuery($search, $sort, $currentSession, $indexFilter) {
		SpotTiming::start(__FUNCTION__);
		
		$isUnfiltered = false;
		
		$categoryList = array();
		$categorySql = array();
		
		$strongNotList = array();
		$strongNotSql = array();
		
		$filterValueList = array();
		$filterValueSql = array();
		
		$additionalFields = array();
		$additionalTables = array();
		$additionalJoins = array();
		$sortFields = array();
		
		# Take the easy way out of no filters have been given
		if (empty($search)) {
			return array('filter' => '',
						 'search' => array(),
					     'additionalFields' => array(),
						 'additionalTables' => array(),
						 'additionalJoins' => array(),
						 'categoryList' => array(),
						 'strongNotList' => array(),
					     'filterValueList' => array(),
						 'unfiltered' => false,
					     'sortFields' => array(array('field' => 'stamp', 'direction' => 'DESC', 'autoadded' => true, 'friendlyname' => null)));
		} # if

		/*
		 * Process the parameters in $search, legacy parameters are converted
		 * to a common format by prepareFilterValues, this list is then
		 * converted to SQL
		 */
		$filterValueList = $this->prepareFilterValues($search);
		list($filterValueSql, $additionalFields, $additionalTables, $additionalJoins, $sortFields) = $this->filterValuesToSql($filterValueList, $currentSession);

		/*
		 * When asked to forget all category filters (and only search for a word/typefilter)
		 * we simply reset the filter by overwriting $search with $indexfilter
		 */
		if ((isset($search['unfiltered'])) && (($search['unfiltered'] === 'true'))) {
			$search = array_merge($search, $indexFilter);
			$isUnfiltered = true;
		} # if

		/*
		 * If a tree was given, convert it to subcategories etc. 
		 * prepareCategorySelection() makes sure all categories eventually
		 * are in a common format
		 */		
		if (!empty($search['tree'])) {
			# explode the dynaList
			$dynaList = explode(',', $search['tree']);
			list($categoryList, $strongNotList) = $this->prepareCategorySelection($dynaList);

			# and convert to SQL
			$categorySql = $this->categoryListToSql($categoryList);
			$strongNotSql = $this->strongNotListToSql($strongNotList);
		} # if

		# Check for an explicit sorting convention
		$sortFields = $this->prepareSortFields($sort, $sortFields);

		$endFilter = array();
		if (!empty($categorySql)) { 
			$endFilter[] = '(' . join(' OR ', $categorySql) . ') ';
		} # if
		if (!empty($filterValueSql['AND'])) {
			$endFilter[] = '(' . join(' AND ', $filterValueSql['AND']) . ') ';
		} # if
		if (!empty($filterValueSql['OR'])) {
			$endFilter[] = '(' . join(' OR ', $filterValueSql['OR']) . ') ';
		} # if
		$endFilter[] = join(' AND ', $strongNotSql);
		$endFilter = array_filter($endFilter);
		
		SpotTiming::stop(__FUNCTION__, array(join(" AND ", $endFilter)));
		return array('filter' => join(" AND ", $endFilter),
					 'categoryList' => $categoryList,
					 'unfiltered' => $isUnfiltered,
					 'strongNotList' => $strongNotList,
					 'filterValueList' => $filterValueList,
					 'additionalFields' => $additionalFields,
					 'additionalTables' => $additionalTables,
					 'additionalJoins' => $additionalJoins,
					 'sortFields' => $sortFields);
	} # filterToQuery
	
	public function setActiveRetriever($b) {
		$this->_activeRetriever = $b;
	} # setActiveRetriever

} # class SpotsOverview
