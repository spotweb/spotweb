<?php
	/*
	 * This is procedural code because we want these functions to
	 * be in the global name space
	 */
	
	/* include the File_GetText routines */
	require_once('lib/gettext/Gettext.php');
	require_once('lib/gettext/MO.php');

	# Current default language
	$__gt_language = '';

	# Default gettext domain
	$__gt_defaultdomain = '';

	# An array with domain => path 
	$__gt_domainList = array();

	/**
	 * Set the default gettext language
	 */
	function _gettext_setlang($language) {
		global $__gt_language;

		$__gt_language = $language;
	} # _gettext_setlang

	/**
	 * Initializes the .MO file access class
	 */
	function _gettext_init($domain)
	{
		global $__gt_domainList;
		global $__gt_language;

		if( !$__gt_domainList[$domain]['moObj'] ) {
			/* which file do we need ? */
			$fname = $__gt_domainList[$domain]['directory'] .
					 DIRECTORY_SEPARATOR .
					 $__gt_language . DIRECTORY_SEPARATOR .
					 'LC_MESSAGES' . DIRECTORY_SEPARATOR .
					 $domain;

			/*
			 * Loading an .MO file is really slow, we basically
			 * serialize the complete language array to an cache
			 * file and use it for loading our data
			 */
			if( ($tmpobj = @file_get_contents($fname . '.ser')) === FALSE) {
				# Create a new File_GetText object instance */
				$__gt_domainList[$domain]['moObj'] =
				File_Gettext::factory('MO',
									  $fname . '.mo');

				# Load the actual resource strings
				$__gt_domainList[$domain]['moObj']->load();

				# Save the resource strings back to disk for caching
				@file_put_contents($fname . '.ser', serialize($__gt_domainList[$domain]['moObj']) );
			} else {
				/* array was to be found serialized */
				$__gt_domainList[$domain]['moObj'] = unserialize($tmpobj);
			} # else
		 } # if
	} # _gettext_init()

	/**
	 * Sets the path for a domain
	 */
	function bindtextdomain($domain, $directory) {
		global $__gt_domainList;

		$__gt_domainList[$domain]['directory'] = $directory;
		$__gt_domainList[$domain]['moObj'] = false;
	} # bindtextdomain()

	/**
	 * Sets the codepoint for a domain (ignored)
	 */
	function bind_textdomain_codeset() {
	} # bind_textdomain_codeset()
	
	/**
	 * Sets the default domain
	 */
	function textdomain($text_domain) {
		global $__gt_defaultdomain;

		$__gt_defaultdomain = $text_domain;
	} # textdomain()

	/**
	 * Lookup a message in the current domain
	 */
	function gettext($message) {
		global $__gt_defaultdomain;
		global $__gt_domainList;

		// initialize current domain
		_gettext_init($__gt_defaultdomain);

		// and return the translated string if its known, else just return the one asked for
		if( isset( $__gt_domainList[$__gt_defaultdomain]['moObj']->strings[$message]) ) {
			return utf8_decode( $__gt_domainList[$__gt_defaultdomain]['moObj']->strings[$message] );
		} else {
			return $message;
		} # else
	} # gettext()

	/**
	 * Alias for gettext()
	 */
	function _($message) {
		return gettext( $message );
	} # _()
