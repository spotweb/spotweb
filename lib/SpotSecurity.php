<?php

class SpotSecurity {
	private $_db;
	private $_user;
	
	/*
	 * Het security systeem kent een aantal rechten welke gedefinieerd worden met een aantal parameters.
	 * 
	 * Parameter:
	 *     Permissie           - Permissie is de permissie die gevraagd wordt - moet gebruik maken van de gedefinieerde constants
	 *     Object              - Geeft aan dat de permissie enkel voor dit spceifieke object geld. Denk bv. aan 'cat0_z4'.
	 *                           Als het objectid leeg is, dan geld de permissie voor alle objecten.
	 *     DenyOrGranted       - Als deze op "True" staat, dan is de permissie expliciet gegeven. Als de permissie op FALSE
	 *                           staat is de permissie expliciet denied. 
	 */

	 /*
	 * Constants used for securing the system
	 */
	const spotsec_view_spots_index			= 0;
	const spotsec_perform_login				= 1;
	const spotsec_perform_search			= 2;
	const spotsec_view_spotdetail			= 3; 	//
	const spotsec_retrieve_nzb				= 4;
	const spotsec_download_integration		= 5;
	const spotsec_mark_spots_asread			= 6;
	const spotsec_view_spotimage			= 7;
	const spotsec_view_rssfeed				= 8;
	const spotsec_view_statics				= 9;
	const spotsec_create_new_user			= 10;	//
	const spotsec_edit_own_userprefs 		= 11;
	const spotsec_edit_own_user				= 12;
	const spotsec_list_all_users			= 13;
	const spotsec_post_comment				= 14;
	const spotsec_perform_logout			= 15;
	const spotsec_use_sabapi				= 16;
	const spotsec_keep_own_watchlist		= 17;
	const spotsec_keep_own_downloadlist 	= 18;
	const spotsec_keep_own_seenlist			= 19;
	const spotsec_view_spotcount_filters	= 20;
	const spotsec_retrieve_spots			= 21;
	const spotsec_view_comments				= 22;
	const spotsec_select_template			= 23;
	const spotsec_conume_api				= 24;
	const spotsec_edit_other_users			= 25;
			
	private $defaultRights = array(
		##### NAME OF PERMISSION ############## OBJECT ################ TRUE = Allowed, FALSE = Denied 
		array('',								'',						TRUE)
	);
	
	
	
	function __construct(SpotDb $db, array $user) {
		$this->_db = $db;
		$this->_user = $user;
	} # ctor
	
	function allowed($perm, $object) {
		return true;
	} # allowed
	
	function fatalPermCheck($perm, $object) {
		if (!$this->allowed($perm, $object)) {
			throw new SecurityException($perm);
		} # if
	} # fatalPermCheck
	
} # class SpotSecurity