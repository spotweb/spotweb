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
	
	function filter2cat($s) {
		$cat = 0;
		if (stripos($s, 'cat0') !== false) {
			return "blue";
		} elseif (stripos($s, 'cat1') !== false) {
			return "orange";
		} elseif (stripos($s, 'cat2') !== false) {
			return "green";
		} elseif (stripos($s, 'cat3') !== false) {
			return "red";
		} # else
	} # filter2cat
	
	function parseUBB($a) {
		$a = preg_replace("/\[b\](.*?)\[\/b\]/si","<b>\\1</b>",$a);
		$a = preg_replace("/\[i\](.*?)\[\/i\]/si","<i>\\1</i>",$a);
		$a = preg_replace("/\[u\](.*?)\[\/u\]/si","<u>\\1</u>",$a);
		$a = preg_replace("/\[img=(.*?)\]/si","<img src=\"images/smileys/\\1.gif\" border=\"0\">",$a);
		
		return $a;
	} # parseUBB
	
} # class CustomTplHelper