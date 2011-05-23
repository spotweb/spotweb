<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor
	
	/*
	 * Geef een volledig Spot array terug
	 */
	function getFullSpot($msgId, $ourUserId, $nntp) {
		$fullSpot = $this->_db->getFullSpot($msgId, $ourUserId);

		if (empty($fullSpot)) {
			# Vraag de volledige spot informatie op -- dit doet ook basic
			# sanity en validatie checking
			$fullSpot = $nntp->getFullSpot($msgId);
			$this->_db->addFullSpot($fullSpot);
			
			# we halen de fullspot opnieuw op zodat we de 'xover' informatie en de 
			# niet xover informatie in 1 hebben
			$fullSpot = $this->_db->getFullSpot($msgId, $ourUserId);
		} # if

		$spotParser = new SpotParser();
		$fullSpot = array_merge($spotParser->parseFull($fullSpot['fullxml']), $fullSpot);
		
		/*
		 * Als je een fullspot ophaalt, maar er is nog gen 'spot' entry, dan blijf je een
		 * lege spot terugkrijgen omdat de join misgaat. Omdat dit verwarring op kan leveren
		 * gooien we dan een exception
		 */
		if (empty($fullSpot)) {
			throw new Exception("Spot is not in our Spotweb database");
		} # if
		
		return $fullSpot;
	} # getFullSpot

	function addToSeenList($msgId, $ourUserId) {
		$this->_db->addToSpotStateList(SpotDb::spotstate_Seen, $msgId, $ourUserId);
	}

	/*
	 * Callback functie om enkel verified 'iets' terug te geven
	 */
	function cbVerifiedOnly($x) {
		return $x['verified'];
	} # cbVerifiedOnly
	
	/*
	 * Geef de lijst met comments terug 
	 */
	function getSpotComments($msgId, $nntp, $start, $length) {
		if (!$this->_settings->get('retrieve_comments')) {
			return array();
		} # if
	
		# Bereken wat waardes zodat we dat niet steeds moeten doen
		$totalCommentsNeeded = ($start + $length);
		
		SpotTiming::start(__FUNCTION__);

		# vraag een lijst op met comments welke in de database zitten en
		# als er een fullcomment voor bestaat, vraag die ook meteen op
		$fullComments = $this->_db->getCommentsFull($msgId);
		
		# Nu gaan we op zoek naar het eerste comment dat nog volledig opgehaald
		# moet worden. Niet verified comments negeren we.
		$haveFullCount = 0;
		$lastHaveFullOffset = -1;
		$retrievedVerified = 0;
		for ($i = 0; $i < count($fullComments); $i++) {
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
					SpotTiming::start(__FUNCTION__);
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
			$this->_db->addCommentsFull($newComments);
			
			# en voeg de oude en de nieuwe comments samen
			$fullComments = $this->_db->getCommentsFull($msgId);
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
	 * Geef de NZB file terug
	 */
	function getNzb($msgIdList, $nntp) {
		return $nntp->getNzb($msgIdList);
	} # getNzb

	/*
	 * Laad de spots van af positie $stat, maximaal $limit spots.
	 *
	 * $parsedSearch is een array met velden, filters en sorteringen die 
	 * alles bevat waarmee SpotWeb kan filteren. De hierin sorteringen worden
	 * eerst uitgevoerd waarna de user-defined sortering wordt bijgeplakt
	 */
	function loadSpots($ourUserId, $start, $limit, $parsedSearch, $sort) {
		SpotTiming::start(__FUNCTION__);
		# als er geen sorteer veld opgegeven is, dan sorteren we niet
		if ($sort['field'] == '') {
			$sort = array();
		} # if
		
		# welke manier willen we sorteren?
		$sortFields = array('category', 'poster', 'title', 'stamp', 'subcata', 'spotrating', 'commentcount');
		if ((!isset($sort['field'])) || (array_search($sort['field'], $sortFields) === false)) {
			# We sorteren standaard op stamp, maar alleen als er vanuit de query
			# geen expliciete sorteermethode is meegegeven
			if (empty($parsedSearch['sortFields'])) {
				$sort = array();
				$sort['field'] = 'stamp';
				$sort['direction'] = 'DESC';
			} # if
		} else {
			if ($sort['direction'] != 'DESC') {
				$sort['direction'] = 'ASC';
			} # if
		} # else
		
		# en haal de daadwerkelijke spots op
		$spotResults = $this->_db->getSpots($ourUserId, $start, $limit, $parsedSearch, $sort, false);
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
	 * Converteer een array met search termen (tree, type en value) naar een SQL
	 * statement dat achter een WHERE geplakt kan worden.
	 */
	function filterToQuery($search, $currentSession) {
		SpotTiming::start(__FUNCTION__);
		$filterList = array();
		$strongNotList = array();
		$dyn2search = array();
		$additionalFields = array();
		$sortFields = array();

		# dont filter anything
		if (empty($search)) {
			return array('filter' => '',
					 'search' => array(),
					 'additionalFields' => array(),
					 'sortFields' => array());
		} # if

		# We hebben twee soorten filters:
		#		- Oude type waarin je een search[type] hebt met als waarden stamp,titel,tag etc en search[text] met 
		#		  de waarde waar je op wilt zoeken. Dit beperkt je tot maximaal 1 type filter wat het lastig maakt.
		#
		#		- Nieuw type waarin je een search[value] array hebt, hierin zitten values in de vorm: type:value, dus
		#		  bijvoorbeeld new:0 (nieuwe posts) of tag:spotweb. 
		#
		# We converteren oude type zoekopdrachten automatisch naar het nieuwe type.
		#
		if (isset($search['type'])) {
			if (!isset($search['text'])) {
				$search['text'] = '';
			} # if
			
			$search['value'][] = $search['type'] . ':' . $search['text'];
			unset($search['type']);
		} # if
		
		if ((!isset($search['value'])) || (!is_array($search['value']))) {
			$search['value'] = array();
		} # if

		# en we converteren het nieuwe type (field:value) naar een array zodat we er makkelijk door kunnen lopen
		$filterValueList = array();
		foreach($search['value'] as $value) {
			$tmpFilter = explode(':', $value);
			$filterValueList[$tmpFilter[0]] = join(":", array_slice($tmpFilter, 1));
		} # for
		$search['filterValues'] = $filterValueList;
		
		# als er gevraagd om de filters te vergeten (en enkel op het woord te zoeken)
		# resetten we gewoon de boom
		if ((isset($search['unfiltered'])) && (($search['unfiltered'] === 'true'))) {
			$search = array_merge($search, $this->_settings->get('index_filter'));
		} # if
		
		# convert the dynatree list to a list 
		if (!empty($search['tree'])) {
			# explode the dynaList
			$dynaList = explode(',', $search['tree']);
			
			# fix de tree variable zodat we dezelfde parameters ondersteunen als de JS
			$newTreeQuery = '';
			for($i = 0; $i < count($dynaList); $i++) {
				# De opgegeven category kan in twee soorten voorkomen:
				#     cat1_a			==> Alles van cat1, en daar alles van 'a' selecteren
				#	  cat1				==> Heel cat1 selecteren
				#
				# Omdat we in deze code de dynatree emuleren, voeren we deze lelijke hack uit.
				if ((strlen($dynaList[$i]) == 6) || (strlen($dynaList[$i]) == 4)) {
					$hCat = (int) substr($dynaList[$i], 3, 1);
					
					# was een subcategory gespecificeerd?
					if (strlen($dynaList[$i]) == 6) {
						$subCatSelected = substr($dynaList[$i], 5);
					} else {
						$subCatSelected = '*';
					} # else

					#
					# creeer een string die alle subcategories bevat
					#
					# we loopen altijd door alle subcategorieen heen zodat we zowel voor complete category selectie
					# als voor enkel subcategory selectie dezelfde code kunnen gebruiken.
					#
					$tmpStr = '';
					foreach(SpotCategories::$_categories[$hCat] as $subCat => $subcatValues) {
					
						if (($subCat == $subCatSelected) || ($subCatSelected == '*')) {
							foreach(SpotCategories::$_categories[$hCat][$subCat] as $x => $y) {
								$tmpStr .= ",cat" . $hCat . "_" . $subCat . $x;
							} # foreach
						} # if
					} # foreach

					$newTreeQuery .= $tmpStr;
				} elseif (substr($dynaList[$i], 0, 1) == '!') {
					# als het een NOT is, haal hem dan uit de lijst
					$newTreeQuery = str_replace(substr($dynaList[$i], 1) . ",", "", $newTreeQuery);
				} elseif (substr($dynaList[$i], 0, 1) == '~') {
					# als het een STRONG NOT is, zorg dat hij in de lijst blijft omdat we die moeten
					# meegeven aan de nextpage urls en dergelijke.
					$newTreeQuery .= "," . $dynaList[$i];
					
					# en voeg hem toe aan een strong NOT list (~cat0_d12)
					$strongNotTmp = explode("_", $dynaList[$i]);
					$strongNotList[(int) substr($strongNotTmp[0], 4)][] = $strongNotTmp[1];
				} else {
					$newTreeQuery .= "," . $dynaList[$i];
				} # else
			} # foreach
			if ($newTreeQuery[0] == ",") { $newTreeQuery = substr($newTreeQuery, 1); }

			# explode the dynaList
			$search['tree'] = $newTreeQuery;
			$dynaList = explode(',', $search['tree']);

			# en fix the list
			foreach($dynaList as $val) {
				if (substr($val, 0, 3) == 'cat') {
					# 0e element is hoofdcategory
					# 1e element is category
					$val = explode('_', (substr($val, 3) . '_'));

					$catVal = $val[0];
					$subCatIdx = substr($val[1], 0, 1);
					$subCatVal = substr($val[1], 1);

					if (count($val) >= 3) {
						$dyn2search['cat'][$catVal][$subCatIdx][] = $subCatVal;
					} # if
				} # if
			} # foreach
		} # if

		# Add a list of possible head categories
		if ((isset($dyn2search['cat'])) && (is_array($dyn2search['cat']))) {
			$filterList = array();

			foreach($dyn2search['cat'] as $catid => $cat) {
				$catid = (int) $catid;
				$tmpStr = "((category = " . (int) $catid . ")";
				
				# Now start adding the sub categories
				if ((is_array($cat)) && (!empty($cat))) {
					#
					# uiteraard is een LIKE query voor category search niet super schaalbaar
					# maar omdat deze webapp sowieso niet bedoeld is voor grootschalig gebruik
					# moet het meer dan genoeg zijn
					#
					$subcatItems = array();
					foreach($cat as $subcat => $subcatItem) {
						$subcatValues = array();
						
						foreach($subcatItem as $subcatValue) {
							# category a en z mogen maar 1 keer voorkomen, dus dan kunnen we gewoon
							# equality ipv like doen
							if (in_array($subcat, array('a', 'z'))) {
								$subcatValues[] = "(subcat" . $subcat . " = '" . $subcat . $subcatValue . "|') ";
							} elseif (in_array($subcat, array('b', 'c', 'd'))) {
								$subcatValues[] = "(subcat" . $subcat . " LIKE '%" . $subcat . $subcatValue . "|%') ";
							} # if
						} # foreach
						
						# voeg de subfilter values (bv. alle formaten films) samen met een OR
						$subcatItems[] = " (" . join(" OR ", $subcatValues) . ") ";
					} # foreach subcat

					# voeg de category samen met de diverse subcategory filters met een OR, bv. genre: actie, type: divx.
					$tmpStr .= " AND (" . join(" AND ", $subcatItems) . ") ";
				} # if
				
				# close the opening parenthesis from this category filter
				$tmpStr .= ")";
				$filterList[] = $tmpStr;
			} # foreach
		} # if

		# Add a list of possible text searches
		$textSearch = array();
		foreach($search['filterValues'] as $searchType => $searchValue) {
			# als het een pure textsearch is, die we potentieel kunnen optimaliseren,
			# voer dan dit pad uit
			if (in_array($searchType, array('Tag', 'Poster', 'UserID', 'Titel'))) {
				$field = '';

				switch($searchType) {
					case 'Tag'		: $field = 'tag'; break;
					case 'Poster'	: $field = 'poster'; break;
					case 'UserID'	: $field = 'userid'; break;
					case 'Titel'	: $field = 'title'; break;
				} # switch
				
				if (!empty($field) && !empty($searchValue)) {
					$parsedTextQueryResult = $this->_db->createTextQuery($field, $searchValue);
					$textSearch[] = ' (' . $parsedTextQueryResult['filter'] . ') ';
					
					# We voegen deze extended textqueryies toe aan de filterlist als
					# relevancy veld, hiermee kunnen we dan ook zoeken op de relevancy
					# wat het net wat interessanter maakt
					if ($parsedTextQueryResult['sortable']) {
						# We zouden in theorie meerdere van deze textsearches kunnen hebben, dan 
						# sorteren we ze in de volgorde waarop ze binnenkwamen 
						$tmpSortCounter = count($additionalFields);
						
						$additionalFields[] = $parsedTextQueryResult['filter'] . ' AS searchrelevancy' . $tmpSortCounter;
						$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
											  'direction' => 'DESC');
					} # if
				} # if
			} else {
				# Anders is het geen textsearch maar een vergelijkings operator, 
				# eerst willen we de vergelijking eruit halen.
				#
				# De filters komen in de vorm: Veldnaam:Operator:Waarde, bv: 
				#   filesize:>=:4000000
				$tmpFilter = explode(":", $searchValue);

				if (count($tmpFilter) >= 2) {
					$filterOperator = $tmpFilter[0];
					$searchValue = join(":", array_slice($tmpFilter, 1));

					# valideer eerst de operatoren
					if (!in_array($filterOperator, array('>', '<', '>=', '<=', '='))) {
						break;
					} # if

					# en valideer dan de zoekvelden
					$filterFieldMapping = array('filesize' => 's.filesize',
										  'date' => 's.stamp',
										  'moderated' => 's.moderated');
					if (!isset($filterFieldMapping[$searchType])) {
						break;
					} # if

					if ($searchType == 'date') {
						$searchValue = date("U",  strtotime($searchValue));
					} elseif ($searchType == 'filesize' && is_numeric($searchValue) == false) {
						$val = trim($searchValue);
						$last = strtolower($val[strlen($val)-1]);
						switch($last) {
							case 'g':
								$val *= 1024;
							case 'm':
								$val *= 1024;
							case 'k':
								$val *= 1024;
						} # switch
						$searchValue = $val;
					} # if

					# en creeer de query string
					$textSearch[] = ' (' . $filterFieldMapping[$searchType] . ' ' . $filterOperator . ' '  . $this->_db->safe($searchValue) . ') ';
				} # if
			} # if
		} # foreach

		$sortFields[] = array('field' => 'stamp', 'direction' => 'DESC');

		# strong nots
		$notSearch = '';
		if (!empty($strongNotList)) {
			$notSearchTmp = array();
			
			foreach(array_keys($strongNotList) as $strongNotCat) {
				foreach($strongNotList[$strongNotCat] as $strongNotSubcat) {
					$subcat = $strongNotSubcat[0];

					# category a en z mogen maar 1 keer voorkomen, dus dan kunnen we gewoon
					# equality ipv like doen
					if (in_array($subcat, array('a', 'z'))) { 
						$notSearchTmp[] = "((Category <> " . (int) $strongNotCat . ") OR (subcat" . $subcat . " <> '" . $this->_db->safe($strongNotSubcat) . "|'))";
					} elseif (in_array($subcat, array('b', 'c', 'd'))) { 
						$notSearchTmp[] = "((Category <> " . (int) $strongNotCat . ") OR (NOT subcat" . $subcat . " LIKE '%" . $this->_db->safe($strongNotSubcat) . "|%'))";
					} # if
				} # foreach				
			} # forEach

			$notSearch = join(' AND ', $notSearchTmp);
		} # if

		# New spots
		if (isset($search['filterValues']['New'])) {
			if ($this->_settings->get('auto_markasread') == true) {
				$newSpotsSearchTmp[] = '(s.stamp > ' . (int) $this->_db->safe( max($currentSession['user']['lastvisit'],$currentSession['user']['lastread']) ) . ')';
			} else {
				$newSpotsSearchTmp[] = '(s.stamp > ' . (int) $this->_db->safe($currentSession['user']['lastread']) . ')';
			} # else
			$newSpotsSearchTmp[] = '(l.seen IS NULL)';
			$newSpotsSearch = join(' AND ', $newSpotsSearchTmp);
		} # if

		# Spots in SpotStateList
		$listFilter = array();
		if (isset($search['filterValues']['Downloaded'])) {
			$listFilter[] = ' (l.download IS NOT NULL)';
		} elseif (isset($search['filterValues']['Watch'])) {
			$listFilter[] = ' (l.watch IS NOT NULL)';
		} elseif (isset($search['filterValues']['Seen'])) {
			$listFilter[] = ' (l.seen IS NOT NULL)';
		} # if

		$endFilter = array();
		if (!empty($filterList)) {
			$endFilter[] = '(' . join(' OR ', $filterList) . ') ';
		} # if
		if (!empty($textSearch)) {
			$endFilter[] = join(' AND ', $textSearch);
		} # if
		if (!empty($listFilter)) {
			$endFilter[] = join(' AND ', $listFilter);
		} # if
		if (!empty($notSearch)) {
			$endFilter[] = $notSearch;
		} # if
		if (!empty($newSpotsSearch)) {
			$endFilter[] = $newSpotsSearch;
		} # if
		
		SpotTiming::stop(__FUNCTION__, array(join(" AND ", $endFilter)));
		return array('filter' => join(" AND ", $endFilter),
					 'search' => $search,
					 'additionalFields' => $additionalFields,
					 'sortFields' => $sortFields);
	} # filterToQuery
	
	
} # class SpotOverview
