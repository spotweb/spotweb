<?php

class Dao_Base_User implements Dao_User
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_UserFilterCount object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Returns the user id for a given username
     */
    public function findUserIdForName($username)
    {
        return $this->_conn->singleQuery(
            'SELECT id FROM users WHERE username = :username',
            [
                ':username' => [$username, PDO::PARAM_STR],
            ]
        );
    }

    // findUserIdForName

    /*
     * Determines whether an email address exists
     */
    public function userEmailExists($mail)
    {
        $tmpResult = $this->_conn->singleQuery(
            'SELECT id FROM users WHERE mail = :mail',
            [
                ':mail' => [$mail, PDO::PARAM_STR],
            ]
        );

        if (!empty($tmpResult)) {
            return $tmpResult;
        } // if

        return false;
    }

    // userEmailExists

    /**
     * Retrieves a user from the database.
     *
     * @returns boolean|array
     */
    public function getUser($userid)
    {
        $tmp = $this->_conn->arrayQuery(
            'SELECT u.id AS userid,
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
						 WHERE u.id = :userid AND NOT DELETED',
            [
                ':userid' => [$userid, PDO::PARAM_INT],
            ]
        );

        if (!empty($tmp)) {
            /*
             * Other preferences are stored 'serialized' in the database to make
             * extensibility easy
             */
            $tmp[0]['prefs'] = unserialize($tmp[0]['prefs']);

            return $tmp[0];
        } // if

        return false;
    }

    // getUser

    /*
     * Retrieve a list of userids and some basic properties
     */
    public function getUserList()
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $tmpResult = $this->_conn->arrayQuery(
            'SELECT u.id AS userid,
								u.username AS username,
								u.firstname AS firstname,
								u.lastname AS lastname,
								u.mail AS mail,
								u.lastlogin AS lastlogin,
								s.otherprefs AS prefs
						 FROM users AS u
						 JOIN usersettings s ON (u.id = s.userid)
						 WHERE (NOT DELETED)'
        );
        if (!empty($tmpResult)) {
            // Other preferences are stored serialized in the database
            $tmpResultCount = count($tmpResult);
            for ($i = 0; $i < $tmpResultCount; $i++) {
                $tmpResult[$i]['prefs'] = unserialize($tmpResult[$i]['prefs']);
            } // for
        } // if

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, []);

        return $tmpResult;
    }

    // getUserList

    /*
     * Retrieves a list of users with their last login time etc
     */
    public function getUserListForDisplay()
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $tmpResult = $this->_conn->arrayQuery(
            'SELECT u.id AS userid,
								u.username AS username,
								MAX(u.firstname) AS firstname,
								MAX(u.lastname) AS lastname,
								MAX(u.mail) AS mail,
								MAX(u.lastlogin) AS lastlogin,
								COALESCE(MAX(ss.lasthit), MAX(u.lastvisit)) AS lastvisit,
								MAX(ipaddr) AS lastipaddr
							FROM users AS u
							LEFT JOIN (SELECT userid, lasthit, ipaddr, devicetype FROM sessions WHERE sessions.userid = userid ORDER BY lasthit) AS ss ON (u.id = ss.userid)
							WHERE (deleted = :isdeleted)
							GROUP BY u.id, u.username',
            [
                ':isdeleted' => [false, PDO::PARAM_BOOL],
            ]
        );

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, []);

        return $tmpResult;
    }

    // getUserListForDisplay

    /*
     * Deletes (but actually disables) a user. We use
     * soft deletes because if we would do a full delete
     * any posted comments, spots and report would no longer
     * be traceable back to this user.
     */
    public function deleteUser($userid)
    {
        $this->_conn->modify(
            'UPDATE users 
								SET deleted = true
								WHERE id = :userid',
            [
                ':userid' => [$userid, PDO::PARAM_INT],
            ]
        );
    }

    // deleteUser

    /*
     * Update the information for a user
     */
    public function setUser($user)
    {
        // Update users' information / settings
        $this->_conn->modify(
            'UPDATE users 
								SET firstname = :firstname,
									lastname = :lastname,
									mail = :mail,
									apikey = :apikey,
									lastlogin = :lastlogin,
									lastvisit = :lastvisit,
									lastread = :lastread,
									lastapiusage = :lastapiusage,
									deleted = :isdeleted
								WHERE id = :userid',
            [
                ':firstname'    => [$user['firstname'], PDO::PARAM_STR],
                ':lastname'     => [$user['lastname'], PDO::PARAM_STR],
                ':mail'         => [$user['mail'], PDO::PARAM_STR],
                ':apikey'       => [$user['apikey'], PDO::PARAM_STR],
                ':lastlogin'    => [$user['lastlogin'], PDO::PARAM_INT],
                ':lastvisit'    => [$user['lastvisit'], PDO::PARAM_INT],
                ':lastread'     => [$user['lastread'], PDO::PARAM_INT],
                ':lastapiusage' => [$user['lastapiusage'], PDO::PARAM_INT],
                ':isdeleted'    => [$user['deleted'], PDO::PARAM_BOOL],
                ':userid'       => [$user['userid'], PDO::PARAM_INT],
            ]
        );

        // update user preferences
        $this->_conn->modify(
            'UPDATE usersettings
								SET otherprefs = :otherprefs
								WHERE userid = :userid',
            [
                ':otherprefs' => [serialize($user['prefs']), PDO::PARAM_STR],
                ':userid'     => [$user['userid'], PDO::PARAM_INT],
            ]
        );
    }

    // setUser

    /*
     * Set users' password. We are expected to be given an updated
     * passhash member
     */
    public function setUserPassword($userarr)
    {
        $this->_conn->modify(
            'UPDATE users 
								SET passhash = :passhash
								WHERE id = :userid',
            [
                ':passhash' => [$userarr['passhash'], PDO::PARAM_STR],
                ':userid'   => [$userarr['userid'], PDO::PARAM_INT],
            ]
        );
    }

    // setUserPassword

    /*
     * Update the public and privatekey pair of a user. All other
     * methodes cannot update this field because we expect and
     * require a key pair and want the private key to be kept
     * secret as much as possible.
     */
    public function setUserRsaKeys($userId, $publicKey, $privateKey)
    {
        // eerst updaten we de users informatie
        $this->_conn->modify(
            'UPDATE usersettings
								SET publickey = :publickey,
									privatekey = :privatekey
								WHERE userid = :userid',
            [
                ':publickey'  => [$publicKey, PDO::PARAM_STR],
                ':privatekey' => [$privateKey, PDO::PARAM_STR],
                ':userid'     => [$userId, PDO::PARAM_INT],
            ]
        );
    }

    // setUserRsaKeys

    /*
     * Retrieves the users' private key
     */
    public function getUserPrivateRsaKey($userId)
    {
        return $this->_conn->singleQuery(
            'SELECT privatekey FROM usersettings WHERE userid = :userid',
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );
    }

    // getUserPrivateRsaKey

    /*
     * Adds a user
     */
    public function addUser($user)
    {
        $stamp = $this->_conn->singleQuery('SELECT MAX(stamp) AS stamp FROM spots');
        if ($stamp == null) {
            $stamp = time();
        } // if

        $this->_conn->modify(
            'INSERT INTO users(username, firstname, lastname, passhash, mail, apikey, lastread, deleted) 
										VALUES(:username, :firstname, :lastname, :passhash, :mail, :apikey, :lastread, :isdeleted)',
            [
                ':username'  => [$user['username'], PDO::PARAM_STR],
                ':firstname' => [$user['firstname'], PDO::PARAM_STR],
                ':lastname'  => [$user['lastname'], PDO::PARAM_STR],
                ':passhash'  => [$user['passhash'], PDO::PARAM_STR],
                ':mail'      => [$user['mail'], PDO::PARAM_STR],
                ':apikey'    => [$user['apikey'], PDO::PARAM_STR],
                ':lastread'  => [$stamp, PDO::PARAM_INT],
                ':isdeleted' => [false, PDO::PARAM_BOOL],
            ]
        );

        /*
         * Now we just re-fetch the userrecords' id to know the users id in
         * the database.
         *
         * Very ugly, but we don't have a reliable lastInsertId() method exposd
         * in our database class
         */
        $user['userid'] = $this->_conn->singleQuery(
            'SELECT id FROM users WHERE username = :username',
            [
                ':username' => [$user['username'], PDO::PARAM_STR],
            ]
        );

        /*
         * and create an empty usersettings record
         */
        $this->_conn->modify(
            "INSERT INTO usersettings(userid, privatekey, publickey, otherprefs) 
										VALUES(:userid, '', '', 'a:0:{}')",
            [
                ':userid' => [$user['userid'], PDO::PARAM_INT],
            ]
        );

        return $user;
    }

    // addUser

    /*
     * Are we able to authenticate with the given username and password hash?
     *
     * Returns 'false' if user is not found, else the userid
     */
    public function authUser($username, $passhash)
    {
        if ($username === false) {
            $tmp = $this->_conn->arrayQuery(
                'SELECT id FROM users WHERE apikey = :apikey AND NOT DELETED',
                [
                    ':apikey' => [$passhash, PDO::PARAM_STR],
                ]
            );
        } else {
            $tmp = $this->_conn->arrayQuery(
                'SELECT id FROM users WHERE username = :username AND passhash = :passhash AND NOT DELETED',
                [
                    ':username' => [$username, PDO::PARAM_STR],
                    ':passhash' => [$passhash, PDO::PARAM_STR],
                ]
            );
        } // if

        if (empty($tmp)) {
            return false;
        } else {
            return $tmp[0]['id'];
        } // else
    }

    // authUser

    /*
     * Updates a users' setting with an base64 encoded image
     */
    public function setUserAvatar($userId, $imageEncoded)
    {
        $this->_conn->modify(
            'UPDATE usersettings SET avatar = :avatar WHERE userid = :userid',
            [
                ':avatar' => [$imageEncoded, PDO::PARAM_STR],
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );
    }

    // setUserAvatar

    /*
     * Returns the permissions from a specific group
     */
    public function getGroupPerms($groupId)
    {
        return $this->_conn->arrayQuery(
            'SELECT permissionid, objectid, deny FROM grouppermissions WHERE groupid = :groupid',
            [
                ':groupid' => [$groupId, PDO::PARAM_INT],
            ]
        );
    }

    // getgroupPerms

    /*
     * Returns the permissions for a specific user, directly in the required format
     * for the authentication checks in SpotSecurity.
     */
    public function getPermissions($userId)
    {
        $permList = [];
        $tmpList = $this->_conn->arrayQuery(
            'SELECT permissionid, objectid, deny FROM grouppermissions 
												WHERE groupid IN 
													(SELECT groupid FROM usergroups WHERE userid = :userid ORDER BY prio)',
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );

        foreach ($tmpList as $perm) {
            // Voeg dit permissionid toe aan de lijst met permissies
            if (!isset($permList[$perm['permissionid']])) {
                $permList[$perm['permissionid']] = [];
            } // if

            $permList[$perm['permissionid']][$perm['objectid']] = !(bool) $perm['deny'];
        } // foreach

        return $permList;
    }

    // getPermissions

    /*
     * Returns all defined groups if $userId == null, else
     * we rreturn the groups and add to it whether the user
     * is a member or not
     */
    public function getGroupList($userId)
    {
        if ($userId == null) {
            return $this->_conn->arrayQuery('SELECT id,name,0 as "ismember" FROM securitygroups');
        } else {
            return $this->_conn->arrayQuery(
                'SELECT sg.id,name,
			                                        ug.userid IS NOT NULL as "ismember"
			                                 FROM securitygroups sg
			                                    LEFT JOIN usergroups ug ON (sg.id = ug.groupid) AND (ug.userid = :userid)',
                [
                    ':userid' => [$userId, PDO::PARAM_INT],
                ]
            );
        } // if
    }

    // getGroupList

    /*
     * Remove a permission from a security group
     */
    public function removePermFromSecGroup($groupId, $perm)
    {
        $this->_conn->modify(
            'DELETE FROM grouppermissions WHERE (groupid = :groupid) AND (permissionid = :permissionid) AND (objectid = :objectid)',
            [
                ':groupid'      => [$groupId, PDO::PARAM_INT],
                ':permissionid' => [$perm['permissionid'], PDO::PARAM_INT],
                ':objectid'     => [$perm['objectid'], PDO::PARAM_STR],
            ]
        );
    }

    // removePermFromSecGroup

    /*
     * Sets a permission to deny within a security group
     */
    public function setDenyForPermFromSecGroup($groupId, $perm)
    {
        $this->_conn->modify(
            'UPDATE grouppermissions
		                        SET deny = :isdenied
		                        WHERE (groupid = :groupid) AND (permissionid = :permissionid) AND (objectid = :objectid)',
            [
                ':isdenied'     => [$perm['deny'], PDO::PARAM_BOOL],
                ':groupid'      => [$groupId, PDO::PARAM_INT],
                ':permissionid' => [$perm['permissionid'], PDO::PARAM_INT],
                ':objectid'     => [$perm['objectid'], PDO::PARAM_STR],
            ]
        );
    }

    // setDenyForPermFromSecGroup

    /*
     * Adds a permission to a security group
     */
    public function addPermToSecGroup($groupId, $perm)
    {
        $this->_conn->modify(
            'INSERT INTO grouppermissions(groupid, permissionid, objectid)
		                        VALUES (:groupid, :permissionid, :objectid)',
            [
                ':groupid'      => [$groupId, PDO::PARAM_INT],
                ':permissionid' => [$perm['permissionid'], PDO::PARAM_INT],
                ':objectid'     => [$perm['objectid'], PDO::PARAM_STR],
            ]
        );
    }

    // addPermToSecGroup

    /*
     * Returns information about a specific security group
     */
    public function getSecurityGroup($groupId)
    {
        return $this->_conn->arrayQuery(
            'SELECT id, name FROM securitygroups WHERE id = :id',
            [
                ':id' => [$groupId, PDO::PARAM_INT],
            ]
        );
    }

    // getSecurityGroup

    /*
     * Updates a specific security group
     */
    public function setSecurityGroup($group)
    {
        $this->_conn->modify(
            'UPDATE securitygroups SET name = :groupname WHERE id = :groupid',
            [
                ':groupname' => [$group['name'], PDO::PARAM_STR],
                ':groupid'   => [$group['id'], PDO::PARAM_INT],
            ]
        );
    }

    // setSecurityGroup

    /*
     * Adds a security group
     */
    public function addSecurityGroup($groupName)
    {
        $this->_conn->modify(
            'INSERT INTO securitygroups(name) VALUES (:groupname)',
            [
                ':groupname' => [$groupName, PDO::PARAM_STR],
            ]
        );
    }

    // addSecurityGroup

    /*
     * Removes a security group
     */
    public function removeSecurityGroup($groupId)
    {
        $this->_conn->modify(
            'DELETE FROM securitygroups WHERE id = :groupid',
            [
                ':groupid' => [$groupId, PDO::PARAM_INT],
            ]
        );
    }

    // removeSecurityGroup

    /*
     * Updates the users' securitygroup membership list
     */
    public function setUserGroupList($userId, $groupList)
    {
        $this->_conn->modify(
            'DELETE FROM usergroups WHERE userid = :userid',
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );

        foreach ($groupList as $groupInfo) {
            $this->_conn->modify(
                'INSERT INTO usergroups(userid, groupid, prio) VALUES(:userid, :groupid, :prio)',
                [
                    ':userid'  => [$userId, PDO::PARAM_INT],
                    ':groupid' => [$groupInfo['groupid'], PDO::PARAM_INT],
                    ':prio'    => [$groupInfo['prio'], PDO::PARAM_INT],
                ]
            );
        } // foreach
    }

    // setUserGroupList
} // Dao_Base_User
