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
		if (function_exists('_gettext_setlang')) {
			_gettext_setlang($lang);
		} else {
			putenv("LC_ALL=" . $lang . ".UTF-8");
			setlocale(LC_ALL, $lang . '.UTF-8');
		} # else

		# Initialize the textdomain
		bindtextdomain('messages', 'locales/');
		bind_textdomain_codeset('messages', 'UTF-8'); 
		textdomain('messages');
	} # initialize
} # class SpotTranslation

