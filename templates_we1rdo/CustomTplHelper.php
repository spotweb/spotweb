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
	
	function filter2color($filter) {
		if(!$filter) {
			return;
		} switch($filter) {
			case stristr($filter,"cat0"): return 'blue'; break;
			case stristr($filter,"cat1"): return 'orange'; break;
			case stristr($filter,"cat2"): return 'green'; break;
			case stristr($filter,"cat3"): return 'red'; break;
		} #switch
	}
	
} # class CustomTplHelper