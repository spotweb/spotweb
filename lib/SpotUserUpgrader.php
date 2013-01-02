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
		$this->createAdmin('admin', 'user', 'admin', 'spotwebadmin@example.com');
		
		$this->updateUserPreferences();
		$this->updateSecurityGroups(false);

		/* Reset the users' group membership */
		if ($this->_settings->get('securityversion') < 0.27) {
			$this->resetUserGroupMembership($this->_settings->get('systemtype'));
		} # if

		$this->updateUserFilters(false);
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

		# DB connection
		$dbCon = $this->_db->getDbHandle();

		# Create an Spotweb API key. It cannot be used but should not be empty
		$apikey = md5('anonymous');
		
		# Create the dummy 'anonymous' user
		$anonymous_user = array(
			# 'userid'		=> 1,		<= Must be 1 for the anonymous user
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

		# Manually update the userid so we can be sure anonymous == userid 1
		$currentId = $dbCon->singleQuery("SELECT id FROM users WHERE username = 'anonymous'");
		$dbCon->exec("UPDATE users SET id = 1 WHERE username = 'anonymous'");
		$dbCon->exec("UPDATE usersettings SET userid = 1 WHERE userid = '%s'", Array( (int) $currentId));
	} # createAnonymous

	/*
	 * Create a password hash. Duplicate with
	 * SpotUserSystem but we cannot rely on that one
	 * to be available for the moment
	 */
	function passToHash($password) {
		return sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)) . $password . $this->_settings->get('pass_salt'));
	} # passToHash


	/*
	 * Resets a given users' password
	 */
	function resetUserPassword($username, $password) {
		$userId = $this->_db->findUserIdForName($username);
		if (empty($userId)) {
			throw new Exception("Username cannot be found to reset password for");
		} # if

		# Retrieve the actual userid
		$user = $this->_db->getUser($userId);

		# update the password
		$user['passhash'] = $this->passToHash($password);
		$this->_db->setUserPassword($user);
	} # resetUserPassword
	/*
	 * Create the admin user 
	 */
	function createAdmin($firstName, $lastName, $password, $mail) {
		# if we already have an admin user, exit
		$adminUser = $this->_db->getUser(2);
		if ($adminUser !== false) {
			return ;
		} # if
		
		# DB connection
		$dbCon = $this->_db->getDbHandle();

		# calculate the password salt for the admin user
		$adminPwdHash = $this->passToHash($password);
		
		# Create an Spotweb API key. It cannot be used but should not be empty
		$apikey = md5('admin');
		
		# Create the dummy 'admin' user
		$admin_user = array(
			# 'userid'		=> 2,		
			'username'	=> 'admin',
			'firstname'	=> $firstName,
			'lastname'		=> $lastName,
			'passhash'	=> $adminPwdHash,
			'mail'		=> $mail,
			'apikey'		=> $apikey,
			'lastlogin'		=> 0,
			'lastvisit'		=> 0,
			'deleted'		=> 0);
		$this->_db->addUser($admin_user);

		# Manually update the userid so we can be sure admin == userid 2
		$currentId = $dbCon->singleQuery("SELECT id FROM users WHERE username = 'admin'");
		$dbCon->exec("UPDATE users SET id = 2 WHERE username = 'admin'");
		$dbCon->exec("UPDATE usersettings SET userid = 2 WHERE userid = '%s'", Array( (int) $currentId));
	} # createAdmin

	/*
	 * Update all users preferences
	 */
	function resetUserGroupMembership($systemType) {
		# DB connection
		$dbCon = $this->_db->getDbHandle();

		$userList = $this->_db->getUserList();

		# loop through every user and fix it 
		foreach($userList as $user) {
			/*
			 * Remove current group membership
			 */
			$dbCon->rawExec("DELETE FROM usergroups WHERE userid = " . $user['userid']);
			
			/* 
			 * Actually update the group membership, depending
			 * on what kind of user this is
			 */
			if ($user['userid'] == 1) {
				# Anonymous user
				if ($systemType == 'shared') {
					/* Grant the group with only logon rights */
					$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(1, 1, 1)");
				} else {
					/* Grant the group with the view permissions */
					$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(1, 2, 1)");
				} # else
			} elseif (($user['userid'] == 2) || ($user['userid'] == $this->_settings->get('custom_admin_userid'))) {
				# Admin user
				$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 2, 1)");
				$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 3, 2)");
				$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 4, 3)");
				$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 5, 4)");
			} else {
				# Grant the regular users all the necessary security groups
				$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 2, 1)");
				$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 3, 2)");

				# For a shared and single system, all users are trusted
				if (($systemType == 'shared') || ($systemType == 'single')) {
					$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 4, 3)");
				} # if

				# For a single system, all users are administrators as well (there should only be one additional user)
				if ($systemType == 'single') {
					$dbCon->rawExec("INSERT INTO usergroups(userid,groupid,prio) VALUES(" . $user['userid'] . ", 5, 4)");
				} # if
			} # else
		} # foreach
	} # resetUserGroupMembership

	/*
	 * Mass update all users preferences
	 */
	function massChangeUserPreferences($prefName, $prefValue) {
		$userList = $this->_db->getUserList();

		# loop through every user and fix it 
		foreach($userList as $user) {
			/*
			 * Because we do not get all users' properties from
			 * getUserList, retrieve the users' settings from scratch
			 */
			$user = $this->_db->getUser($user['userid']);


			/*
			 * update the preference in the record, we don't
			 * support nested preferences just yet.
			 */
			if (isset($user['prefs'][$prefName])) {
				$user['prefs'][$prefName] = $prefValue;
			} # if


			/*
			 * update the user record in the database			
			 */
			$this->_db->setUser($user);
		} # foreach
	} # massChangeUserPreferences

	/*
	 * Update all users preferences
	 */
	function updateUserPreferences() {
		$userList = $this->_db->getUserList();

		# loop through every user and fix it 
		foreach($userList as $user) {
			/*
			 * Because we do not get all users' properties from
			 * getUserList, retrieve the users' settings from scratch
			 */
			$user = $this->_db->getUser($user['userid']);

			# set the users' preferences
			$this->setSettingIfNot($user['prefs'], 'perpage', 25);
			$this->setSettingIfNot($user['prefs'], 'date_formatting', 'human');
			$this->setSettingIfNot($user['prefs'], 'normal_template', 'we1rdo');
			$this->setSettingIfNot($user['prefs'], 'mobile_template', 'we1rdo');
			$this->setSettingIfNot($user['prefs'], 'tablet_template', 'we1rdo');
			$this->setSettingIfNot($user['prefs'], 'count_newspots', true);
            $this->setSettingIfNot($user['prefs'], 'mouseover_subcats', true);
			$this->setSettingIfNot($user['prefs'], 'keep_seenlist', true);
			$this->setSettingIfNot($user['prefs'], 'auto_markasread', true);
			$this->setSettingIfNot($user['prefs'], 'keep_downloadlist', true);
			$this->setSettingIfNot($user['prefs'], 'keep_watchlist', true);
			$this->setSettingIfNot($user['prefs'], 'nzb_search_engine', 'nzbindex');
			$this->setSettingIfNot($user['prefs'], 'show_filesize', true);
			$this->setSettingIfNot($user['prefs'], 'show_reportcount', true);
			$this->setSettingIfNot($user['prefs'], 'minimum_reportcount', 1);
			$this->setSettingIfNot($user['prefs'], 'show_nzbbutton', true);
			$this->setSettingIfNot($user['prefs'], 'show_multinzb', true);
			$this->setSettingIfNot($user['prefs'], 'customcss', '');
			$this->setSettingIfNot($user['prefs'], 'newspotdefault_tag', $user['username']);
			$this->setSettingIfNot($user['prefs'], 'newspotdefault_body', '');
			$this->setSettingIfNot($user['prefs'], 'user_language', 'en_US');
			$this->setSettingIfNot($user['prefs'], 'show_avatars', true);
			$this->setSettingIfNot($user['prefs'], 'usemailaddress_for_gravatar', true);

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

			$this->setSettingIfNot($user['prefs']['notifications']['boxcar'], 'email', '');
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
				$this->setSettingIfNot($user['prefs']['notifications'][$notifProvider]['events'], 'newspots_for_filter', false);		
			} // foreach

			# make sure a sort preference is defined. An empty field means relevancy
			$this->setSettingIfNot($user['prefs'], 'defaultsortfield', '');

			# Remove deprecated preferences
			$this->unsetSetting($user['prefs'], 'search_url');
			$this->unsetSetting($user['prefs'], 'template');
			$this->unsetSetting($user['prefs']['notifications'], 'libnotify');
			
			# Make sure the user has a valid RSA key
			if ($user['userid'] > 2) {
				$rsaKey = $this->_db->getUserPrivateRsaKey($user['userid']);
				if (empty($rsaKey)) {
					# Creer een private en public key paar voor deze user
					$spotSigning = Services_Signing_Base::newServiceSigning();
					$userKey = $spotSigning->createPrivateKey($this->_settings->get('openssl_cnf_path'));
					
					$this->_db->setUserRsaKeys($user['userid'], $userKey['public'], $userKey['private']);
				} # if
			} # if

			# update the user record in the database			
			$this->_db->setUser($user);
		} # foreach
	} # update()

	/*
	 * Create the default security groups
	 */
	function createSecurityGroups() {
		# DB connection
		$dbCon = $this->_db->getDbHandle();
		
		if ($this->_settings->get('securityversion') < 0.27) {
			/* Truncate the current defined permissions  */
			$dbCon->rawExec("DELETE FROM securitygroups");

			/* Create the security groepen */
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(1, 'Anonymous user - closed system')");
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(2, 'Anonymous user - open system')");
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(3, 'Authenticated users')");
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(4, 'Trusted users')");
			$dbCon->rawExec("INSERT INTO securitygroups(id,name) VALUES(5, 'Administrators')");				
		} # if
	} # createSecurityGroups
	
	/* 
	 * Update de 'default' security groepen hun rechten
	 */
	function updateSecurityGroups($forceReset) {
		# DB connectie	
		$dbCon = $this->_db->getDbHandle();
		
		if (($forceReset) || ($this->_settings->get('securityversion') < 0.27)) {
			/* Truncate de  huidige permissies */
			$dbCon->rawExec("DELETE FROM grouppermissions");

			/* Grant the logon right to the anonymous user - closed system group */
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(1, " . SpotSecurity::spotsec_perform_login . ")");
			
			/* Default permissions for anonymous users */
			$anonPerms = array(SpotSecurity::spotsec_view_spots_index, SpotSecurity::spotsec_perform_login, SpotSecurity::spotsec_perform_search,
							   SpotSecurity::spotsec_view_spotdetail, SpotSecurity::spotsec_retrieve_nzb, SpotSecurity::spotsec_view_spotimage,
							   SpotSecurity::spotsec_view_statics, SpotSecurity::spotsec_create_new_user, SpotSecurity::spotsec_view_comments, 
							   SpotSecurity::spotsec_view_spotcount_total);
			foreach($anonPerms as $anonPerm) {
				$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(2, " . $anonPerm . ")");
			} # foreach

			/* Default permissions for authenticated users */
			$authedPerms = array(SpotSecurity::spotsec_download_integration, SpotSecurity::spotsec_mark_spots_asread, SpotSecurity::spotsec_view_rssfeed,
							   SpotSecurity::spotsec_edit_own_userprefs, SpotSecurity::spotsec_edit_own_user, SpotSecurity::spotsec_post_comment,
							   SpotSecurity::spotsec_perform_logout, SpotSecurity::spotsec_use_sabapi, SpotSecurity::spotsec_keep_own_watchlist, 
							   SpotSecurity::spotsec_keep_own_downloadlist, SpotSecurity::spotsec_keep_own_seenlist, SpotSecurity::spotsec_view_spotcount_filtered,
							   SpotSecurity::spotsec_select_template, SpotSecurity::spotsec_consume_api, SpotSecurity::spotsec_allow_custom_stylesheet,
							   SpotSecurity::spotsec_keep_own_filters, SpotSecurity::spotsec_report_spam, SpotSecurity::spotsec_post_spot,
							   SpotSecurity::spotsec_blacklist_spotter, SpotSecurity::spotsec_view_statistics);
			foreach($authedPerms as $authedPerm) {
				$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . $authedPerm . ")");
			} # foreach

			/* Viewing of spotweb avatar images is a security right so administrators can globally disable this */
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid,objectid) VALUES(2, " . SpotSecurity::spotsec_view_spotimage . ", 'avatar')");
			
			/* Allow authenticated users to consume Spotweb using an API */
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_consume_api . ", 'rss')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_consume_api . ", 'newznabapi')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_consume_api . ", 'getnzb')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_consume_api . ", 'getspot')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_consume_api . ", 'getnzbmobile')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_download_integration . ", 'disable')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_download_integration . ", 'client-sabnzbd')");

			/* Allow certain notification services */
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ")");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(2, " . SpotSecurity::spotsec_send_notifications_services . ", 'welcomemail')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'email')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'notifo')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'twitter')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'prowl')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'nma')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_services . ", 'boxcar')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(4, " . SpotSecurity::spotsec_send_notifications_services . ", 'growl')");

			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ")");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'watchlist_handled')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'spot_posted')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'nzb_handled')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'report_posted')");

			/* Trusted users are allowed to some additional download integration options etc */
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(4, " . SpotSecurity::spotsec_download_integration . ", 'push-sabnzbd')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(4, " . SpotSecurity::spotsec_download_integration . ", 'save')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(4, " . SpotSecurity::spotsec_download_integration . ", 'runcommand')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(4, " . SpotSecurity::spotsec_download_integration . ", 'nzbget')");
			# Being able to erase downloads has become its seperate rights (GH issue #935)
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid,objectid) VALUES(4, " . SpotSecurity::spotsec_keep_own_downloadlist . ", 'erasedls')");

			/* Default permissions for administrative users */
			$adminPerms = array(SpotSecurity::spotsec_list_all_users, SpotSecurity::spotsec_retrieve_spots, SpotSecurity::spotsec_edit_other_users,
							 SpotSecurity::spotsec_delete_user, SpotSecurity::spotsec_edit_groupmembership, 
							 SpotSecurity::spotsec_display_groupmembership, SpotSecurity::spotsec_edit_securitygroups,
							 SpotSecurity::spotsec_set_filters_as_default, SpotSecurity::spotsec_view_spotweb_updates,
							 SpotSecurity::spotsec_edit_settings);
			foreach($adminPerms as $adminPerm) {
				$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid) VALUES(5, " . $adminPerm . ")");
			} # foreach

			# Notifications of these are only allowed to be sent to administrators
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(5, " . SpotSecurity::spotsec_send_notifications_types . ", 'retriever_finished')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(5, " . SpotSecurity::spotsec_send_notifications_types . ", 'user_added')");
		} # if

		########################################################################
		## Security level 0.28
		########################################################################
		if (($forceReset) || ($this->_settings->get('securityversion') < 0.28)) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_send_notifications_types . ", 'newspots_for_filter')");
		} # if

		########################################################################
		## Security level 0.29
		########################################################################
		if (($forceReset) || ($this->_settings->get('securityversion') < 0.29)) {
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_select_template . ", 'we1rdo')");
			$dbCon->rawExec("INSERT INTO grouppermissions(groupid,permissionid, objectid) VALUES(3, " . SpotSecurity::spotsec_select_template . ", 'mobile')");
		} # if
	} # updateSecurityGroups

	/*
	 * Update user filters
	 */
	function updateUserFilters($forceReset) {
		if (($this->_settings->get('securityversion') < 0.12) || ($forceReset)) {
			# DB connection
			$dbCon = $this->_db->getDbHandle();
			
			# delete all existing filters
			$dbCon->rawExec("DELETE FROM filters WHERE filtertype = 'filter'");

			$userList = $this->_db->getUserList();

			# loop through every user and fix it 
			foreach($userList as $user) {
				/* Image */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Image', 'film', 0, 0, 'cat0_z0')");
				$beeldFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'DivX', 'divx', 0, " . $beeldFilterId . ", 'cat0_z0_a0')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'WMV', 'wmv', 1, " . $beeldFilterId . ", 'cat0_z0_a1')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'MPEG', 'mpg', 2, " . $beeldFilterId . ", 'cat0_z0_a2')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'DVD', 'dvd', 3, " . $beeldFilterId . ", 'cat0_z0_a3,cat0_z0_a10')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'HD', 'hd', 4, " . $beeldFilterId . ", 'cat0_z0_a4,cat0_z0_a6,cat0_z0_a7,cat0_z0_a8,cat0_z0_a9')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Series', 'tv', 5, " . $beeldFilterId . ", 'cat0_z1')");

				/* Books */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Books', 'book', 6, " . $beeldFilterId . ", 'cat0_z2')");
				$boekenFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Dutch', 'book', 0, " . $boekenFilterId . ", 'cat0_z2_c11')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'English', 'book', 1, " . $boekenFilterId . ", 'cat0_z2_c10')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Others', 'book', 2, " . $boekenFilterId . ", 'cat0_z2,~cat0_z2_c10,~cat0_z2_c11')");
				
				/* Erotica */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Erotica', 'female', 7, " . $beeldFilterId. ", 'cat0_z3')");
				$erotiekFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Hetero', 'female', 0, " . $erotiekFilterId . ", 'cat0_z3_d75,cat0_z3_d23')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Gay male', 'female', 1, " . $erotiekFilterId . ", 'cat0_z3_d74,cat0_z3_d24')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Gay female', 'female', 2, " . $erotiekFilterId . ", 'cat0_z3_d73,cat0_z3_d25')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Bi', 'female', 3, " . $erotiekFilterId . ", 'cat0_z3_d72,cat0_z3_d26')");

				/* Music */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Sounds', 'music', 2, 0, 'cat1')");
				$muziekFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Compressed', 'music', 0, " . $muziekFilterId . ", 'cat1_a0,cat1_a3,cat1_a5,cat1_a6')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Lossless', 'music', 1, " . $muziekFilterId . ", 'cat1_a2,cat1_a4,cat1_a7,cat1_a8')");

				/* Games */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Games', 'controller', 3, 0, 'cat2')");
				$gameFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Windows', 'windows', 0, " . $gameFilterId . ", 'cat2_a0')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Mac / Linux', 'linux', 1, " . $gameFilterId . ", 'cat2_a1,cat2_a2')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Playstation', 'playstation', 2, " . $gameFilterId . ", 'cat2_a3,cat2_a4,cat2_a5,cat2_a12')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'XBox', 'xbox', 3, " . $gameFilterId . ", 'cat2_a6,cat2_a7')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Nintendo', 'nintendo_ds', 4, " . $gameFilterId . ", 'cat2_a8,cat2_a9,cat2_a10,cat2_a11')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Smartphone / PDA', 'pda', 5, " . $gameFilterId . ", 'cat2_a13,cat2_a14,cat2_a15')");

				/* Applications */
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Applications', 'application', 4, 0, 'cat3')");
				$appFilterId = $dbCon->lastInsertId('filters');
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Windows', 'vista', 0, " . $appFilterId . ", 'cat3_a0')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'Mac / Linux / OS2', 'linux', 1, " . $appFilterId . ", 'cat3_a1,cat3_a2,cat3_a3')");
				$dbCon->rawExec("INSERT INTO filters(userid,filtertype,title,icon,torder,tparent,tree) VALUES(" . $user['userid'] . ", 'filter', 'PDA / Navigation', 'pda', 2, " . $appFilterId . ", 'cat3_a4,cat3_a5,cat3_a6,cat3_a7')");
			} # foreach
		} # if
	} # updateUserFilters
	
	/*
	 * Update the current version of the settings
	 */
	function updateSecurityVersion() {
		# Ugly trick to trigger the autoloader to load the SpotSecurity class
		if (SpotSecurity::spotsec_perform_login == 0) { } ;
		
		$this->_settings->set('securityversion', SPOTWEB_SECURITY_VERSION);
	} # updateSecurityVersion

	/*
	 * Put a setting, but only if it doesn't exist
	 */
	function setSettingIfNot(&$pref, $name, $value) {
		if (isset($pref[$name])) {
			return ;
		} # if

		$pref[$name] = $value;
	} # setSettingIfNot
	
	/*
	 * Removes a setting
	 */
	function unsetSetting(&$pref, $name) {
		if (!isset($pref[$name])) {
			return ;
		} # if

		unset($pref[$name]);
	} # setSettingIfNot
	 
} # SpotUserUpgrader
