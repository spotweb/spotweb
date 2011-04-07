<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_settings;

	function __construct($db, $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor
	
	/*
	 * Geef een volledig Spot array terug
	 */
	function getFullSpot($msgId, $nntp) {
		$fullSpot = $this->_db->getFullSpot($msgId);
		
		if (empty($fullSpot)) {
			# Vraag de volledige spot informatie op -- dit doet ook basic
			# sanity en validatie checking
			$fullSpot = $nntp->getFullSpot($msgId);
			$this->_db->addFullSpot($fullSpot);
			
			# we halen de fullspot opnieuw op zodat we de 'xover' informatie en de 
			# niet xover informatie in 1 hebben
			$fullSpot = $this->_db->getFullSpot($msgId);
		} # if
		
		$spotParser = new SpotParser();
		$fullSpot = array_merge($spotParser->parseFull($fullSpot['fullxml']), $fullSpot);
		return $fullSpot;
	} # getFullSpot
	
	/*
	 * Geef de lijst met comments terug 
	 */
	function getSpotComments($msgId, $nntp, $start, $length) {
		if (!$this->_settings['retrieve_comments']) {
			return array();
		} # if
	
		# Vraag een lijst op met alle comments messageid's
		$commentList = $this->_db->getCommentRef($msgId);
		
		# haal enkel die comments op wat we denken nodig te hebben
		# volgens de parameters
		if (($start > 0) || ($length > 0)) {
			$commentList = array_slice($commentList, $start, $length);
		} # if
		
		# en haal de comments zelf op
		return $nntp->getComments($commentList);
	} # getSpotComments()
	
	/* 
	 * Geef de NZB file terug
	 */
	function getNzb($msgIdList, $nntp) {
		return $nntp->getNzb($msgIdList);
	} # getNzb

	/*
	 * Geeft het overzichts van spots in de watchlist terug
	 */
	function loadWatchlist($sort) {
		# welke manier willen we sorteren?
		$sortFields = array('category', 'poster', 'title', 'stamp', 'subcata');
		if (array_search($sort['field'], $sortFields) === false) {
			$sort = array();
			$sort['field'] = 'stamp';
			$sort['direction'] = 'DESC';
		} else {
			if ($sort['direction'] != 'DESC') {
				$sort['direction'] = 'ASC';
			} # if
		} # else

		return $this->_db->getWatchList($sort);
	} # loadWatchList
	
	/*
	 * Laad de spots van af positie $stat, maximaal $limit spots.
	 *
	 * $sqlfilter is een kant en klaar SQL statement waarmee de spotweb
	 * filter ingesteld wordt;
	 */
	function loadSpots($start, $limit, $sqlFilter, $sort) {
		# welke manier willen we sorteren?
		$sortFields = array('category', 'poster', 'title', 'stamp', 'subcata');
		if (array_search($sort['field'], $sortFields) === false) {
			$sort = array();
			$sort['field'] = 'stamp';
			$sort['direction'] = 'DESC';
		} else {
			if ($sort['direction'] != 'DESC') {
				$sort['direction'] = 'ASC';
			} # if
		} # else

		# en haal de daadwerkelijke spotrs op
		$spotList = $this->_db->getSpots($start, $limit + 1, $sqlFilter, $sort, false);
		$spotCnt = count($spotList);

		# we vragen altijd 1 spot meer dan gevraagd, als die dan mee komt weten 
		# we dat er nog een volgende pagina is
		$hasMore = ($spotCnt > $limit);
			
		for ($i = 0; $i < $spotCnt; $i++) {
			# We forceren category naar een integer, sqlite kan namelijk een lege
			# string terug ipv een category nummer
			$spotList[$i]['category'] = (int) $spotList[$i]['category'];
			
			# We trekken de lijst van subcategorieen uitelkaar 
			$spotList[$i]['subcatlist'] = explode("|", 
							$spotList[$i]['subcata'] . 
							$spotList[$i]['subcatb'] . 
							$spotList[$i]['subcatc'] . 
							$spotList[$i]['subcatd'] . 
							$spotList[$i]['subcatz']);
		} # foreach

		return array('list' => $spotList, 
					 'hasmore' => $hasMore);
	} # loadSpots()

	
	
	/*
	 * Converteer een array met search termen (tree, type en value) naar een SQL
	 * statement dat achter een WHERE geplakt kan worden.
	 */
	function filterToQuery(&$search) {
		$filterList = array();
		$strongNotList = array();
		$dyn2search = array();

		# dont filter anything
		if (empty($search)) {
			return '';
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
			$search = array_merge($search, $this->_settings['index_filter']);
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
					$strongNotList[(int) substr($strongNotTmp[0], 3)][] = $strongNotTmp[1];
				} else {
					$newTreeQuery .= "," . $dynaList[$i];
				} # else
			} # foreach
			
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
							$subcatValues[] = "(subcat" . $subcat . " LIKE '%" . $subcat . $subcatValue . "|%') ";
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
					$textSearch[] = ' (' . $this->_db->createTextQuery($field, $searchValue) . ') ';
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
										  'date' => 's.stamp');
					if (!isset($filterFieldMapping[$searchType])) {
						break;
					} # if

					# en creeer de query string
					$textSearch[] = ' (' . $filterFieldMapping[$searchType] . ' ' . $filterOperator . ' '  . $this->_db->safe($searchValue) . ') ';
				} # if
			} # if
		} # foreach

		# strong nots
		$notSearch = '';
		if (!empty($strongNotList)) {
			$notSearchTmp = array();
			
			foreach(array_keys($strongNotList) as $strongNotCat) {
				foreach($strongNotList[$strongNotCat] as $strongNotSubcat) {
					$notSearchTmp[] = "((Category <> " . (int) $strongNotCat . ") OR (NOT subcatd LIKE '%" . $this->_db->safe($strongNotSubcat) . "|%'))";
				} # foreach				
			} # forEach

			$notSearch = join(' AND ', $notSearchTmp);
		} # if

		# New spots
		if (isset($search['filterValues']['New'])) {
			if (isset($_SESSION['last_visit'])) {
				$newSpotsSearchTmp[] = ' (s.stamp > ' . (int) $this->_db->safe($_SESSION['last_visit']) . ')';
			} # if
			$newSpotsSearch = join(' AND ', $newSpotsSearchTmp);
		} # if

		# Downloaded spots
		if (isset($search['filterValues']['Downloaded'])) {
			$textSearch[] = ' (d.stamp IS NOT NULL)';
		} # if

		$endFilter = array();
		if (!empty($filterList)) {
			$endFilter[] = '(' . join(' OR ', $filterList) . ') ';
		} # if
		if (!empty($textSearch)) {
			$endFilter[] = join(' AND ', $textSearch);
		} # if
		if (!empty($notSearch)) {
			$endFilter[] = $notSearch;
		} # if
		if (!empty($newSpotsSearch)) {
			$endFilter[] = $newSpotsSearch;
		} # if
		
		return join(" AND ", $endFilter);
	} # filterToQuery
	
	
} # class SpotOverview
