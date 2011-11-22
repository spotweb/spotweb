<?php
/*
 * Translation code for Spotweb
 */
class SpotTranslation {

	public static function initialize($lang) {
		# Do we native gettext?
		if (extension_loaded('gettext')) {
			putenv("LC_ALL=" . $lang . ".UTF-8");
			setlocale(LC_ALL, $lang . '.UTF-8');

			# Initialize the textdomain
			bindtextdomain('messages', 'locales/');
			bind_textdomain_codeset('messages', 'UTF-8');
			textdomain('messages');
		} else {
			global $_gt_obj;
			$_gt_obj = new Gettext_PHP('locales', 'messages', $lang);
		} # else
	} # initialize
} # class SpotTranslation


/*
 * This is procedural code because we want these functions to
 * be in the global name space
 */
if (!extension_loaded('gettext')) {
	function _($msg) {
		global $_gt_obj;
		return $_gt_obj->gettext($msg);
	} # _ alias of gettext
} # if

if (!extension_loaded('gettext')) {
	function gettext($msg) {
		global $_gt_obj;
		return $_gt_obj->gettext($msg);
	} # gettext
} # if

if (!extension_loaded('gettext')) {
	function ngettext($msg, $msg_plural, $count) {
		global $_gt_obj;
		return $_gt_obj->ngettext($msg, $msg_plural, $count);
	} # ngettext
} # if
