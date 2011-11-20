<?php

if (!function_exists('gettext')) {
	require_once "lib/SpotGetTextEmulation.php";
} # if

/*
 * Translation code for Spotweb
 */
class SpotTranslation {

	public static function initialize($lang) {
		# Do we need the emulation library?
		if (function_exists('_gettext_init')) {
			_gettext_init('messages', $lang);
		} else {
			putenv("LC_ALL=" . $lang . ".UTF-8");
			setlocale(LC_ALL, $lang . '.UTF-8');

			# Initialize the textdomain
			bindtextdomain('messages', 'locales/');
			bind_textdomain_codeset('messages', 'UTF-8'); 
			textdomain('messages');
		} # else
	} # initialize
} # class SpotTranslation

