<?php
class CustomTplHelper extends SpotTemplateHelper {

	function cat2color($spot) {
		if (is_array($spot)) {
			(int) $cat = $spot['category'];
		} else {
			$cat = (int) $spot;
		} # else
			
		switch( $cat ) {
			case 0: return 'blue'; break;
			case 1: return 'orange'; break;
			case 2: return 'green'; break;
			case 3: return 'red'; break;
		} # switch
		
		return '-';
	} # cat2color

	function filter2cat($s) {
		$cat = 0;
		if (stripos($s, 'cat0') !== false) {
			return "blue";
		} elseif (stripos($s, 'cat1') !== false) {
			return "orange";
		} elseif (stripos($s, 'cat1') !== false) {
			return "green";
		} elseif (stripos($s, 'cat1') !== false) {
			return "red";
		} # else
	} # filter2cat 
	
	function getSitePath() {
		$site = $_SERVER['SERVER_NAME'];
		$source = $_SERVER['REQUEST_URI'];
		$getpath = explode('/',$source);
		$setpath = $site . "/" . $getpath[1] . "/";
		
		return $setpath;
	} # getSitePath
	
	
} # class CustomTplHelper