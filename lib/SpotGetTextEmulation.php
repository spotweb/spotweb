<?php
	/*
	 * This is procedural code because we want these functions to
	 * be in the global name space
	 */
	
	/* include the File_GetText routines */
	require_once('lib/gettext/Gettext.php');

	# Object
	$__gt_obj = '';

	
	/**
	 * Initializes the .MO file access class
	 */
	function _gettext_init($domain, $language) {
		global $__gt_obj;
		$__gt_obj = new Gettext_PHP('locales', $domain, $language);
	} # _gettext_init()

	function bindtextdomain($domain, $directory) {
		global $__gt_obj;
		return $__gt_obj->bindtextdomain($domain, $directory);
	} # bindtextdomain

	function _($msg) {
		global $__gt_obj;
		return $__gt_obj->gettext($msg);
	} # gettext

	function gettext($msg) {
		global $__gt_obj;
		return $__gt_obj->gettext($msg);
	} # gettext

	function ngettext($msg, $msg_plural, $count) {
		global $__gt_obj;
		return $__gt_obj->ngettext($msg, $msg_plural, $count);
	} # ngettext