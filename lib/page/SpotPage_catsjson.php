<?php
class SpotPage_catsjson extends SpotPage_Abs {
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		
		$this->sendContentTypeHeader();

		$this->_params = $params;
	} # ctor
	
	/*
	 * render a page 
	 */
	function render() {
		if ($this->_params['rendertype'] == 'tree') {
			$this->categoriesToJson();
		} else {
			$this->renderSelectBox();
		} # else
	} # render
	
	/*
	 * Render the JSON specifically for one selectbox, no
	 * logic whatsoever
	 */
	function renderSelectBox() {
		# stuur een 'always cache' header zodat dit gecached kan worden
		$this->sendExpireHeaders(false);
		
		$category = $this->_params['category'];
		$genre = $this->_params['subcatz'];
		
		/* Validate the selected category */
		if (!isset(SpotCategories::$_head_categories[$category])) {
			return '';
		} # if

		$returnArray = array();
		$scType = 'z';
		
		switch($this->_params['rendertype']) {
			case 'subcatz'	: {
					$scType = $this->_params['rendertype'][6];
					
					foreach(SpotCategories::$_categories[$category]['z'] as $key => $value) {
						$returnArray[$key] = $value;
					} # foreach
			} # case subcatz

			case 'subcata'  :
			case 'subcatb'  :
			case 'subcatc'  :
			case 'subcatd'	: {
					$scType = $this->_params['rendertype'][6];
					
					foreach(SpotCategories::$_categories[$category][$scType] as $key => $value) {
						if (in_array('z'. $genre, $value[1])) {
							$returnArray['cat' . $category . '_z' . $genre . '_' . $scType . $key] = $value[0];
						} # if
					} # foreach
			} # case subcatz
		} # switch
		
		echo json_encode(
					array('title' => SpotCategories::$_subcat_descriptions[$category][$scType],
					      'items' => $returnArray));
	} # renderSelectBox
	
	/*
	 * Geeft JSON terug interpreteerbaar voor DynaTree om de categorylist als boom
	 * te kunnen weergeven
	 */
	function categoriesToJson() {
		# stuur een expires header zodat dit niet gecached is, hierin staat 
		# state van de boom
		$this->sendExpireHeaders(true);

		/* First parse the search string so we know which items to select and which not */
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$parsedSearch = $spotsOverview->filterToQuery($this->_params['search'], 
													  array(),
													  $this->_currentSession,
													  $spotUserSystem->getIndexFilter($this->_currentSession['user']['userid']));
		if ($this->_params['disallowstrongnot']) {
			$parsedSearch['strongNotList'] = '';
		} # if
		$compressedCatList = ',' . $spotsOverview->compressCategorySelection($parsedSearch['categoryList'], $parsedSearch['strongNotList']);
//error_log($this->_params['search']['tree']);
//var_dump($parsedSearch);
//var_dump($compressedCatList);
//die();

		echo "[";
		
		$hcatList = array();
		foreach(SpotCategories::$_head_categories as $hcat_key => $hcat_val) {
			# The uer can opt to only show a specific category, if so, skip all others
			if (($hcat_key != $this->_params['category']) && ($this->_params['category'] != '*')) {
				continue;
			} # if
			
			# If the user choose to show only one category, we dont want the category item itself
			if ($this->_params['category'] == '*') {
				$hcatTmp = '{"title": "' . $hcat_val . '", "isFolder": true, "key": "cat' . $hcat_key . '",	"children": [' ;
			} # if
			$typeCatDesc = array();

			if (isset(SpotCategories::$_categories[$hcat_key]['z'])) {
				foreach(SpotCategories::$_categories[$hcat_key]['z'] as $type_key => $type_value) {
			
					if (($type_key !== 'z') && (($this->_params['subcatz'] == $type_key) || ($this->_params['subcatz'] == '*'))) {
						# Now determine wether we need to enable the checkbox
						$isSelected = strpos($compressedCatList, ',cat' . $hcat_key . '_z' . $type_key . ',') !== false ? "true" : "false";

						# Is this strongnot?
						$isStrongNot = strpos($compressedCatList, ',~cat' . $hcat_key . '_z' . $type_key . ',') !== false ? true : false;
						if ($isStrongNot) {
							$isStrongNot = '"strongnot": true, "addClass": "strongnotnode", ';
							$isSelected = 'true';
						} else {
							$isStrongNot = '';
						} # if

						# If the user choose to show only one categortype, we dont want the categorytype item itself
						if ($this->_params['subcatz'] == '*') {
							$typeCatTmp = '{"title": "' . $type_value . '", "isFolder": true, ' . $isStrongNot . ' "select": ' . $isSelected . ', "hideCheckbox": false, "key": "cat' . $hcat_key . '_z' . $type_key . '", "unselectable": false, "children": [';
						} # if
					} # if
					
					$subcatDesc = array();
					foreach(SpotCategories::$_subcat_descriptions[$hcat_key] as $sclist_key => $sclist_desc) {
						if (($sclist_key !== 'z') && (($this->_params['subcatz'] == $type_key) || ($this->_params['subcatz'] == '*'))) {

							# We inherit the strongnode from our parent
							$isStrongNot = strpos($compressedCatList, ',~cat' . $hcat_key . '_z' . $type_key . ',') !== false ? true : false;
							if ($isStrongNot) {
								$isStrongNot = '"strongnot": true, "addClass": "strongnotnode", ';
								$isSelected = 'true';
							} else {
								$isStrongNot = '';
							} # if

							$subcatTmp = '{"title": "' . $sclist_desc . '", "isFolder": true, ' . $isStrongNot . ' "hideCheckbox": true, "key": "cat' . $hcat_key . '_z' . $type_key . '_' . $sclist_key . '", "unselectable": false, "children": [';
							# echo ".." . $sclist_desc . " <br>";

							$catList = array();
							foreach(SpotCategories::$_categories[$hcat_key][$sclist_key] as $key => $valTmp) {
								if (in_array('z' . $type_key, $valTmp[1])) {
									$val = $valTmp[0];
									
									if ((strlen($val) != 0) && (strlen($key) != 0)) {
										# Now determine wether we need to enable the checkbox
										$isSelected = strpos($compressedCatList, ',cat' . $hcat_key . '_z' . $type_key . '_' . $sclist_key.$key . ',') !== false ? true : false;
										$parentSelected = strpos($compressedCatList, ',cat' . $hcat_key . '_z' . $type_key .',') !== false ? true : false;
										$isSelected = ($isSelected || $parentSelected) ? 'true' : 'false';
										
										/*
										 * Is this strongnot?
										 */
										$isStrongNot = strpos($compressedCatList, ',~cat' . $hcat_key . '_z' . $type_key . ',') !== false ? true : false;
										if (!$isStrongNot) { 
											$isStrongNot = strpos($compressedCatList, ',~cat' . $hcat_key . '_z' . $type_key . '_' . $sclist_key.$key . ',') !== false ? true : false;
										} # if
										if ($isStrongNot) {
											$isStrongNot = '"strongnot": true, "addClass": "strongnotnode", ';
											$isSelected = 'true';
										} else {
											$isStrongNot = '';
										} # if
	
										$catList[] = '{"title": "' . $val . '", "icon": false, "select": ' . $isSelected . ', ' . $isStrongNot . '"key":"'. 'cat' . $hcat_key . '_z' . $type_key . '_' . $sclist_key.$key .'"}';
									} # if
								} # if
							} # foreach
							$subcatTmp .= join(",", $catList);
							
							$subcatDesc[] = $subcatTmp . "]}";
						} # if
					} # foreach

 					if ($type_key !== 'z') {
						# If the user choose to show only one categortype, we dont want the categorytype item itself
						if ($this->_params['subcatz'] == '*') {
							$typeCatDesc[] = $typeCatTmp . join(",", $subcatDesc) . "]}";
						} else {
							if (!empty($subcatDesc)) {
								$typeCatDesc[] = join(",", array_filter($subcatDesc));
							} # if
						} # else
					} else {
						$typeCatDesc[] = join(",", $subcatDesc);
					} # else
				} # foreach
				
			} # foreach

			# If the user choose to show only one category, we dont want the category item itself
			if ($this->_params['category'] == '*') {
				$hcatList[] = $hcatTmp . join(",", $typeCatDesc) . "]}";
			} else {
				$hcatList[] = join(",", $typeCatDesc);
			} # if
		} # foreach	
		
		echo join(",", $hcatList);
		echo "]";
	} # categoriesToJson

	
} # class SpotPage_catjson
