<?php
class SpotsOverview {
	private $_db;

	function __construct($db) {
		$this->_db = $db;
	} # ctor
	
	/*
	 * Laad de spots van af positie $stat, maximaal $limit spots.
	 *
	 * $sqlfilter is een kant en klaar SQL statement waarmee de spotweb
	 * filter ingesteld wordt;
	 */
	function loadSpots($start, $limit, $sqlFilter) {
		$spotList = $this->_db->getSpots($start, $limit + 1, $sqlFilter);
		$spotCnt = count($spotList);

		# we vragen altijd 1 spot meer dan gevraagd, als die dan mee komt weten 
		# we dat er nog een volgende pagina is
		$hasNextPage = ($spotCnt > $limit);
			
		for ($i = 0; $i < $spotCnt; $i++) {
			# We trekken de lijst van subcategorieen uitelkaar 
			$spotList[$i]['subcatlist'] = explode("|", 
							$spotList[$i]['subcata'] . 
							$spotList[$i]['subcatb'] . 
							$spotList[$i]['subcatc'] . 
							$spotList[$i]['subcatd']);
		} # foreach

		return array('list' => $spotList, 
					 'hasmore' => $hasNextPage);
	} # loadSpots()

	
	
	/*
	 * Converteer een array met search termen (tree, type en text) naar een SQL
	 * statement dat achter een WHERE geplakt kan worden.
	 */
	function filterToQuery($search) {
		$filterList = array();
		$dyn2search = array();

		# dont filter anything
		if (empty($search)) {
			return '';
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

		# Add a list of possible head categories
		$textSearch = '';
		if ((!empty($search['text'])) && ((isset($search['type'])))) {
			$field = 'title';
		
			if ($search['type'] == 'Tag') {
				$field = 'tag';
			} else if ($search['type'] == 'Poster') {
				$field = 'poster';
			} # else
			
			$textSearch .= ' (' . $field . " LIKE '%" . $this->_db->safe($search['text']) . "%')";
		} # if

		if (!empty($filterList)) {
			return '(' . (join(' OR ', $filterList) . ') ' . (empty($textSearch) ? "" : " AND " . $textSearch));
		} else {
			return $textSearch;
		} # if
	} # filterToQuery
	
	
} # class SpotOverview