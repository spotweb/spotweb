<?php

class Dao_Base_User implements Dao_User {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_UserFilterCount object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor


	/*
	 * Returns the user id for a given username
	 */
	function findUserIdForName($username) {
		return $this->_conn->singleQuery("SELECT id FROM users WHERE username = '%s'", Array($username));
	} # findUserIdForName

	/*
	 * Determines whether an email address exists
	 */
	function userEmailExists($mail) {
		$tmpResult = $this->_conn->singleQuery("SELECT id FROM users WHERE mail = '%s'", Array($mail));
		
		if (!empty($tmpResult)) {
			return $tmpResult;
		} # if

		return false;
	} # userEmailExists

	/*
	 * Retrieves a user from the database
	 */
	function getUser($userid) {
		$tmp = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								u.firstname AS firstname,
								u.lastname AS lastname,
								u.mail AS mail,
								u.apikey AS apikey,
								u.deleted AS deleted,
								u.lastlogin AS lastlogin,
								u.lastvisit AS lastvisit,
								u.lastread AS lastread,
								u.lastapiusage AS lastapiusage,
								s.publickey AS publickey,
								s.avatar AS avatar,
								s.otherprefs AS prefs
						 FROM users AS u
						 JOIN usersettings s ON (u.id = s.userid)
						 WHERE u.id = %d AND NOT DELETED",
				 Array( (int) $userid ));

		if (!empty($tmp)) {
			/*
			 * Other preferences are stored 'serialized' in the database to make 
			 * extensibility easy
			 */
			$tmp[0]['prefs'] = unserialize($tmp[0]['prefs']);
			return $tmp[0];
		} # if
		
		return false;
	} # getUser

	/*
	 * Retrieve a list of userids and some basic properties
	 */
	function getUserList() {
		SpotTiming::start(__FUNCTION__);
		
		$tmpResult = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								u.firstname AS firstname,
								u.lastname AS lastname,
								u.mail AS mail,
								u.lastlogin AS lastlogin,
								s.otherprefs AS prefs
						 FROM users AS u
						 JOIN usersettings s ON (u.id = s.userid)
						 WHERE (NOT DELETED)");
		if (!empty($tmpResult)) {
			# Other preferences are stored serialized in the database
			$tmpResultCount = count($tmpResult);
			for($i = 0; $i < $tmpResultCount; $i++) {
				$tmpResult[$i]['prefs'] = unserialize($tmpResult[$i]['prefs']);
			} # for
		} # if

		SpotTiming::stop(__FUNCTION__, array());
		return $tmpResult;
	} # getUserList

	/*
	 * Retrieves a list of users with their last login time etc
	 */
	function getUserListForDisplay() {
		SpotTiming::start(__FUNCTION__);
		
		$tmpResult = $this->_conn->arrayQuery(
						"SELECT u.id AS userid,
								u.username AS username,
								MAX(u.firstname) AS firstname,
								MAX(u.lastname) AS lastname,
								MAX(u.mail) AS mail,
								MAX(u.lastlogin) AS lastlogin,
								COALESCE(MAX(ss.lasthit), MAX(u.lastvisit)) AS lastvisit,
								MAX(ipaddr) AS lastipaddr
							FROM users AS u
							LEFT JOIN (SELECT userid, lasthit, ipaddr, devicetype FROM sessions WHERE sessions.userid = userid ORDER BY lasthit) AS ss ON (u.id = ss.userid)
							WHERE (deleted = '%s')
							GROUP BY u.id, u.username", array($this->_conn->bool2dt(false)));

		SpotTiming::stop(__FUNCTION__, array());
		return $tmpResult;
	} # getUserListForDisplay

	/*
	 * Deletes (but actually disables) a user. We use
	 * soft deletes because if we would do a full delete
	 * any posted comments, spots and report would no longer
	 * be traceable back to this user.
	 */
	function deleteUser($userid) {
		$this->_conn->modify("UPDATE users 
								SET deleted = true
								WHERE id = '%s'", 
							Array( (int) $userid));
	} # deleteUser

	/*
	 * Update the information for a user
	 */
	function setUser($user) {
		# Update users' information / settings
		$this->_conn->modify("UPDATE users 
								SET firstname = '%s',
									lastname = '%s',
									mail = '%s',
									apikey = '%s',
									lastlogin = %d,
									lastvisit = %d,
									lastread = %d,
									lastapiusage = %d,
									deleted = '%s'
								WHERE id = %d", 
				Array($user['firstname'],
					  $user['lastname'],
					  $user['mail'],
					  $user['apikey'],
					  (int) $user['lastlogin'],
					  (int) $user['lastvisit'],
					  (int) $user['lastread'],
					  (int) $user['lastapiusage'],
					  $this->_conn->bool2dt($user['deleted']),
					  (int) $user['userid']));

		# update user preferences
		$this->_conn->modify("UPDATE usersettings
								SET otherprefs = '%s'
								WHERE userid = '%s'", 
				Array(serialize($user['prefs']),
					  (int) $user['userid']));
	} # setUser

	/*
	 * Set users' password. We are expected to be given an updated
	 * passhash member
	 */
	function setUserPassword($user) {
		$this->_conn->modify("UPDATE users 
								SET passhash = '%s'
								WHERE id = '%s'", 
				Array($user['passhash'],
					  (int) $user['userid']));
	} # setUserPassword

	/*
	 * Update the public and privatekey pair of a user. All other
	 * methodes cannot update this field because we expect and
	 * require a key pair and want the private key to be kept
	 * secret as much as possible.
	 */
	function setUserRsaKeys($userId, $publicKey, $privateKey) {
		# eerst updaten we de users informatie
		$this->_conn->modify("UPDATE usersettings
								SET publickey = '%s',
									privatekey = '%s'
								WHERE userid = '%s'",
				Array($publicKey, $privateKey, $userId));
	} # setUserRsaKeys 

	/*
	 * Retrieves the users' private key
	 */
	function getUserPrivateRsaKey($userId) {
		return $this->_conn->singleQuery("SELECT privatekey FROM usersettings WHERE userid = '%s'", 
					Array($userId));
	} # getUserPrivateRsaKey

	/* 
	 * Adds a user
	 */
	function addUser($user) {
		$stamp = $this->_conn->singleQuery("SELECT MAX(stamp) AS stamp FROM spots");
		if ($stamp == null) {
			$stamp = time();
		} # if

		$this->_conn->modify("INSERT INTO users(username, firstname, lastname, passhash, mail, apikey, lastread, deleted) 
										VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
								Array($user['username'], 
									  $user['firstname'],
									  $user['lastname'],
									  $user['passhash'],
									  $user['mail'],
									  $user['apikey'],
									  $stamp,
									  $this->_conn->bool2dt(false)));

		/*
		 * Now we just re-fetch the userrecords' id to know the users id in
		 * the database.
		 *
		 * Very ugly, but we don't have a reliable lastInsertId() method exposd
		 * in our database class
		 */
		$user['userid'] = $this->_conn->singleQuery("SELECT id FROM users WHERE username = '%s'", Array($user['username']));

		/* 
		 * and create an empty usersettings record 
		 */
		$this->_conn->modify("INSERT INTO usersettings(userid, privatekey, publickey, otherprefs) 
										VALUES('%s', '', '', 'a:0:{}')",
								Array((int)$user['userid']));
		return $user;
	} # addUser

	/*
	 * Are we able to authenticate with the given username and password hash?
	 *
	 * Returns 'false' if user is not found, else the userid
	 */
	function authUser($username, $passhash) {
		if ($username === false) {
			$tmp = $this->_conn->arrayQuery("SELECT id FROM users WHERE apikey = '%s' AND NOT DELETED", Array($passhash));
		} else {
			$tmp = $this->_conn->arrayQuery("SELECT id FROM users WHERE username = '%s' AND passhash = '%s' AND NOT DELETED", Array($username, $passhash));
		} # if

		if (empty($tmp)) {
			return false;
		} else {
			return $tmp[0]['id'];
		} # else
	} # authUser

	/*
	 * Updates a users' setting with an base64 encoded image
	 */
	function setUserAvatar($userId, $imageEncoded) {
		$this->_conn->modify("UPDATE usersettings SET avatar = '%s' WHERE userid = %d", Array( $imageEncoded, (int) $userId));
	} # setUserAvatar


	/* 
	 * Returns the permissions from a specific group
	 */
	function getGroupPerms($groupId) {
		return $this->_conn->arrayQuery("SELECT permissionid, objectid, deny FROM grouppermissions WHERE groupid = %d",
					Array($groupId));
	} # getgroupPerms
	
	/*
	 * Returns the permissions for a specific user, directly in the required format
	 * for the authentication checks in SpotSecurity.
	 */
	function getPermissions($userId) {
		$permList = array();
		$tmpList = $this->_conn->arrayQuery('SELECT permissionid, objectid, deny FROM grouppermissions 
												WHERE groupid IN 
													(SELECT groupid FROM usergroups WHERE userid = %d ORDER BY prio)',
											 Array($userId));

		foreach($tmpList as $perm) {
			# Voeg dit permissionid toe aan de lijst met permissies
			if (!isset($permList[$perm['permissionid']])) {
				$permList[$perm['permissionid']] = array();
			} # if
			
			$permList[$perm['permissionid']][$perm['objectid']] = !(boolean) $perm['deny'];
		} # foreach
		
		return $permList;
	} # getPermissions

	/*
	 * Returns all defined groups if $userId == null, else
	 * we rreturn the groups and add to it whether the user
	 * is a member or not
	 */
	function getGroupList($userId) {
		if ($userId == null) {
			return $this->_conn->arrayQuery("SELECT id,name,0 as \"ismember\" FROM securitygroups");
		} else {
			return $this->_conn->arrayQuery("SELECT sg.id,name,ug.userid IS NOT NULL as \"ismember\" FROM securitygroups sg LEFT JOIN usergroups ug ON (sg.id = ug.groupid) AND (ug.userid = %d)",
										Array($userId));
		} # if
	} # getGroupList
	
	/*
	 * Remove a permission from a security group
	 */
	function removePermFromSecGroup($groupId, $perm) {
		$this->_conn->modify("DELETE FROM grouppermissions WHERE (groupid = %d) AND (permissionid = %d) AND (objectid = '%s')", 
				Array($groupId, $perm['permissionid'], $perm['objectid']));
	} # removePermFromSecGroup

	/*
	 * Sets a permission to deny within a security group
	 */
	function setDenyForPermFromSecGroup($groupId, $perm) {
		$this->_conn->modify("UPDATE grouppermissions SET deny = '%s' WHERE (groupid = %d) AND (permissionid = %d) AND (objectid = '%s')", 
				Array($this->_conn->bool2dt($perm['deny']), $groupId, $perm['permissionid'], $perm['objectid']));
	} # setDenyForPermFromSecGroup
	
	/*
	 * Adds a permission to a security group
	 */
	function addPermToSecGroup($groupId, $perm) {
		$this->_conn->modify("INSERT INTO grouppermissions(groupid,permissionid,objectid) VALUES (%d, %d, '%s')",
				Array($groupId, $perm['permissionid'], $perm['objectid']));
	} # addPermToSecGroup

	/*
	 * Returns information about a specific security group
	 */
	function getSecurityGroup($groupId) {
		return $this->_conn->arrayQuery("SELECT id,name FROM securitygroups WHERE id = %d", Array($groupId));
	} # getSecurityGroup
		
	/*
	 * Updates a specific security group
	 */
	function setSecurityGroup($group) {
		$this->_conn->modify("UPDATE securitygroups SET name = '%s' WHERE id = %d", Array($group['name'], $group['id']));
	} # setSecurityGroup
	
	/*
	 * Adds a security group
	 */
	function addSecurityGroup($group) {
		$this->_conn->modify("INSERT INTO securitygroups(name) VALUES ('%s')", Array($group['name']));
	} # addSecurityGroup

	/*
	 * Removes a security group
	 */
	function removeSecurityGroup($group) {
		$this->_conn->modify("DELETE FROM securitygroups WHERE id = %d", Array($group['id']));
	} # removeSecurityGroup
	
	/*
	 * Updates the users' securitygroup membership list
	 */
	function setUserGroupList($userId, $groupList) {
		# We wissen eerst huidige group membership
		$this->_conn->modify("DELETE FROM usergroups WHERE userid = %d", array($userId));
		
		foreach($groupList as $groupInfo) {
			$this->_conn->modify("INSERT INTO usergroups(userid,groupid,prio) VALUES(%d, %d, %d)",
						Array($userId, $groupInfo['groupid'], $groupInfo['prio']));
		} # foreach
	} # setUserGroupList

} # Dao_Base_User
