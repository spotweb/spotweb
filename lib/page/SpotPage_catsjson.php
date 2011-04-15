<?php
class SpotPage_catsjson extends SpotPage_Abs {


	function render() {
		# stuur een expires header zodat dit een jaar of 10 geldig is
		Header("Cache-Control: public");
		Header("Expires: " . gmdate("D, d M Y H:i:s", (time() + (86400 * 3650))) . " GMT");
		Header("Pragma: ");

		$this->categoriesToJson();
	} # render
	
	/*
	 * Geeft JSON terug interpreteerbaar voor DynaTree om de categorylist als boom
	 * te kunnen weergeven
	 */
	function categoriesToJson() {
		echo "[";
		
		$hcatList = array();
		foreach(SpotCategories::$_head_categories as $hcat_key => $hcat_val) {
			$hcatTmp = '{"title": "' . $hcat_val . '", "isFolder": true, "key": "cat' . $hcat_key . '",	"children": [' ;
					
			$subcatDesc = array();
			foreach(SpotCategories::$_subcat_descriptions[$hcat_key] as $sclist_key => $sclist_desc) {
				$subcatTmp = '{"title": "' . $sclist_desc . '", "isFolder": true, "hideCheckbox": true, "key": "cat' . $hcat_key . '_' . $sclist_key . '", "unselectable": false, "children": [';
				# echo ".." . $sclist_desc . " <br>";

				$catList = array();
				foreach(SpotCategories::$_categories[$hcat_key][$sclist_key] as $key => $val) {
					if ((strlen($val) != 0) && (strlen($key) != 0)) {
						$catList[] = '{"title": "' . $val . '", "icon": false, "key":"'. 'cat' . $hcat_key . '_' . $sclist_key.$key .'"}';
					} # if
				} # foreach
				$subcatTmp .= join(",", $catList);
				
				$subcatDesc[] = $subcatTmp . "]}";
			} # foreach

			$hcatList[] = $hcatTmp . join(",", $subcatDesc) . "]}";
		} # foreach	
		
		echo join(",", $hcatList);
		echo "]";
	} # categoriesToJson

	
} # class SpotPage_catjson
