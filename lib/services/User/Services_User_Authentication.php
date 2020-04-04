<?php

class Services_User_Authentication
{
    private $_sessionDao;
    private $_userDao;
    private $_daoFactory;
    private $_settings;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;

        $this->_userDao = $daoFactory->getUserDao();
        $this->_sessionDao = $daoFactory->getSessionDao();
    }

    // ctor

    /*
     * Create a new session for the userid
     */
    public function createNewSession($userid)
    {
        // If this is an actual user, we need to have the user record
        $tmpUser = $this->_userDao->getUser($userid);

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

            // Mark everything as read for anonymous users
            $this->_daoFactory->getUserFilterCountDao()->markFilterCountAsSeen($userid);
        } else {
            $tmpUser['lastvisit'] = $tmpUser['lastlogin'];
        } // if

        // Create a new session record
        $session = ['sessionid' => Services_User_Util::generateUniqueId(),
            'userid'            => $userid,
            'hitcount'          => 1,
            'lasthit'           => time(),
            'ipaddr'            => $this->determineUsersIpAddress(),
            'devicetype'        => $this->determineDeviceType(),
        ];

        /*
         * To prevent flooding the sessions table, we
         * don't actually create the db entry for anonymous
          * sessions. We can only do this for 'real' anonymous
          * users because when this is overriden, the new
          * anonymous user might have given additional features
          */
        if ($userid != SPOTWEB_ANONYMOUS_USERID) {
            $this->_sessionDao->addSession($session);
        } // if

        return ['user' => $tmpUser,
            'session'  => $session, ];
    }

    // createNewSession

    /*
     * Update the users cookie
     */
    private function updateCookie($userSession)
    {
        setcookie(
            'spotsession',
            $userSession['session']['sessionid'].'.'.$userSession['user']['userid'],
            (time() + (max(1, (int) $this->_settings->get('cookie_expires')) * 60 * 60 * 24)),
            '', // path: The default value is the current directory that the cookie is being set in.
                  $this->_settings->get('cookie_host'),
            false,	// Indicates if the cookie should only be transmitted over a secure HTTPS connection from the client.
                  true
        );	// Only available to the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
    }

    // updateCookie

    /*
     * Removes a session from the database.
     */
    public function removeSession($userSession)
    {
        // and remove the users' session if the user isn't the anonymous one
        if ($userSession['user']['userid'] != $this->_settings->get('nonauthenticated_userid')) {
            $this->_sessionDao->deleteSession($userSession['session']['sessionid']);

            return true;
        } else {
            return false;
        } // else
    }

    // removeSession

    /*
     * Removes all users' sessions from the database
     */
    public function removeAllUserSessions($userId)
    {
        $this->_sessionDao->deleteAllUserSessions($userId);

        return new Dto_FormResult('success');
    }

    // removeAllUserSessions

    /*
     * Checks whether the user already has a session in its cookie. If it
     * has, we use the existing session, else we create a new one for the
     * anonymous user.
     */
    public function useOrStartSession($forceAnonymous)
    {
        $userSession = false;

        if ((isset($_COOKIE['spotsession'])) && (!$forceAnonymous)) {
            $userSession = $this->validSession($_COOKIE['spotsession']);
        } // if

        if ($userSession === false) {
            /*
             * If we don't have a session by now, let's create a new
             * anonymous session.
             *
             * UserID is our default anonymous user, but this can be
             * overriden by the usersystem
             */
            $userSession = $this->createNewSession($this->_settings->get('nonauthenticated_userid'));
        } // if

        // Initialize the security system
        $spotSec = new SpotSecurity(
            $this->_userDao,
            $this->_daoFactory->getAuditDao(),
            $this->_settings,
            $userSession['user'],
            $userSession['session']['ipaddr']
        );
        $userSession['security'] = $spotSec;

        /*
         * Determine the users' template name
         */
        switch ($userSession['session']['devicetype']) {
            case 'mobile': $userSession['active_tpl'] = $userSession['user']['prefs']['mobile_template']; break;
            case 'tablet': $userSession['active_tpl'] = $userSession['user']['prefs']['tablet_template']; break;
            default: $userSession['active_tpl'] = $userSession['user']['prefs']['normal_template']; break;
        } // switch

        /*
         * And always update the cookie even if one already exists,
         * this prevents the cookie from expiring all of a sudden
         */
        $this->updateCookie($userSession);

        return $userSession;
    }

    // useOrStartSession

    /*
     * Tries to authenticate the user with the given credentials.
     * Returns an user record when authed, or false if the
     * authentication fails
     */
    public function authenticate($user, $password)
    {
        // Sals the password with the unique salt given in the database
        $password = Services_User_Util::passToHash($this->_settings->get('pass_salt'), $password);

        // authenticate the user
        $userId = $this->_userDao->authUser($user, $password);
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

            // now update the user record with the last logon time
            $userSession['user']['lastlogin'] = time();
            $this->_userDao->setUser($userSession['user']);

            // Initialize the security system
            $userSession['security'] = new SpotSecurity(
                $this->_userDao,
                $this->_daoFactory->getAuditDao(),
                $this->_settings,
                $userSession['user'],
                $userSession['session']['ipaddr']
            );

            return $userSession;
        } else {
            return false;
        } // else
    }

    // authenticate

    public function verifyApi($apikey)
    {
        // try to authenticate the user
        $userId = $this->_userDao->authUser(false, $apikey);

        if ($userId !== false && $userId > SPOTWEB_ADMIN_USERID && $apikey != '') {
            /*
             * In a normal logon, we need to have a session.
             * For API logons, we do not want a session because
             * that would bloat the session table.
             *
             * We therefore manually retrieve the user record
             */
            $userRecord['user'] = $this->_userDao->getUser($userId);

            // and use the userrecord to update the lastapiusage time
            $userRecord['user']['lastapiusage'] = time();
            $this->_userDao->setUser($userRecord['user']);

            // Initialize the security system
            $userRecord['security'] = new SpotSecurity(
                $this->_userDao,
                $this->_daoFactory->getAuditDao(),
                $this->_settings,
                $userRecord['user'],
                $this->determineUsersIpAddress()
            );

            // always use the default template
            $userRecord['active_tpl'] = $userRecord['user']['prefs']['normal_template'];

            // create an IP address in the record because we need it
            $userRecord['session'] = ['ipaddr' => $this->determineUsersIpAddress()];

            return $userRecord;
        } else {
            return false;
        } // else
    }

    // verifyApi

    /*
     * Checks whether an given session is valid. If the session
     * is valid, this function returns an userrecord
     */
    private function validSession($sessionCookie)
    {
        $sessionParts = explode('.', $sessionCookie);
        if (count($sessionParts) != 2) {
            return false;
        } // if

        // Check whether the session is to be found in the database
        $sessionValid = $this->_sessionDao->getSession($sessionParts[0], $sessionParts[1]);
        if ($sessionValid === false) {
            return false;
        } // if

        // The session is valid, let's update the hit counter and retrieve the user
        $this->_sessionDao->hitSession($sessionParts[0]);
        $userRecord = $this->_userDao->getUser($sessionValid['userid']);

        /*
         * If the user could not be found, the session wasn't valid after all
         */
        if ($userRecord === false) {
            return false;
        } // if

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
        if ($sessionValid['lasthit'] < (time() - (15 * 60))) {
            $userRecord['lastvisit'] = $sessionValid['lasthit'];

            /*
             * Update the last read time to the last spot we find in the
             * database. Theoreticall this still contains an race condtion
             * because the spots could be updated by now.
             *
             * We ignore this for now to not cause any performance issues
             */
            if ($userRecord['prefs']['auto_markasread']) {
                // Retrieve the last update stamp from the filters
                $filterHashes = $this->_daoFactory->getUserFilterCountDao()->getCachedFilterCount($userRecord['userid']);

                /*
                 * Set the lastread stamp to the last time the spotcount was updated
                 * in the filtercounts
                 */
                if (!empty($filterHashes)) {
                    $filterKeys = array_keys($filterHashes);
                    $userRecord['lastread'] = $filterHashes[$filterKeys[0]]['lastupdate'];
                } else {
                    $userRecord['lastread'] = $this->_daoFactory->getSpotDao()->getMaxMessageTime();
                } // else

                // Mark older spots as read for this user
                $this->_daoFactory->getUserFilterCountDao()->resetFilterCountForUser($userRecord['userid']);
            } // if

            $this->_userDao->setUser($userRecord);
        } // if

        return ['user' => $userRecord,
            'session'  => $sessionValid, ];
    }

    // validSession

    /*
     * Returns the users' remote IP address
     */
    private function determineUsersIpAddress()
    {
        /*
         * We now compare the X-Fowarded-For header and it's not clear if this
         * is the right thing to do.
         */
        $hdrsToCheck = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
            'REMOTE_ADDR', ];

        $remote_addr = 'N/A';
        foreach ($hdrsToCheck as $key) {
            if (isset($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        $remote_addr = $ip;
                    } // if
                } // foreach
            } // if
        } // foreach

        return $remote_addr;
    }

    // determineUsersIpAddress

    /*
     * Returns a string depending on the device type.
     */
    private function determineDeviceType()
    {
        $mobDetect = new Mobile_Detect();

        if ($mobDetect->isTablet()) {
            return 'tablet';
        } elseif ($mobDetect->isMobile()) {
            return 'mobile';
        } else {
            return 'full';
        } // else
    }

    // determineDeviceType
} // Services_User_Authentication
