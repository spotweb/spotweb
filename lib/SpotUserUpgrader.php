<?php
class SpotUserUpgrader {
	private $_db;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	function update() {
		$this->createSecurityGroups();
		$this->createAnonymous();
		$this->createAdmin();
		
		$this->updateUserPreferences();
		$this->updateSecurityGroupMembership();
		
		$this->updateSecurityVersion();
	} # update()

	/*
	 * Creates the anonymous user
	 */
	function createAnonymous() {
		# if we already have an anonymous user, exit
		$anonUser = $this->_db->getUser(1);
		if ($anonUser !== false) {
			if ($anonUser['username'] != 'anonymous') {
				throw new Exception("Anonymous user is not anonymous anymore. Database logical corrupted, unable to continue");
			} # if
			
			return ;
		} # if

		# DB connectie
		$dbCon = $this->_db->getDbHandle();

		# Maak een apikey aan. Deze kan niet worden gebruikt, maar is bij voorkeur niet leeg
		$apikey = md5('anonymous');
		
		# Create the dummy 'anonymous' user
		$anonymous_user = array(
			# 'userid'		=> 1,		<= Moet 1 zijn voor de anonymous user
			'username'		=> 'anonymous',
			'firstname'		=> 'Jane',
			'passhash'		=> '',
			'lastname'		=> 'Doe',
			'mail'			=> 'john@example.com',
			'apikey'		=> $apikey,
			'lastlogin'		=> 0,
			'lastvisit'		=> 0,
			'deleted'		=> 0);
		$this->_db->addUser($anonymous_user);

		# update handmatig het userid
		$currentId = $dbCon->singleQuery("SELECT id FROM users WHERE username = 'anonymous'");
		$dbCon->exec("UPDATE users SET id = 1 WHERE username = 'anonymous'");
		$dbCon->exec("UPDATE usersettings SET userid = 1 WHERE userid = '%s'", Array( (int) $currentId));

		# Geef de anonieme user de anonymous group
		$dbCon->rawExec("INSERT INTO usergroups(userid, groupid, prio) VALUES(1, 1, 1)");
	} # createAnonymous

	/*
	 * Create the admin user 
	 */
	function createAdmin() {
		# if we already have an admin user, exit
		$adminUser = $this->_db->getUser(2);
		if ($adminUser !== false) {
			return ;
		} # if
		
		# DB connectie
		$dbCon = $this->_db->getDbHandle();

		# Vraag de password salt op 
		$passSalt = $this->_settings->get('pass_salt');
		
		# Bereken het password van de dummy admin user
		$adminPwdHash = sha1(strrev(substr($passSalt, 1, 3)) . 'admin' . $passSalt);
		
		# Maak een apikey aan. Deze kan niet worden gebruikt, maar is bij voorkeur niet leeg
		$apikey = md5('admin');
		
		# Create the dummy 'admin' user
		$admin_user = array(
			# 'userid'		=> 2,		
			'username'		=> 'admin',
			'firstname'		=> 'admin',
			'passhash'		=> $adminPwdHash,
			'lastname'		=> 'user',
			'mail'			=> 'spotwebadmin@example.com',
			'apikey'		=> $apikey,
			'lastlogin'		=> 0,
			'lastvisit'		=> 0,
			'deleted'		=> 0);
		$this->_db->addUser($admin_user);

		# update handmatig het userid
		$currentId = $dbCon->singleQuery("SELECT id FROM users WHERE username = 'admin'");
		$dbCon->exec("UPDATE users SET id = 2 WHERE username = 'admin'");
		$dbCon->exec("UPDATE usersettings SET userid = 2 WHERE userid = '%s'", Array( (int) $currentId));

		# Geef user 2 (de admin user, naar we van uit gaan) de anon, auth en admin group
		$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(2, 1, 1)");
		$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(2, 2, 2)");
		$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(2, 3, 3)");
	} # createAdmin

	/*
	 * Update all users preferences
	 */
	function updateUserPreferences() {
		$userList = $this->_db->listUsers("", 0, 9999999);

		# loop through every user and fix it 
		foreach($userList['list'] as $user) {
			# Omdat we vanuit listUsers() niet alle velden meekrijgen
			# vragen we opnieuw het user record op
			$user = $this->_db->getUser($user['userid']);

			# set the users' preferences
			$this->setSettingIfNot($user['prefs'], 'perpage', 25);
			$this->setSettingIfNot($user['prefs'], 'date_formatting', 'human');
			$this->setSettingIfNot($user['prefs'], 'template', 'we1rdo');
			$this->setSettingIfNot($user['prefs'], 'count_newspots', true);
			$this->setSettingIfNot($user['prefs'], 'keep_seenlist', true);
			$this->setSettingIfNot($user['prefs'], 'auto_markasread', true);
			$this->setSettingIfNot($user['prefs'], 'keep_downloadlist', true);
			$this->setSettingIfNot($user['prefs'], 'keep_watchlist', true);
			$this->setSettingIfNot($user['prefs'], 'nzb_search_engine', 'nzbindex');
			$this->setSettingIfNot($user['prefs'], 'show_filesize', true);
			$this->setSettingIfNot($user['prefs'], 'show_multinzb', true);
			$this->setSettingIfNot($user['prefs'], 'customcss', '');

			$this->setSettingIfNot($user['prefs']['nzbhandling'], 'action', 'disable');
			$this->setSettingIfNot($user['prefs']['nzbhandling'], 'local_dir', '/tmp');
			$this->setSettingIfNot($user['prefs']['nzbhandling'], 'prepare_action', 'merge');
			$this->setSettingIfNot($user['prefs']['nzbhandling'], 'command', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['sabnzbd'], 'url', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['sabnzbd'], 'apikey', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['nzbget'], 'host', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['nzbget'], 'port', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['nzbget'], 'username', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['nzbget'], 'password', '');
			$this->setSettingIfNot($user['prefs']['nzbhandling']['nzbget'], 'timeout', 15);

			$this->setSettingIfNot($user['prefs']['notifications']['growl'], 'host', '');
			$this->setSettingIfNot($user['prefs']['notifications']['growl'], 'password', '');
			$this->setSettingIfNot($user['prefs']['notifications']['notifo'], 'username', '');
			$this->setSettingIfNot($user['prefs']['notifications']['notifo'], 'api', '');
			$this->setSettingIfNot($user['prefs']['notifications']['prowl'], 'apikey', '');
			$notifProviders = Notifications_Factory::getFutureServices();
			foreach ($notifProviders as $notifProvider) {
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider], 'enabled', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'nzb_handled', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'retriever_finished', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'user_added', false);		
			} // foreach

			# oude settings verwijderen
			$this->unsetSetting($user['prefs'], 'search_url');

			# update the user record in the database			
			$this->_db->setUser($user);
		} # foreach
	} # update()

	/*
	 * Creeer de default security groepen
	 */
	function createSecurityGroups() {
		# DB connectie
		$dbCon = $this->_db->getDbHandle();
		
		if ($this->_settings->get('securityversion') < 0.01) {
			/* Truncate de  huidige permissies */
			$dbCon->rawExec("DELETE FROM securitygroups");

			/* Creeer de security groepen */
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(1, 'Anonymous users')");
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(2, 'Authenticated users')");
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(3, 'Administrators')");			
		} # if
	} # createSecurityGroups
	
	/* 
	 * Update de 'default' security groepen hun membership
	 */
	function updateSecurityGroupMembership() {
		# DB connectie
		$dbCon = $this->_db->getDbHandle();
		
		if ($this->_settings->get('securityversion') < 0.01) {
			/* Truncate de  huidige permissies */
			$dbCon->rawExec("DELETE FROM grouppermissions");

			/* Default permissions for anonymous users */
			$anonPerms = array(SpotSecurity::spotsec_view_spots_index, SpotSecurity::spotsec_perform_login, SpotSecurity::spotsec_perform_search,
							   SpotSecurity::spotsec_view_spotdetail, SpotSecurity::spotsec_retrieve_nzb, SpotSecurity::spotsec_view_spotimage,
							   SpotSecurity::spotsec_view_statics, SpotSecurity::spotsec_create_new_user, SpotSecurity::spotsec_view_comments, 
							   SpotSecurity::spotsec_view_spotcount_total);
			foreach($anonPerms as $anonPerm) {
				$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(1, " . $anonPerm . ")");
			} # foreach

			/* Default permissions for authenticated users */
			$authedPerms = array(SpotSecurity::spotsec_download_integration, SpotSecurity::spotsec_mark_spots_asread, SpotSecurity::spotsec_view_rssfeed,
							   SpotSecurity::spotsec_edit_own_userprefs, SpotSecurity::spotsec_edit_own_user, SpotSecurity::spotsec_post_comment,
							   SpotSecurity::spotsec_perform_logout, SpotSecurity::spotsec_use_sabapi, SpotSecurity::spotsec_keep_own_watchlist, 
							   SpotSecurity::spotsec_keep_own_downloadlist, SpotSecurity::spotsec_keep_own_seenlist, SpotSecurity::spotsec_view_spotcount_filtered,
							   SpotSecurity::spotsec_select_template, SpotSecurity::spotsec_consume_api);
			foreach($authedPerms as $authedPerm) {
				$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . $authedPerm . ")");
			} # foreach

			/* Default permissions for administrative users */
			$adminPerms = array(SpotSecurity::spotsec_list_all_users, SpotSecurity::spotsec_retrieve_spots, SpotSecurity::spotsec_edit_other_users);
			foreach($adminPerms as $adminPerm) {
				$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . $adminPerm . ")");
			} # foreach
		} # if
		
		# We voegen nog extra security toe voor de logged in user, deze mag gebruik
		# maken van een aantal paginas via enkel api authenticatie
		if ($this->_settings->get('securityversion') < 0.02) {
			$dbCon->rawExec("DELETE FROM grouppermissions WHERE permissionid = " . SpotSecurity::spotsec_consume_api . " AND 
								objectid in ('rss', 'newznabapi', 'getnzb', 'getspot')");
			
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_consume_api . ", 'rss')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_consume_api . ", 'newznabapi')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_consume_api . ", 'getnzb')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_consume_api . ", 'getspot')");
		} # if

		# We voegen nog extra security toe voor de logged in user, deze mag gebruik
		# maken van een aantal download integration settings. De admin user mag ze van
		# allemaal (tot nu toe bekent) gebruik maken.
		if ($this->_settings->get('securityversion') < 0.03) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_download_integration . ", 'disable')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_download_integration . ", 'client-sabnzbd')");

			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_download_integration . ", 'push-sabnzbd')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_download_integration . ", 'save')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_download_integration . ", 'runcommand')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_download_integration . ", 'nzbget')");
		} # if

		# We voegen nog extra security toe voor de admin user, deze mag users wissen en
		# groepen van users aanpassen
		if ($this->_settings->get('securityversion') < 0.06) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_delete_user . ")");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_edit_groupmembership . ")");
		} # if

		# We voegen nog extra security toe voor de admin user, deze mag group membership van
		# een user tonen, en securitygroepen inhoudelijk wijzigen
		if ($this->_settings->get('securityversion') < 0.07) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_display_groupmembership . ")");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_edit_securitygroups . ")");
		} # if

		# We voegen nog extra security toe voor notificaties
		if ($this->_settings->get('securityversion') < 0.08) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ")");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ", 'email')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'growl')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'libnotify')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ", 'notifo')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ", 'prowl')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_types . ")");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_types . ", 'nzb_handled')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'retriever_finished')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'user_added')");
		} # if

		# We voegen nog extra security toe voor custom stylesheets
		if ($this->_settings->get('securityversion') < 0.09) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . SpotSecurity::spotsec_allow_custom_stylesheet . ")");
		} # if
	} # updateSecurityGroups
	
	/*
	 * Update de huidige versie van de settings
	 */
	function updateSecurityVersion() {
		# Lelijke truc om de class autoloader de SpotSecurity klasse te laten laden
		if (SpotSecurity::spotsec_perform_login == 0) { } ;
		
		$this->_settings->set('securityversion', SPOTWEB_SECURITY_VERSION);
	} # updateSecurityVersion

	/*
	 * Set een setting alleen als hij nog niet bestaat
	 */
	function setSettingIfNot(&$pref, $name, $value) {
		if (isset($pref[$name])) {
			return ;
		} # if

		$pref[$name] = $value;
	} # setSettingIfNot
	
	/*
	 * Verwijdert een gekozen setting
	 */
	function unsetSetting(&$pref, $name) {
		if (!isset($pref[$name])) {
			return ;
		} # if

		unset($pref[$name]);
	} # setSettingIfNot
	 
} # SpotUserUpgrader
