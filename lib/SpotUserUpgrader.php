<?php
class SpotUserUpgrader {
	private $_db;

	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function update() {
		$this->createAnonymous();
		$this->createAdmin();
		
		$this->updateUserPreferences();
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
		$dbCon->rawExec("INSERT INTO usergroups(userid,groupid, prio) VALUES(1, 1, 1)");
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
		$passSalt = $dbCon->singleQuery("SELECT value FROM settings WHERE name = 'pass_salt'");
		
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
			# Omdat we vanuti listUsers() niet alle velden meekrijgen
			# vragen we opnieuw het user record op
			$user = $this->_db->getUser($user['userid']);

			# set the users' preferences
			$this->setSettingIfNot($user['prefs'], 'perpage', '50');
			$this->setSettingIfNot($user['prefs'], 'date_formatting', 'human');
			$this->setSettingIfNot($user['prefs'], 'template', 'we1rdo');
			$this->setSettingIfNot($user['prefs'], 'perpage', '50');
			$this->setSettingIfNot($user['prefs'], 'count_newspots', true);
			$this->setSettingIfNot($user['prefs'], 'keep_seenlist', true);
			$this->setSettingIfNot($user['prefs'], 'auto_markasread', true);
			$this->setSettingIfNot($user['prefs'], 'keep_downloadlist', true);
			$this->setSettingIfNot($user['prefs'], 'keep_watchlist', true);
			$this->setSettingIfNot($user['prefs'], 'search_url', 'nzbindex');

			# update the user record in the database			
			$this->_db->setUser($user);
		} # foreach
	} # update()

	/*
	 * Set een setting alleen als hij nog niet bestaat
	 */
	function setSettingIfNot(&$pref, $name, $value) {
		if (isset($pref[$name])) {
			return ;
		} # if

		$pref[$name] = $value;
	} # setSettingIfNot
} # SpotUserUpgrader
