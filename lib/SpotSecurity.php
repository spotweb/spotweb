<?php
define('SPOTWEB_SECURITY_VERSION', '0.07');

require_once "lib/exceptions/PermissionDeniedException.php";

class SpotSecurity {
	private $_db;
	private $_user;
	private $_permissions;
	private $_settings;
	
	/*
	 * Het security systeem kent een aantal rechten welke gedefinieerd worden met een aantal parameters.
	 * 
	 * Parameter:
	 *     Permissie           - Permissie is de permissie die gevraagd wordt - moet gebruik maken van de gedefinieerde constants
	 *     Object              - Geeft aan dat de permissie enkel voor dit specifieke object geld. Denk bv. aan 'cat0_z4'.
	 *                           Als het objectid leeg is, dan geld de permissie voor alle objecten.
	 *     DenyOrGranted       - Als deze op "True" staat, dan is de permissie expliciet gegeven. Als de permissie op FALSE
	 *                           staat is de permissie expliciet denied. 
	 */

	 /*
	 * Constants used for securing the system
	 */
	const spotsec_view_spots_index			= 0;	//
	const spotsec_perform_login				= 1;	//
	const spotsec_perform_search			= 2;	//
	const spotsec_view_spotdetail			= 3; 	//
	const spotsec_retrieve_nzb				= 4;	//
	const spotsec_download_integration		= 5;
	const spotsec_mark_spots_asread			= 6;	//
	const spotsec_view_spotimage			= 7;	//
	const spotsec_view_rssfeed				= 8;	//
	const spotsec_view_statics				= 9;	//
	const spotsec_create_new_user			= 10;	//
	const spotsec_edit_own_userprefs 		= 11;	//
	const spotsec_edit_own_user				= 12;	//
	const spotsec_list_all_users			= 13;	//
	const spotsec_post_comment				= 14;	//
	const spotsec_perform_logout			= 15;	//
	const spotsec_use_sabapi				= 16;	//
	const spotsec_keep_own_watchlist		= 17;	//
	const spotsec_keep_own_downloadlist 	= 18;	//
	const spotsec_keep_own_seenlist			= 19;	//
	const spotsec_view_spotcount_filtered	= 20;	//
	const spotsec_retrieve_spots			= 21;
	const spotsec_view_comments				= 22;	//
	const spotsec_select_template			= 23;
	const spotsec_consume_api				= 24;	//
	const spotsec_edit_other_users			= 25;	//
	const spotsec_view_spotcount_total		= 26;	//
	const spotsec_delete_user				= 27;
	const spotsec_edit_groupmembership		= 28;
	const spotsec_display_groupmembership	= 29;
	const spotsec_edit_securitygroups		= 30;
	
	// Array mapping the security id to a human readable text
	private $_secHumanReadable = array(
		0		=> "Overzicht van spots tonen",
		1		=> "Inloggen",
		2		=> "Zoekopdracht uitvoeren",
		3		=> "Spot in detail zien",
		4		=> "NZB file opvragen",
		5		=> "Integratie met download manager",
		6		=> "Spots markeren als gelezen",
		7		=> "Afbeelding van een spot tonen",
		8		=> "RSS feed",
		9		=> "Static resources",
		10		=> "Nieuwe gebruiker aanmaken",
		11		=> "Eigen voorkeuren wijzigen",
		12		=> "Eigen gebruiker wijzigen",
		13		=> "Alle gebruikers oplijsten",
		14		=> "Commentaar op een spot posten",
		15		=> "Uitloggen",
		16		=> "sabnzbd API gebruiken (niet download manager integratie)",
		17		=> "Watchlist bijhouden",
		18		=> "Downloadlist bijhouden",
		19		=> "Seenlist bijhouden",
		20		=> "Spotcount in filterlijst tonen",
		21		=> "Nieuwe spots ophalen knop tonen",
		22		=> "Comments op een spot laten zien",
		23		=> "Eigen template selecteren",
		24		=> "SpotWeb gebruiken via API key",
		25		=> "Andere gebruikers wijzigen",
		26		=> "Totaal aantal spots tonen",
		27		=> "Gebruikers wissen",
		28		=> "Groupen waar een user lid van is wijzigen",
		29		=> "Groupen waar een user lid van is tonen",
		30 		=> "Security groupen kunnen wijzigen"
	);
	
	function __construct(SpotDb $db, SpotSettings $settings, array $user) {
		$this->_db = $db;
		$this->_user = $user;
		$this->_settings = $settings;
		
		$this->_permissions = $db->getPermissions($user['userid']);
	} # ctor
	
	function allowed($perm, $object) {
		return isset($this->_permissions[$perm][$object]) && $this->_permissions[$perm][$object];
	} # allowed
	
	function fatalPermCheck($perm, $object) {
		if (!$this->allowed($perm, $object)) {
			throw new PermissionDeniedException($perm, $object);
		} # if
	} # fatalPermCheck
	
	function toHuman($perm) {
		return $this->_secHumanReadable[$perm];
	} # toHuman
	
	function getAllPermissions() {
		return $this->_secHumanReadable;
	} # getAllPermissions
	
	function securityValid() {
		# SPOTWEB_SECURITY_VERSION is gedefinieerd bovenin dit bestand
		return ($this->_settings->get('securityversion') == SPOTWEB_SECURITY_VERSION);
	} # securityValid
	
} # class SpotSecurity
