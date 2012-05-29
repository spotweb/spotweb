<?php
define('SPOTWEB_SECURITY_VERSION', '0.29');

class SpotSecurity {
	private $_db;
	private $_user;
	private $_permissions;
	private $_settings;
	private $_failAudit;
	private $_AllAudit;
	
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
	const spotsec_view_spots_index				= 0;	//
	const spotsec_perform_login					= 1;	//
	const spotsec_perform_search				= 2;	//
	const spotsec_view_spotdetail				= 3; 	//
	const spotsec_retrieve_nzb					= 4;	//
	const spotsec_download_integration			= 5;
	const spotsec_mark_spots_asread				= 6;	//
	const spotsec_view_spotimage				= 7;	//
	const spotsec_view_rssfeed					= 8;	//
	const spotsec_view_statics					= 9;	//
	const spotsec_create_new_user				= 10;	//
	const spotsec_edit_own_userprefs 			= 11;	//
	const spotsec_edit_own_user					= 12;	//
	const spotsec_list_all_users				= 13;	//
	const spotsec_post_comment					= 14;	//
	const spotsec_perform_logout				= 15;	//
	const spotsec_use_sabapi					= 16;	//
	const spotsec_keep_own_watchlist			= 17;	//
	const spotsec_keep_own_downloadlist 		= 18;	//
	const spotsec_keep_own_seenlist				= 19;	//
	const spotsec_view_spotcount_filtered		= 20;	//
	const spotsec_retrieve_spots				= 21;
	const spotsec_view_comments					= 22;	//
	const spotsec_select_template				= 23;
	const spotsec_consume_api					= 24;	//
	const spotsec_edit_other_users				= 25;	//
	const spotsec_view_spotcount_total			= 26;	//
	const spotsec_delete_user					= 27;
	const spotsec_edit_groupmembership			= 28;
	const spotsec_display_groupmembership		= 29;
	const spotsec_edit_securitygroups			= 30;
	const spotsec_send_notifications_services	= 31;
	const spotsec_send_notifications_types		= 32;
	const spotsec_allow_custom_stylesheet		= 33;
	const spotsec_keep_own_filters				= 34;
	const spotsec_set_filters_as_default		= 35;
	const spotsec_report_spam					= 36;
	const spotsec_post_spot  					= 37;
	const spotsec_blacklist_spotter				= 38;
	const spotsec_view_statistics				= 39;
	const spotsec_view_spotweb_updates			= 40;
	const spotsec_edit_settings					= 41;
	
	// Array mapping the security id to a human readable text
	private $_secHumanReadable = array(
		0		=> "Display overview of spots",
		1		=> "Log in",
		2		=> "Perform a search",
		3		=> "View spot in detail",
		4		=> "Retrieve NZB file",
		5		=> "Download manager integration",
		6		=> "Mark spots as read",
		7		=> "View image of spot",
		8		=> "RSS feed",
		9		=> "Static resources",
		10		=> "Create new user",
		11		=> "Change own preferences",
		12		=> "Change own user settings",
		13		=> "List all users",
		14		=> "Post comments to a spot",
		15		=> "Log out",
		16		=> "Use downloads manager API ",
		17		=> "Keep watchlist",
		18		=> "Keep downloadlist",
		19		=> "Keep seenlist",
		20		=> "Show new spotcount in list of filters",
		21		=> "Display Retrieve new spots button",
		22		=> "Display comments of a spot",
		23		=> "Let user choose their template",
		24		=> "Use Spotweb using an API key",
		25		=> "Change other users",
		26		=> "Display total amount of spots",
		27		=> "Delete users",
		28		=> "Change users' group membeship", 
		29		=> "Display users' group membership",
		30 		=> "Change security groups",
		31 		=> "Send notifications (per service)",
		32 		=> "Send notifications (per type)",
		33		=> "Let users create their own CSS",
		34		=> "Create own Spot filters",
		35		=> "Set a set of filters as default for new users",
		36		=> "Report a spot as spam",
		37		=> "Post a new spot",
		38		=> "Blacklist a spotter",
		39		=> "Display statistics",
		40		=> "Display Spotweb's changelog",
		41		=> "Change settings"
	);

	/*
	 * Audit levels
	 */
	const spot_secaudit_none			= 0; 
	const spot_secaudit_failure			= 1;
	const spot_secaudit_all				= 2; 
	
	function __construct(SpotDb $db, SpotSettings $settings, array $user, $ipaddr) {
		$this->_db = $db;
		$this->_user = $user;
		$this->_settings = $settings;
		$this->_failAudit = ($settings->get('auditlevel') == SpotSecurity::spot_secaudit_failure);
		$this->_allAudit = ($settings->get('auditlevel') == SpotSecurity::spot_secaudit_all);
		
		if (($this->_failAudit) || ($this->_allAudit)) {
			$this->_spotAudit = new SpotAudit($db, $settings, $user, $ipaddr);
		} # if
		
		$this->_permissions = $db->getPermissions($user['userid']);
	} # ctor
	
	function allowed($perm, $object) {
		$allowed = isset($this->_permissions[$perm][$object]) && $this->_permissions[$perm][$object];

		# We check for auditing in SpotSecurity to prevent the overhead
		# of a function call for each security check
		if (($this->_allAudit) || ((!$allowed) && ($this->_failAudit))) {
			$this->_spotAudit->audit($perm, $object, $allowed);
		} # if
		
		return $allowed;
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
