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
	function getSpotComments($msgId, $nntp) {
		if (!$this->_settings['retrieve_comments']) {
			return array();
		} # if
	
		# Vraag een lijst op met alle comments messageid's
		$commentList = $this->_db->getCommentRef($msgId);
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
			# We trekken de lijst van subcategorieen uitelkaar 
			$spotList[$i]['subcatlist'] = explode("|", 
							$spotList[$i]['subcata'] . 
							$spotList[$i]['subcatb'] . 
							$spotList[$i]['subcatc'] . 
							$spotList[$i]['subcatd']);
		} # foreach

		return array('list' => $spotList, 
					 'hasmore' => $hasMore);
	} # loadSpots()

	
	
	/*
	 * Converteer een array met search termen (tree, type en text) naar een SQL
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
					# als het een STRONG NOT is, haal hem dan uit de lijst
					$newTreeQuery = str_replace(substr($dynaList[$i], 1) . ",", "", $newTreeQuery);
					
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
				$tmpStr = "((category = " . $catid . ")";
				
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
		$textSearch = '';
		if ((!empty($search['text'])) && ((isset($search['type'])))) {
			$field = 'title';
		
			if ($search['type'] == 'Tag') {
				$field = 'tag';
			} else if ($search['type'] == 'Poster') {
				$field = 'poster';
			} else if ($search['type'] == 'UserID') {
				$field = 'userid';
			} # else
			
			switch($this->_settings['db']['engine']) {
				# disabled vanwege https://github.com/spotweb/spotweb/issues#issue/364
				#     case 'mysql'	: $textSearch .= " MATCH($field) AGAINST('" . $this->_db->safe($search['text']) . "' IN BOOLEAN MODE)"; break;
				default			: $textSearch .= ' (' . $field . " LIKE '%" . $this->_db->safe($search['text']) . "%')"; break;
			} # switch
		} # if

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
		if (isset($search['type']) && $search['type'] == 'New') {
			if (isset($_SESSION['last_visit'])) {
				$newSpotsSearchTmp[] = ' (s.stamp > ' . (int) $this->_db->safe($_SESSION['last_visit']) . ')';
			} # if
			
			$newSpotsSearch = join(' AND ', $newSpotsSearchTmp);
		} # if

		# Downloaded spots
		if (isset($search['type']) && $search['type'] == 'Downloaded') {
			$textSearch .= ' (d.stamp IS NOT NULL)';
		} # if

		$endFilter = array();
		if (!empty($filterList)) {
			$endFilter[] = '(' . join(' OR ', $filterList) . ') ';
		} # if
		if (!empty($textSearch)) {
			$endFilter[] = $textSearch;
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
