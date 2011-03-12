<?php
class CustomTplHelper extends SpotTemplateHelper {

	function cat2color($spot) {
		switch( (int) $spot['category']) {
			case 0: return 'blue'; break;
			case 1: return 'orange'; break;
			case 2: return 'green'; break;
			case 3: return 'red'; break;
		} # switch
		
		return '-';
	} # cat2color
	
	function getSitePath() {
		$site = $_SERVER['SERVER_NAME'];
		$source = $_SERVER['REQUEST_URI'];
		$getpath = explode('/',$source);
		$setpath = $site . "/" . $getpath[1] . "/";
		
		return $setpath;
	} # getSitePath
	
	
} # class CustomTplHelper