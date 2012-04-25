<?php
define('SPOTWEB_ANONYMOUS_USERID', 1);
define('SPOTWEB_ADMIN_USERID', 2);

class SpotUserSystem {
	private $_db;
	private $_settings;
	
	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Generates an unique id, mostly used for sessions
	 */
	function generateUniqueId() {
		$sessionId = '';
		
		for($i = 0; $i < 10; $i++) {
			$sessionId .= base_convert(mt_rand(), 10, 36);
		} # for
		
		return $sessionId;
	} # generateUniqueId
	
	/*
	 * Create a new session for the userid
	 */
	public function createNewSession($userid) {
		# If this is an actual user, we need to have the user record
		$tmpUser = $this->getUser($userid);
		
		/*
		 * If this is an anonymous user, or if the user has never
		 * logged in before, the last visit time is always the 
		 * session creation time.
		 *
		 * We do not use the 'nonauthencated_userid' for this because
		 * it would result in loss of read data for single-user systems
		 */
		if (($userid == SPOTWEB_ANONYMOUS_USERID) || ($tmpUser['lastlogin'] == 0)) {
			$tmpUser['lastvisit'] = time();
			
			# Mark everything as read for anonymous users
			$this->_db->markFilterCountAsSeen($userid);
		} else {
			$tmpUser['lastvisit'] = $tmpUser['lastlogin'];
		} # if

		# Create a new session record
		$session = array('sessionid' => $this->generateUniqueId(),
						 'userid' => $userid,
						 'hitcount' => 1,
						 'lasthit' => time(),
						 'ipaddr' => $this->determineUsersIpAddress()
						 );

		/*
		 * To prevent flooding the sessions table, we 
		 * don't actually create the db entry for anonymous 
	 	 * sessions. We can only do this for 'real' anonymous
	 	 * users because when this is overriden, the new 
	 	 * anoonymous user might have given additional features
	 	 */
		if ($userid != SPOTWEB_ANONYMOUS_USERID) {
			$this->_db->addSession($session);
		} # if
		
		return array('user' => $tmpUser,
					 'session' => $session);
	} # createNewSession

	/* 
	 * Update the users cookie
	 */
	function updateCookie($userSession) {
		SetCookie("spotsession",
				  $userSession['session']['sessionid'] . '.' . $userSession['user']['userid'],
				  (time() + (max(1, (int) $this->_settings->get('cookie_expires')) * 60*60*24)),
				  '', # path: The default value is the current directory that the cookie is being set in.
				  $this->_settings->get('cookie_host'),
				  false,	# Indicates if the cookie should only be transmitted over a secure HTTPS connection from the client.
				  true);	# Only available to the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
	} # updateCookie
	
	/*
	 * Removes a session from the database. 
	 */
	function removeSession($sessionId) {
		$this->_db->deleteSession($sessionId);
	} # removeSession
	
	/*
	 * Removes all users' sessions from the database
	 */
	function removeAllUserSessions($userId) {
		$this->_db->deleteAllUserSessions($userId);
	} # removeAllUserSessions
	
	/*
	 * Checks whether the user already has a session in its cookie. If it 
	 * has, we use the existing session, else we create a new one for the
	 * anonymous user.
	 */
	function useOrStartSession($forceAnonymous) {
		$userSession = false;
		
		if ((isset($_COOKIE['spotsession'])) && (!$forceAnonymous)) {
			$userSession = $this->validSession($_COOKIE['spotsession']);
		} # if

		if ($userSession === false) {
			/*
			 * If we don't have a session by now, let's create a new 
			 * anonymous session.
			 *
			 * UserID is our default anonymous user, but this can be 
			 * overriden by the usersystem
			 */
			$userSession = $this->createNewSession( $this->_settings->get('nonauthenticated_userid'));
		} # if
		
		# Initialize the security system
		$spotSec = new SpotSecurity($this->_db, $this->_settings, $userSession['user'], $userSession['session']['ipaddr']);
		$userSession['security'] = $spotSec;
		
		/*
		 * And always update the cookie even if one already exists,
		 * this prevents the cookie from expiring all of a sudden
		 */
		$this->updateCookie($userSession);
		
		return $userSession;
	} # useOrStartSession

	/*
	 * Password to hash
	 */
	function passToHash($password) {
		return sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)) . $password . $this->_settings->get('pass_salt'));
	} # passToHash

	/*
	 * Tries to authenticate the user with the given credentials.
	 * Returns an user record when authed, or false if the 
	 * authentication fails
	 */
	function login($user, $password) {
		# Sals the password with the unique salt given in the database
		$password = $this->passToHash($password);

		# authenticate the user
		$userId = $this->_db->authUser($user, $password);
		if ($userId !== false) {
			/*
			 * If the user is logged in, create a session.
			 *
			 * Order of actions is import here, because
			 * in a new session the lastvisit time is always
			 * set to the lastlogon time, therefore we first
			 * want the session to be created and after that
			 * we can update the last logon time 
			 */
			$userSession = $this->createNewSession($userId);
			$this->updateCookie($userSession);

			# now update the user record with the last logon time
			$userSession['user']['lastlogin'] = time();
			$this->_db->setUser($userSession['user']);

			# Initialize the security system
			$userSession['security'] = new SpotSecurity($this->_db, $this->_settings, $userSession['user'], $userSession['session']['ipaddr']);

			return $userSession;
		} else {
			return false;
		} # else
	} # login

	function verifyApi($apikey) {
		# try to authenticate the user
		$userId = $this->_db->authUser(false, $apikey);
		
		if ($userId !== false && $userId > SPOTWEB_ADMIN_USERID && $apikey != '') {
			/*
			 * In a normal logon, we need to have a session.
			 * For API logons, we do not want a session because
			 * that would bloat the session table.
			 *
			 * We therefore manually retrieve the user record
			 */
			$userRecord['user'] = $this->getUser($userId);

			# and use the userrecord to update the lastapiusage time
			$userRecord['user']['lastapiusage'] = time();
			$this->_db->setUser($userRecord['user']);

			# Initialize the security system
			$userRecord['security'] = new SpotSecurity($this->_db, $this->_settings, $userRecord['user'], $this->determineUsersIpAddress() );

			return $userRecord;
		} else {
			return false;
		} # else
	} # verifyApi

	/*
	 * Reset the seenstamp timestamp
	 */
	function resetReadStamp($user) {
		$user['lastvisit'] = time();
		$user['lastread'] = $this->_db->getMaxMessageTime();
		$this->_db->setUser($user);

		# Mark everything as read for this user
		$this->_db->markFilterCountAsSeen($user['userid']);
		
		return $user;
	} # resetReadStamp

	/*
	 * Checks whether an given session is valid. If the session
	 * is valid, this function returns an userrecord
	 */
	function validSession($sessionCookie) {
		$sessionParts = explode(".", $sessionCookie);
		if (count($sessionParts) != 2) {
			return false;
		} # if

		# Check whether the session is to be found in the database
		$sessionValid = $this->_db->getSession($sessionParts[0], $sessionParts[1]);
		if ($sessionValid === false) {
			return false;
		} # if
		
		# The session is valid, let's update the hit counter and retrieve the user
		$this->_db->hitSession($sessionParts[0]);
		$userRecord = $this->getUser($sessionValid['userid']);
		
		/*
		 * If the user could not be found, the session wasn't valid after all
		 */
		if ($userRecord === false) {
			return false;
		} # if

		/*
		 * Now determine whether we need to update the lastvisit timestamp.
		 *
		 * If the *lasthit* is older than 15 minutes, we update the *lastvisit* 
		 * timestamp to the *lasthit* time. 
		 *
		 * Basically this makes sure the 'lastvisit' time is only reset when
		 * the user wasn't active on Spotweb for 15 minutes. This ensures us
		 * the unread count for the user doesn't get unset all of a sudden
		 * during a browsing session.
		 */
		if ($sessionValid['lasthit'] < (time() - 900)) {
			$userRecord['lastvisit'] = $sessionValid['lasthit'];
			
			/*
			 * Update the last read time to the last spot we find in the
			 * database. Theoreticall this still contains an race condtion
			 * because the spots could be updated by now.
			 * 
			 * We ignore this for now to not cause any performance issues
			 */
			if ($userRecord['prefs']['auto_markasread']) {
				# Retrieve the last update stamp from the filters
				$filterHashes = $this->_db->getCachedFilterCount($userRecord['userid']);
				
				/* 
				 * Set the lastread stamp to the last time the spotcount was updated
				 * in the filtercounts
				 */
				if (!empty($filterHashes)) {
					$filterKeys = array_keys($filterHashes);
					$userRecord['lastread'] = $filterHashes[$filterKeys[0]]['lastupdate'];
				} else {
					$userRecord['lastread'] = $this->_db->getMaxMessageTime();
				} # else
				
				# Mark older spots as read for this user
				$this->_db->resetFilterCountForUser($userRecord['userid']);
			} # if
			
			$this->_db->setUser($userRecord);			
		} # if
		
		return array('user' => $userRecord,
					 'session' => $sessionValid);
	} # validSession
	
	/*
	 * Is this user allowed to post content like spam reports etc?
	 */
	function allowedToPost($user) {
		/*
		 * When an invalid (reserved) username is used, prevent
		 * posting 
		 */
		if (!$this->validUsername($user['username'])) {
			return false;
		} # if

		# Als de user niet ingelogged is, dan heeft dit geen zin
		if ($user['userid'] <= SPOTWEB_ADMIN_USERID) {
			return false;
		} # if

		return true;
	} # allowedToPost

	/*
	 * Validates a username
	 */
	function validUsername($user) {
		$invalidNames = array('god', 'mod', 'modje', 'spot', 'spotje', 'spotmod', 
							  'admin', 'drazix', 'moderator', 'superuser', 'supervisor', 
							  'spotnet', 'spotnetmod', 'administrator',  'spotweb',
							  'root', 'anonymous', 'spotlite');

		$validUsername = !in_array(strtolower($user), $invalidNames);
		if ($validUsername) {
			$validUsername = strlen($user) >= 3;
		} # if
		
		return $validUsername;
	} # validUsername

	/*
	 * Adds a user to the database
	 */
	function addUser($user) {
		if (!$this->validUsername($user['username'])) {
			throw new Exception("Invalid username");
		} # if

		# Convert the password to an passhash
		$user['passhash'] = $this->passToHash($user['newpassword1']);

		# Create an API key
		$user['apikey'] = md5($this->generateUniqueId());

		# and actually add the user to the database
		$tmpUser = $this->_db->addUser($user);
		$this->_db->setUserRsaKeys($tmpUser['userid'], $user['publickey'], $user['privatekey']);
		
		/*
		 * Now copy the preferences from the anonymous user to this
		 * new user 
		 */
		$anonUser = $this->_db->getUser($this->_settings->get('nonauthenticated_userid'));
		$tmpUser = array_merge($anonUser, $tmpUser);
		$tmpUser['prefs']['newspotdefault_tag'] = $user['username'];
		$this->_db->setUser($tmpUser);
		
		# and add the user to the default set of groups as configured
		$this->_db->setUserGroupList($tmpUser['userid'], $this->_settings->get('newuser_grouplist'));
		
		# now copy the users' filters to the new user
		$this->_db->copyFilterList($this->_settings->get('nonauthenticated_userid'), $tmpUser['userid']);
	} # addUser()

	/*
	 * Update a user's group membership
	 */
	function setUserGroupList($user, $groupList) {
		$this->_db->setUserGroupList($user['userid'], $groupList);
	} # setUserGroupList
	 
	/*
	 * Update a userid's password
	 */
	function setUserPassword($user) {
		# Convert the password to an passhash
		$user['passhash'] = $this->passToHash($user['newpassword1']);
		
		$this->_db->setUserPassword($user);
	} # setUserPassword

	/*
	 * Update a user's API key
	 */
	function resetUserApi($user) {
		$user['apikey'] = md5($this->generateUniqueId());
		
		$this->_db->setUser($user);
		return $user;
	} # setUserApi

	/*
	 * Merge an array recursively, overwriting
	 * existing values
	 *
	 * Code copied from 
	 *    http://nl3.php.net/manual/en/function.array-merge-recursive.php#106985
	 */
	function array_merge_recursive_overwrite() {
		$arrays = func_get_args();
		$base = array_shift($arrays);

		foreach ($arrays as $array) {
			reset($base); //important
			while (list($key, $value) = @each($array)) {
				if (is_array($value) && @is_array($base[$key])) {
					$base[$key] = $this->array_merge_recursive_overwrite($base[$key], $value);
				} else {
					$base[$key] = $value;
				} # else
			} # while
		} # foreach

		return $base;
	} # array_merge_recursive_overwrite

	/* 
	 * Cleanup of user preferences
	 */
	function cleanseUserPreferences($prefs, $tpl) {
		/*
		 * We do not want any user preferences to be submitted which aren't in the anonuser preferences,
		 * as this would allow garbage preferences or invalid settings for non-existing preferences.
		 *
		 * A simple recursive merge with the anonuser preferences is not possible because some browsers
		 * just don't submit the values of a checkbox when the checkbox is deselected, in that case the
		 * anonuser's settings would be set instead of the false setting as it should be.
		 *
		 * We solve this by simply setting the values of all the checkboxes and then performing
		 * a recursive merge
		 *
		 * Convert other settings to booleans so we always have a valid result.
		 * We need to do this because not all browsers post checkboxes in a form in
		 * the same way.
		 */
		$tpl['count_newspots'] = (isset($prefs['count_newspots'])) ? true : false;
        $tpl['mouseover_subcats'] = (isset($prefs['mouseover_subcats'])) ? true : false;
		$tpl['keep_seenlist'] = (isset($prefs['keep_seenlist'])) ? true : false;
		$tpl['auto_markasread'] = (isset($prefs['auto_markasread'])) ? true : false;
		$tpl['keep_downloadlist'] = (isset($prefs['keep_downloadlist'])) ? true : false;
		$tpl['keep_watchlist'] = (isset($prefs['keep_watchlist'])) ? true : false;
		$tpl['show_filesize'] = (isset($prefs['show_filesize'])) ? true : false;
		$tpl['show_reportcount'] = (isset($prefs['show_reportcount'])) ? true : false;
		$tpl['show_nzbbutton'] = (isset($prefs['show_nzbbutton'])) ? true : false;
		$tpl['show_multinzb'] = (isset($prefs['show_multinzb'])) ? true : false;
		$tpl['show_avatars'] = (isset($prefs['show_avatars'])) ? true : false;
		
		$notifProviders = Notifications_Factory::getActiveServices();
		foreach ($notifProviders as $notifProvider) {
			$tpl['notifications'][$notifProvider]['enabled'] = (isset($prefs['notifications'][$notifProvider]['enabled'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['watchlist_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['watchlist_handled'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['nzb_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['nzb_handled'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['retriever_finished'] = (isset($prefs['notifications'][$notifProvider]['events']['retriever_finished'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['report_posted'] = (isset($prefs['notifications'][$notifProvider]['events']['report_posted'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['spot_posted'] = (isset($prefs['notifications'][$notifProvider]['events']['spot_posted'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['user_added'] = (isset($prefs['notifications'][$notifProvider]['events']['user_added'])) ? true : false;
			$tpl['notifications'][$notifProvider]['events']['newspots_for_filter'] = (isset($prefs['notifications'][$notifProvider]['events']['newspots_for_filter'])) ? true : false;
		} # foreach

		# When nzbhandling settings are not entered at all, we default to disable
		if (!isset($prefs['nzbhandling'])) {
			$tpl['nzbhandling'] = array('action' => 'disable',
										  'prepare_action' => 'merge');										  
		} # if

		/*
		 * Unset any keys in the preferences which aren't available 
		 * in the preferences template (anonyuser)
		 */
		foreach(array_diff_key($prefs, $tpl) as $keys => $values) {
			unset($prefs[$keys]);
		} # foreach

		/* 
		 * Of course array_merge_recursive doesn't do what one would
		 * expect it to do and merge embedded arrays by combining them
		 * instead of overwriting key values...
		 */ 
		$prefs = $this->array_merge_recursive_overwrite($tpl, $prefs);

		return $prefs;
	} # cleanseUserPreferences
	
	/*
	 * Validate user preferences
	 */
	function validateUserPreferences($prefs, $currentPrefs) {
		$errorList = array();
		
		# Define several arrays with valid settings
		$validDateFormats = array('human', '%a, %d-%b-%Y (%H:%M)', '%d-%m-%Y (%H:%M)');
		$validTemplates = array('we1rdo');
		$validDefaultSorts = array('', 'stamp');
		$validLanguages = array_keys($this->_settings->get('system_languages'));
		
		# Check per page setting
		$prefs['perpage'] = (int) $prefs['perpage'];
		if (($prefs['perpage'] < 2) || ($prefs['perpage'] > 250)) {
			$errorList[] = _('Invalid preference value (perpage)');
		} # if
		
		# Controleer basis settings
		if (in_array($prefs['date_formatting'], $validDateFormats) === false) {
			$errorList[] = _('Invalid user preference value (date_formatting)');
		} # if
		
		if (in_array($prefs['template'], $validTemplates) === false) { 	
			$errorList[] = _('Invalid user preference value (template)');
		} # if

		if (in_array($prefs['user_language'], $validLanguages) === false) { 	
			$errorList[] = _('Invalid user preference value (language)');
		} # if

		if (in_array($prefs['defaultsortfield'], $validDefaultSorts) === false) { 	
			$errorList[] = _('Invalid user preference value (defaultsortfield)');
		} # if
		
		# when an sabnzbd host is entered, it has to be a valid URL
		if ( ($prefs['nzbhandling']['action'] == 'client-sabnzbd') || ($prefs['nzbhandling']['action'] == 'push-sabnzbd') ) {
			$tmpHost = parse_url($prefs['nzbhandling']['sabnzbd']['url']);
			
			if ( ($tmpHost === false) | (!isset($tmpHost['scheme'])) || (($tmpHost['scheme'] != 'http') && ($tmpHost['scheme'] != 'https')) ) {
				$errorList[] = _('sabnzbd host is not a valid URL');
			} # if
			
			# SABnzbd URL should always end with a s slash
			if(substr($prefs['nzbhandling']['sabnzbd']['url'], -1) !== '/') {
				$prefs['nzbhandling']['sabnzbd']['url'] .= '/';
			} # if
		} # if

		# Twitter tokens are never posted by the form, but they shouldn't be tossed out
		$prefs['notifications']['twitter']['screen_name'] = $currentPrefs['notifications']['twitter']['screen_name'];
		$prefs['notifications']['twitter']['access_token'] = $currentPrefs['notifications']['twitter']['access_token'];
		$prefs['notifications']['twitter']['access_token_secret'] = $currentPrefs['notifications']['twitter']['access_token_secret'];
		$prefs['notifications']['twitter']['request_token'] = $currentPrefs['notifications']['twitter']['request_token'];
		$prefs['notifications']['twitter']['request_token_secret'] = $currentPrefs['notifications']['twitter']['request_token_secret'];

		# We don't want to save megabyts of CSS, so put a limit to the size
		if (strlen($prefs['customcss'] > 1024 * 10)) { 
			$errorList[] = _('Custom CSS is too large');
		} # if		

		# We don't want to save megabytes of default newspot body, so limit it
		if (strlen($prefs['newspotdefault_tag'] > 90)) { 
			$errorList[] = _('Default value for a spots\' tag is too long');
		} # if		
		
		if (strlen($prefs['newspotdefault_body'] > 9000)) { 
			$errorList[] = _('Default value for a spots\' body is too long');
		} # if		
		
		# When a 'runcommand' or 'save' action is chosen, 'local_dir' is a mandatry setting
		if (($prefs['nzbhandling']['action'] == 'save') || ($prefs['nzbhandling']['action'] == 'runcommand')) {
			if (empty($prefs['nzbhandling']['local_dir'])) {
				$errorList[] = _('When NZB handling is either "save" or "runcommand" the directory must be entered');
			} # if
		} # if

		# When a 'runcommand' action is chosen, 'command' is a mandatry setting
		if ($prefs['nzbhandling']['action'] == 'runcommand') {
			if (empty($prefs['nzbhandling']['command'])) {
				$errorList[] = _('When NZB handling is "runcommand" a command must be entered');
			} # if
		} # if

		# For the 'growl' notification provider, a host is mandatory
		if ($prefs['notifications']['growl']['enabled']) {
			if (empty($prefs['notifications']['growl']['host'])) {
				$errorList[] = _('Growl notifications require a growl host to be entered');
			} # if
		} # if

		# 'Notify My Android' requires an API key
		if ($prefs['notifications']['nma']['enabled']) {
			if (empty($prefs['notifications']['nma']['api'])) {
				$errorList[] = _('"Notify My Android" notifications require an API key');
			} # if
		} # if

		# 'Notifo' requires both a username and apikey
		if ($prefs['notifications']['notifo']['enabled']) {
			if (empty($prefs['notifications']['notifo']['username'])) {
				$errorList[] = _('"Notifo" notifications require an username to be entered');
			} # if
			if (empty($prefs['notifications']['notifo']['api'])) {
				$errorList[] = _('"Notifo" notifications require an api key to be entered');
			} # if
		} # if

		# 'Prowl' requires an API key
		if ($prefs['notifications']['prowl']['enabled']) {
			if (empty($prefs['notifications']['prowl']['apikey'])) {
				$errorList[] = _('"Prowl" notifications require an API key to be entered');
			} # if
		} # if

		# To use Twitter, an twitter account should be defined
		if ($prefs['notifications']['twitter']['enabled']) {
			if (empty($prefs['notifications']['twitter']['access_token']) || empty($prefs['notifications']['twitter']['access_token_secret'])) {
				$errorList[] = _('To use twitter you need to enter and validate a twitter account');
			} # if
		} # if

		return array($errorList, $prefs);
	} # validateUserPreferences

	/*
	 * Validate the user record. Might be used for both adding and changing
	 */
	function validateUserRecord($user, $isEdit) {
		$errorList = array();
		
		# Make sure the username is valid
		if (!$isEdit) {
			if (!$this->validUsername($user['username'])) {
				$errorList[] = _('Invalid username chosen');
			} # if
		} # if
		
		# Check a firstname is entered
		if (strlen($user['firstname']) < 2) {
			$errorList[] = _('Not a valid firstname');
		} # if
		
		# Check a lastname is entered
		if (strlen($user['lastname']) < 2) {
			$errorList[] = _('Not a valid lastname');
		} # if

		# Make sure a valid password is entered for existing users
		if ((strlen($user['newpassword1'] > 0)) && ($isEdit)) {
			if (strlen($user['newpassword1']) < 5){
				$errorList[] = _('Entered password is too short');
			} # if
		} # if

		# Make sure a valid password is entered for new users
		if ((strlen($user['newpassword1']) < 5) && (!$isEdit)) {
			$errorList[] = _('Entered password is too short');
		} # if

		# and make sure the passwords match
		if ($user['newpassword1'] != $user['newpassword2']) {
			$errorList[] = _('Passwords do not match');
		} # if
		
		# check the mailaddress
		if (!filter_var($user['mail'], FILTER_VALIDATE_EMAIL)) {
			$errorList[] = _('Not a valid email address');
		} # if

		# and make sure the mailaddress is unique among all users
		$emailExistResult = $this->_db->userEmailExists($user['mail']);
		if (($emailExistResult !== $user['userid']) && ($emailExistResult !== false)) {
			$errorList[] = _('Mailaddress is already in use');
		} # if
		
		return $errorList;
	} # validateUserRecord
	
	/*
	 * Set the users' public and private keys
	 */
	function setUserRsaKeys($user, $privateKey, $publicKey) {
		$this->_db->setUserRsaKeys($user['userid'], $privateKey, $publicKey);
	} # setUserRsaKeys
	
	/*
	 * Validate a group record
	 */
	function validateSecGroup($group) {
		$errorList = array();

		# Remove any lingering spaces
		$group['name'] = trim($group['name']);
		
		# Ensure a gorupname is given and it is not too short
		if (strlen($group['name']) < 3) {
			$errorList[] = _('Invalid groupname');
		} # if

		/*
		 * Now list all security groups to make sure the groupname
		 * is unique.
		 *
		 * This is not the most efficient way to do stuff, but we 
		 * do not expect dozens of security groups so this is acceptable
		 */
		$secGroupList = $this->_db->getGroupList(null);
		foreach($secGroupList as $secGroup) {
			if ($secGroup['name'] == $group['name']) {
				if ($secGroup['id'] != $group['id']) {
					$errorList[] = _('Name is already in use');
				} # if
			} # if
		} # foreach
		
		return array($errorList, $group);
	} # validateSecGroup

	/*
	 * Removes a permission from a securitygroup
	 */
	function removePermFromSecGroup($groupId, $perm) {
		$this->_db->removePermFromSecGroup($groupId, $perm);
	} # removePermFromSecGroup

	/*
	 * Sets a speific permission in a group to either allow or deny
	 */
	function setDenyForPermFromSecGroup($groupId, $perm) {
		$this->_db->setDenyForPermFromSecGroup($groupId, $perm);
	} # setDenyForPermFromSecGroup
	
	/*
	 * Adds a permission to an security group
	 */
	function addPermToSecGroup($groupId, $perm) {
		$errorList = array();
		
		# Remove any superfluous spaces
		$perm['objectid'] = trim($perm['objectid']);
		
		/*
		 * Make sure this specific permission is unique in the group
		 *
		 * We do not check the deny here, because we do not want
		 * groups with both a deny and an allow setting as the results
		 * would be undefined
		 */
		$groupPerms = $this->_db->getGroupPerms($groupId);
		foreach($groupPerms as $groupPerm) {
			if (($groupPerm['permissionid'] == $perm['permissionid']) && 
				($groupPerm['objectid'] == $perm['objectid'])) {
				
				# Duplicate permission
				$errorList[] = _('Permission already exists in this group');
			} # if
		} # foreach
	
		# Add the permission to the group
		if (empty($errorList)) {
			$this->_db->addPermToSecGroup($groupId, $perm);
		} # if
		
		return $errorList;
	} # addPermToSecGroup
	
	/*
	 * Update a group record
	 */
	function setSecGroup($group) {
		$this->_db->setSecurityGroup($group);
	} # setSecGroup

	/*
	 * Add an security group
	 */
	function addSecGroup($group) {
		$this->_db->addSecurityGroup($group);
	} # addSecGroup
	
	/*
	 * Retrieve a group record 
	 */
	function getSecGroup($groupId) {
		$tmpGroup = $this->_db->getSecurityGroup($groupId);
		if (!empty($tmpGroup)) {
			return $tmpGroup[0];
		} else {
			return false;
		} # else
	} # getSecGroup

	/*
	 * Removes a group record
	 */
	function removeSecGroup($group) {
		$this->_db->removeSecurityGroup($group);
	} # removeSecGroup

	/*
	 * Retrieves an user record
	 */
	function getUser($userid) {
		$tmpUser = $this->_db->getUser($userid);
		
		return $tmpUser;
	} # getUser()

	/*
	 * Retrieves an unformatted filterlist
	 */
	function getPlainFilterList($userId, $filterType) {
		return $this->_db->getPlainFilterList($userId, $filterType);
	} # get PlainFilterList
	
	/*
	 * Retrieves a list of filters (in an hierarchical list)
	 */
	function getFilterList($userId, $filterType) {
		return $this->_db->getFilterList($userId, $filterType);
	} # getFilterList
	
	/*
	 * Retrieves one specific filter
	 */
	function getFilter($userId, $filterId) {
		return $this->_db->getFilter($userId, $filterId);
	} # getFilter

	/*
	 * Changes the filter values.
	 *
	 * For now only the following values might be changed:
	 *
	 *   * Title
	 *   * Order
	 *   * Parent
	 */
	function changeFilter($userId, $filterForm) {
		return $this->_db->updateFilter($userId, $filterForm);
	} # changeFilter


	/*
	 * Validates a filter
	 */
	function validateFilter($filter) {
		$errorList = array();

		# Remove any spaces 
		$filter['title'] = trim(utf8_decode($filter['title']), " \t\n\r\0\x0B");
		$filter['title'] = trim(utf8_decode($filter['title']), " \t\n\r\0\x0B");
		
		# Make sure a filter name is valid
		if (strlen($filter['title']) < 3) {
			$errorList[] = _('Invalid filter name');
		} # if
		
		return array($filter, $errorList);
	} # validateFilter
	
	/*
	 * Adds a filter to a user
	 */
	function addFilter($userId, $filter) {
		$errorList = array();
		list($filter, $errorList) = $this->validateFilter($filter);
		
		# No errors found? add it to the datbase
		if (empty($errorList)) {
			$this->_db->addFilter($userId, $filter);
		} # if
		
		return $errorList;
	} # addFilter
	
	/*
	 * Retrieves the users' index filter
	 */
	function getIndexFilter($userId) {
		/*
		 * The users' index filter is usually retrieved two or 
		 * thee times for the index page, make sure we don't approach
		 * the database that many times
		 */
		$userIndexFilter = $this->_db->getUserIndexFilter($userId);
		
		if ($userIndexFilter === false) {
			return array('tree' => '');
		} else {
			return $userIndexFilter;
		} # else
	} # getIndexFilter
	
	/*
	 * Add user's index filter
	 */
	function setIndexFilter($userId, $filter) {
		# There can only be one 
		$this->removeIndexFilter($userId);
		
		# and actually add the index filter
		$filter['filtertype'] = 'index_filter';
		$this->_db->addFilter($userId, $filter);
	} # addIndexFilter
	
	/*
	 * Remove an index filter
	 */
	function removeIndexFilter($userId) {
		$tmpFilter = $this->_db->getUserIndexFilter($userId);
		
		if (!empty($tmpFilter)) {
			$this->_db->deleteFilter($userId, $tmpFilter['id'], 'index_filter');
		} # if
	} # removeIndexFilter

	/*
	 * Removes a userfilter
	 */
	function removeFilter($userId, $filterId) {
		$this->_db->deleteFilter($userId, $filterId, 'filter');
	} # removeFilter
	
	/*
	 * Removes all existing filters for a user, and reset its
	 * filerlist to the one for the system defined anonymous account
	 */
	function resetFilterList($userId) {
		# Remove all filters
		$this->_db->removeAllFilters($userId);
		
		# and copy them back from the userlist
		$this->_db->copyFilterList($this->_settings->get('nonauthenticated_userid'), $userId);
	} # resetFilterList

	/*
	 * Set the filterlist as specified
	 */
	function setFilterList($userId, $filterList) {
		# remove all existing filters
		$this->_db->removeAllFilters($userId);
		
		# and add the filters from the list
		foreach($filterList as $filter) {
			$this->_db->addFilter($userId, $filter);
		} # foreach
	} # setFilterList
	
	/*
	 * Copy the filters from a specific user to be the
	 * default filters
	 */
	function setFiltersAsDefault($userId) {
		# Remove all filters for the Anonymous user
		$this->_db->removeAllFilters($this->_settings->get('nonauthenticated_userid'));
		
		# and copy them from the specified user to anonymous
		$this->_db->copyFilterList($userId, $this->_settings->get('nonauthenticated_userid'));
	} # setFiltersAsDefault

	/*
	 * Update a user record (does not change the password)
	 */
	function setUser($user) {
		/*
		 * We always assume the password is not set using
		 * this function, hence the password is never updated
		 * by setUser()
		 */
		$this->_db->setUser($user);
	} # setUser()
	
	/*
	 * Removes an user record
	 */
	function removeUser($userid) {
		$this->_db->deleteUser($userid);
	} # removeUser()

	/*
	 * Retrieves an RSA key from the users' record.
	 */
	function getUserPrivateRsaKey($userId) {
		return $this->_db->getUserPrivateRsaKey($userId);
	} # getUserPrivateRsaKey
	
	/*
	 * Converts a list of filters to an XML record which should
	 * be interchangeable
	 */
	public function filtersToXml($filterList) {
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# create the XML document
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$mainElm = $doc->createElement('spotwebfilter');
		$mainElm->appendChild($doc->createElement('version', '1.0'));
		$mainElm->appendChild($doc->createElement('generator', 'SpotWeb v' . SPOTWEB_VERSION));
		$doc->appendChild($mainElm);

		$filterListElm = $doc->createElement('filters');

		foreach($filterList as $filter) {
			$filterElm = $doc->createElement('filter');
			
			$filterElm->appendChild($doc->createElement('id', $filter['id']));
			$filterElm->appendChild($doc->createElement('title', $filter['title']));
			$filterElm->appendChild($doc->createElement('icon', $filter['icon']));
			$filterElm->appendChild($doc->createElement('parent', $filter['tparent']));
			$filterElm->appendChild($doc->createElement('order', $filter['torder']));
			$filterElm->appendChild($doc->createElement('enablenotify', $filter['enablenotify']));

			/* 
			 * Now add the tree. We get the list of filters as a tree, but we 
			 * want to keep the XML as clean as possible so we try to compress it.
			 *
			 * First we have to extract the tree to a list of selections, strongnots
			 * and excludes
			 */
			$dynaList = explode(',', $filter['tree']);
			list($categoryList, $strongNotList) = $spotsOverview->prepareCategorySelection($dynaList);
			$treeList = explode(',', $spotsOverview->compressCategorySelection($categoryList, $strongNotList));
			$tree = $doc->createElement('tree');
			foreach($treeList as $treeItem) { 
				if (!empty($treeItem)) {
					# determine what type of element this is
					$treeType = 'include';
					if ($treeItem[0] == '~') {
						$treeType = 'strongnot';
						$treeItem = substr($treeItem, 1);
					} elseif ($treeItem[1] == '!') {
						$treeType = 'exclude';
						$treeItem = substr($treeItem, 1);
					} # else
					
					# and create the XML item
					$treeElm = $doc->createElement('item', $treeItem);
					$treeElm->setAttribute('type', $treeType);

					if (!empty($treeItem)) {
						$tree->appendChild($treeElm);
					} # if
				} # if
			} # treeItems
			$filterElm->appendChild($tree);

			/* 
			 * Prepareer the filtervalue list to make it usable for the XML
			 */
			$tmpFilterValues = explode('&', $filter['valuelist']);
			$filterValueList = array();
			foreach($tmpFilterValues as $filterValue) {
				$tmpFilter = explode(':', urldecode($filterValue));
				
				# and create the actual filter
				if (count($tmpFilter) >= 3) {
					$filterValueList[] = Array('fieldname' => $tmpFilter[0],
											 'operator' => $tmpFilter[1],
											 'value' => join(":", array_slice($tmpFilter, 2)));
				} # if
			} # foreach

			/* 
			 * Now add the filter items (text searches etc)
			 */
			 if (!empty($filterValueList)) {
				 $valuesElm = $doc->createElement('values');
				 foreach($filterValueList as $filterValue) {
					# Create the value XML item
					$itemElm = $doc->createElement('item');
					$itemElm->appendChild($doc->createElement('fieldname', $filterValue['fieldname']));
					$itemElm->appendChild($doc->createElement('operator', $filterValue['operator']));
					$itemElm->appendChild($doc->createElement('value', $filterValue['value']));

					$valuesElm->appendChild($itemElm);
				 } # foreach
				$filterElm->appendChild($valuesElm);
			} # if
			 
			/* 
			 * Add the sorting items
			 */
			if (!empty($filter['sorton'])) {
				$sortElm = $doc->createElement('sort');

				$itemElm = $doc->createElement('item');
				$itemElm->appendChild($doc->createElement('fieldname', $filter['sorton']));
				$itemElm->appendChild($doc->createElement('direction', $filter['sortorder']));

				$sortElm->appendChild($itemElm);
				$filterElm->appendChild($sortElm);
			} # if

			$filterListElm->appendChild($filterElm);
		} # foreach
		
		$mainElm->appendChild($filterListElm);

		return $doc->saveXML();
	} # filtersToXml 

	/*
	 * Translates an XML string back to a list of filters
	 */
	public function xmlToFilters($xmlStr) {
		$filterList = array();
		$idMapping = array();
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		/*
		 * Parse the XML file
		 */		
		$xml = @(new SimpleXMLElement($xmlStr));

		# We can only parse version 1.0 of the filters
		if ( (string) $xml->version != '1.0') {
			return $filterList;
		} # if

		# and try to process all of the filters
		foreach($xml->xpath('/spotwebfilter/filters/filter') as $filterItem) {
			$filter['id'] = (string) $filterItem->id;
			$filter['title'] = (string) $filterItem->title;
			$filter['icon'] = (string) $filterItem->icon;
			$filter['tparent'] = (string) $filterItem->parent;
			$filter['torder'] = (string) $filterItem->order;
			$filter['filtertype'] = 'filter';
			$filter['sorton'] = '';
			$filter['sortorder'] = '';
			$filter['tree'] = '';
			$filter['enablenotify'] = (boolean) $filterItem->enablenotify;
			$filter['children'] = array();

			/*
			 * start with the tree items
			 */
			$treeStr = "";
			foreach($filterItem->xpath('tree/item') as $treeItem) {
				$treeType = (string) $treeItem->attributes()->type;
				if ($treeType == 'exclude') {
					$treeStr .= ',!' . $treeItem[0];
				} elseif ($treeType == 'strongnot') {
					$treeStr .= ',~' . $treeItem[0];
				} elseif ($treeType == 'include') {
					$treeStr .= ',' . $treeItem[0];
				} # if
			} # foreach
			
			if (strlen($treeStr) > 1) {
				$treeStr = substr($treeStr, 1);
			} # if
			
			$filter['tree'] = $treeStr;

			/*
			 * now parse the values (textsearches etc)
			 */
			$filterValues = array();
			foreach($filterItem->xpath('values/item') as $valueItem) {
				$value = array();

				$filterValues[] = urlencode(
								   (string) $valueItem->fieldname . 
									':' . 
								   (string) $valueItem->operator . 
									':' . 
								   (string) $valueItem->value
								  );
			} # foreach
			$filter['valuelist'] = $filterValues;

			/* 
			 * Sorting elements are optional
			 */
			if ($filterItem->sort) {
				$filter['sorton'] = (string) $filterItem->sort->item->fieldname;
				$filter['sortorder'] = (string) $filterItem->sort->item->direction;
			} # if
			
			$filterList[$filter['id']] = $filter;
		} # foreach
		
		/*
		 * Now create a tree out of it. We cannot do this the same way
		 * as in SpotDb because we cannot create references to the XPATH
		 * function
		 */
		 foreach($filterList as $idx => &$filter) {
			if (($filter['tparent'] != 0) && (isset($filterList[$filter['tparent']]))) {
				$filterList[$filter['tparent']]['children'][] =& $filter;
			} # if
		} # foreach
		
		/*
		 * we have to run it in two passes because unsetting it 
		 * will result in an incorrect result on an nested-nested
		 * list
		 */
		foreach($filterList as $idx => &$filter) {
			if (($filter['tparent'] != 0) && (isset($filterList[$filter['tparent']]))) {
				unset($filterList[$filter['id']]);
			} # if
		} # foreach
		
		return $filterList;
	} # xmlToFilters
	
	/*
	 * Changes the avatar of this user
	 */
	function changeAvatar($userId, $imageFile) {
		$errorList = array();
		
		/* 
		 * Don't allow images larger than 4000 bytes
		 */
		if (strlen($imageFile) > 4000) {
			$errorList[] = _('An avatar image has a maximum of 4000 bytes');
		} # if
		
		/*
		 * Make sure the image can be read, and stuff
		 */
		$spotImage = new SpotImage($this->_db);
		if ($spotImage->getImageInfoFromString($imageFile) === false) {
			$errorList[] = _('Invalid avatar image was supplied');
		} # if

		if (empty($errorList)) {
			/*
			 * We store the images base64 encoded
			 */
			$imageFile = base64_encode($imageFile);
			
			/*
			 * and update the database 
			 */
			$this->_db->setUserAvatar($userId, $imageFile);
		} # if

		return $errorList;
	} # changeAvatar
	
	/*
	 * Blacklist a specific spotter
	 */
	function addSpotterToList($ourUserId, $spotterId, $origin, $idtype) {
		if (($idtype < 0) || ($idtype > 2)) {
			/* Invalid id type, dont allow this */
			return ;
		} # if

		$this->_db->addSpotterToList($spotterId, $ourUserId, $origin, $idtype);
	} # addSpotterToList	

	/*
	 * Removes a specific spotter from the blacklis
	 */
	function removeSpotterFromList($ourUserId, $spotterId) {
		$this->_db->removeSpotterFromList($spotterId, $ourUserId);
	} # removeSpotterFromList
	
	/*
	 * Returns the users' remote IP address
	 */
	function determineUsersIpAddress() {
		/*
		 * We now compare the X-Fowarded-For header and it's not clear if this 
		 * is the right thing to do.
		 */
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
						$remote_addr = $ip;
					} # if
				} # foreach
			} # if
		} # foreach
		
		if (isset($remote_addr)) {
			return $remote_addr;
		} else {
			return "N/A";
		} # if
	} # determineUsersIpAddress
	
} # class SpotUserSystem
