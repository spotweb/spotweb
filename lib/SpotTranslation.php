<?php

/*
 * Translation code for Spotweb
 */
class SpotTranslation {

	public static function initialize($lang) {
		putenv("LC_ALL=" . $lang . ".UTF-8");
		setlocale(LC_ALL, $lang . '.UTF-8');

		# Initialize the textdomain
		bindtextdomain('messages', __DIR__ . '/../locales/');
		textdomain('messages');
	} # initialize
	
} # class SpotTranslation

# Make sure _() exists, a very dumb placeholder for now
if (!function_exists('_')) {
	function _($s) { return $s; }
} # if

