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
		$this->updateUserFilters();
		
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
			$this->setSettingIfNot($user['prefs'], 'show_reportcount', true);
			$this->setSettingIfNot($user['prefs'], 'show_multinzb', true);
			$this->setSettingIfNot($user['prefs'], 'customcss', '');
			$this->setSettingIfNot($user['prefs'], 'newspotdefault_tag', $user['username']);
			$this->setSettingIfNot($user['prefs'], 'newspotdefault_body', '');

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
			$this->setSettingIfNot($user['prefs']['notifications']['nma'], 'api', '');
			$this->setSettingIfNot($user['prefs']['notifications']['notifo'], 'username', '');
			$this->setSettingIfNot($user['prefs']['notifications']['notifo'], 'api', '');
			$this->setSettingIfNot($user['prefs']['notifications']['prowl'], 'apikey', '');
			$this->setSettingIfNot($user['prefs']['notifications']['twitter'], 'screen_name', '');
			$this->setSettingIfNot($user['prefs']['notifications']['twitter'], 'request_token', '');
			$this->setSettingIfNot($user['prefs']['notifications']['twitter'], 'request_token_secret', '');
			$this->setSettingIfNot($user['prefs']['notifications']['twitter'], 'access_token', '');
			$this->setSettingIfNot($user['prefs']['notifications']['twitter'], 'access_token_secret', '');
			$notifProviders = Notifications_Factory::getActiveServices();
			foreach ($notifProviders as $notifProvider) {
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider], 'enabled', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'watchlist_handled', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'nzb_handled', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'retriever_finished', false);
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'report_posted', false);		
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'spot_posted', false);		
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'user_added', false);		
			} // foreach

			# make sure a sort preference is defined. An empty field means relevancy
			$this->setSettingIfNot($user['prefs'], 'defaultsortfield', '');

			# oude settings verwijderen
			$this->unsetSetting($user['prefs'], 'search_url');
			$this->unsetSetting($user['prefs']['notifications'], 'libnotify');
			
			# controleren dat de user een geldige RSA key heeft
			if ($user['userid'] > 2) {
				$rsaKey = $this->_db->getUserPrivateRsaKey($user['userid']);
				if (empty($rsaKey)) {
					# Creer een private en public key paar voor deze user
					$spotSigning = new SpotSigning();
					$userKey = $spotSigning->createPrivateKey($this->_settings->get('openssl_cnf_path'));
					
					$this->_db->setUserRsaKeys($user['userid'], $userKey['public'], $userKey['private']);
				} # if
			} # if

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

		# We voegen nog extra security toe voor watchlist notificaties en een vergeten NZB download
		if ($this->_settings->get('securityversion') < 0.10) {
			$dbCon->rawExec("DELETE FROM grouppermissions WHERE permissionid = " . SpotSecurity::spotsec_send_notifications_services . " AND objectid = 'libnotify'");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_types . ", 'watchlist_handled')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_consume_api . ", 'getnzbmobile')");
		} # if

		# Twitter toegevoegd
		if ($this->_settings->get('securityversion') < 0.11) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ", 'twitter')");
		} # if

		# Zelf filters kunnen wijzigen
		if ($this->_settings->get('securityversion') < 0.12) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . SpotSecurity::spotsec_keep_own_filters . ")");
		} # if

		# Filters als default in kunnen stellen voor de anonymous user
		if ($this->_settings->get('securityversion') < 0.13) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_set_filters_as_default . ")");
		} # if

		# Downloads kunnen wissen is een apart recht geworden (issue #935)
		if ($this->_settings->get('securityversion') < 0.14) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid,objectid) VALUES(3, " . SpotSecurity::spotsec_keep_own_downloadlist . ", 'erasedls')");
		} # if
		
		# Spam reporting toegevoegd
		if ($this->_settings->get('securityversion') < 0.15) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . SpotSecurity::spotsec_report_spam . ")");
		} # if

		# Nieuwe spot posten toegevoegd
		if ($this->_settings->get('securityversion') < 0.16) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . SpotSecurity::spotsec_post_spot . ")");
		} # if

		# Notify My Android toegevoegd
		if ($this->_settings->get('securityversion') < 0.17) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ", 'nma')");
		} # if

		# Notificatie bij Spot Posten
		if ($this->_settings->get('securityversion') < 0.18) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_types . ", 'spot_posted')");
		} # if

		# Notificatie bij Report Posten
		if ($this->_settings->get('securityversion') < 0.19) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_types . ", 'report_posted')");
		} # if
	} # updateSecurityGroups

	/*
	 * Update user filters
	 */
	function updateUserFilters() {
		if (($this->_settings->get('securityversion') < 0.12)) {
			# DB connectie
			$dbCon = $this->_db->getDbHandle();
			
			# delete all existing filters
			$dbCon->rawExec("DELETE FROM filters WHERE filtertype = 'filter'");

			$userList = $this->_db->listUsers("", 0, 9999999);

			# loop through every user and fix it 
			foreach($userList['list'] as $user) {
				/* Beeld */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Beeld', 'film', 0, 0, 'cat0_z0')");
				$beeldFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'DivX', 'divx', 0, " . $beeldFilterId . ", 'cat0_z0_a0')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'WMV', 'wmv', 1, " . $beeldFilterId . ", 'cat0_z0_a1')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'MPEG', 'mpg', 2, " . $beeldFilterId . ", 'cat0_z0_a2')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'DVD', 'dvd', 3, " . $beeldFilterId . ", 'cat0_z0_a3,cat0_z0_a10')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'HD', 'hd', 4, " . $beeldFilterId . ", 'cat0_z0_a4,cat0_z0_a6,cat0_z0_a7,cat0_z0_a8,cat0_z0_a9')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Series', 'tv', 5, " . $beeldFilterId . ", 'cat0_z1')");

				/* Boeken */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Boeken', 'book', 6, " . $beeldFilterId . ", 'cat0_z2')");
				$boekenFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Nederlands', 'book', 0, " . $boekenFilterId . ", 'cat0_z2_c11')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Engels', 'book', 1, " . $boekenFilterId . ", 'cat0_z2_c10')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Anders', 'book', 2, " . $boekenFilterId . ", 'cat0_z2,~cat0_z2_c10,~cat0_z2_c11')");
				
				/* Erotiek */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Erotiek', 'female', 7, " . $beeldFilterId. ", 'cat0_z3')");
				$erotiekFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Hetero', 'female', 0, " . $erotiekFilterId . ", 'cat0_z3_d75,cat0_z3_d23')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Homo', 'female', 1, " . $erotiekFilterId . ", 'cat0_z3_d74,cat0_z3_d24')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Lesbo', 'female', 2, " . $erotiekFilterId . ", 'cat0_z3_d73,cat0_z3_d25')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Bi', 'female', 3, " . $erotiekFilterId . ", 'cat0_z3_d72,cat0_z3_d26')");

				/* Muziek */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Muziek', 'music', 2, 0, 'cat1')");
				$muziekFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Compressed', 'music', 0, " . $muziekFilterId . ", 'cat1_a0,cat1_a3,cat1_a5,cat1_a6')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Lossless', 'music', 1, " . $muziekFilterId . ", 'cat1_a2,cat1_a4,cat1_a7,cat1_a8')");

				/* Spellen */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Spellen', 'controller', 3, 0, 'cat2')");
				$gameFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Windows', 'windows', 0, " . $gameFilterId . ", 'cat2_a0')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Mac / Linux', 'linux', 1, " . $gameFilterId . ", 'cat2_a1,cat2_a2')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Playstation', 'playstation', 2, " . $gameFilterId . ", 'cat2_a3,cat2_a4,cat2_a5,cat2_a12')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'XBox', 'xbox', 3, " . $gameFilterId . ", 'cat2_a6,cat2_a7')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Nintendo', 'nintendo_ds', 4, " . $gameFilterId . ", 'cat2_a8,cat2_a9,cat2_a10,cat2_a11')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Smartphone / PDA', 'pda', 5, " . $gameFilterId . ", 'cat2_a13,cat2_a14,cat2_a15')");

				/* Applicaties */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Applicaties', 'application', 4, 0, 'cat3')");
				$appFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Windows', 'vista', 0, " . $appFilterId . ", 'cat3_a0')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Mac / Linux / OS2', 'linux', 1, " . $appFilterId . ", 'cat3_a1,cat3_a2,cat3_a3')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'PDA / Navigatie', 'pda', 2, " . $appFilterId . ", 'cat3_a4,cat3_a5,cat3_a6,cat3_a7')");
			} # foreach
		} # if
	} # updateUserFilters
	
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
