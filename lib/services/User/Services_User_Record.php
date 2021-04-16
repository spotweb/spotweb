<?php

class Services_User_Record
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
     * Create a new user record
     */
    public function createUserRecord(array $spotUser)
    {
        $result = new Dto_FormResult();
        $spotUser['userid'] = false;

        /*
         * Create a random password for this user
         */
        $spotUser['newpassword1'] = substr(Services_User_Util::generateUniqueId(), 1, 9);
        $spotUser['newpassword2'] = $spotUser['newpassword1'];

        /*
         * Validate several properties of the user, we share
         * this code with the user editor
         */
        $result->mergeResult($this->validateUserRecord($spotUser, false));

        /*
         * Make sure no other user exists with the same username
         */
        $userIdForName = $this->_userDao->findUserIdForName($spotUser['username']);
        if (!empty($userIdForName)) {
            $result->addError(sprintf(_("'%s' already exists"), $spotUser['username']));
            $spotUser['userid'] = $userIdForName;
            $result->addData('userid', $spotUser['userid']);
        } // if

        if ($result->isSuccess()) {
            // Create a private and public key pair for this user
            $spotSigning = Services_Signing_Base::factory();
            $userKey = $spotSigning->createPrivateKey($this->_settings->get('openssl_cnf_path'));
            $spotUser['publickey'] = $userKey['public'];
            $spotUser['privatekey'] = $userKey['private'];

            // Actually add the user
            $spotUser['userid'] = $this->addUser($spotUser);

            /*
             * We assume the user was successfully added, all validation is done at
             * a higher level, and addUser() will throw an exception if something is
             * seriously wrong
             */
            $result->addData('userid', $spotUser['userid']);
            $result->addData('username', $spotUser['username']);
            $result->addData('password', $spotUser['newpassword1']);
            $result->addData('userrecord', $spotUser);
            $result->addInfo(sprintf(_('User <strong>&quot;%s&quot;</strong> successfully added'), $spotUser['username']));
            $result->addInfo(sprintf(_('Password: <strong>&quot;%s&quot;</strong>'), $spotUser['newpassword1']));

            $result->setResult('success');
        } // if

        return $result;
    }

    // createUserRecord

    /*
     * Reset the seenstamp timestamp
     */
    public function resetReadStamp($user)
    {
        $user['lastvisit'] = time();
        $user['lastread'] = $this->_daoFactory->getSpotDao()->getMaxMessageTime();
        $this->_userDao->setUser($user);

        // Mark everything as read for this user
        $this->_daoFactory->getUserFilterCountDao()->markFilterCountAsSeen($user['userid']);

        return $user;
    }

    // resetReadStamp

    /*
     * Is this user allowed to post content like spam reports etc?
     */
    public function allowedToPost($user)
    {
        /*
         * When an invalid (reserved) username is used, prevent
         * posting
         */
        if (!$this->validUsername($user['username'])) {
            return false;
        } // if

        // Als de user niet ingelogged is, dan heeft dit geen zin
        if ($user['userid'] <= SPOTWEB_ADMIN_USERID) {
            return false;
        } // if

        return true;
    }

    // allowedToPost

    /*
     * Validates a username
     */
    public function validUsername($user)
    {
        $invalidNames = ['god', 'mod', 'modje', 'spot', 'spotje', 'spotmod',
            'admin', 'drazix', 'moderator', 'superuser', 'supervisor',
            'spotnet', 'spotnetmod', 'administrator',  'spotweb',
            'root', 'anonymous', 'spotlite', ];

        $validUsername = !in_array(strtolower($user), $invalidNames);
        if ($validUsername) {
            $validUsername = strlen($user) >= 3;
        } // if

        return $validUsername;
    }

    // validUsername

    /*
     * Adds a user to the database
     */
    private function addUser($user)
    {
        if (!$this->validUsername($user['username'])) {
            throw new Exception('Invalid username');
        } // if

        // Convert the password to an passhash
        $user['passhash'] = Services_User_Util::passToHash($this->_settings->get('pass_salt'), $user['newpassword1']);

        // Create an API key
        $user['apikey'] = md5(Services_User_Util::generateUniqueId());

        // and actually add the user to the database
        $tmpUser = $this->_userDao->addUser($user);
        $this->_userDao->setUserRsaKeys($tmpUser['userid'], $user['publickey'], $user['privatekey']);

        /*
         * Now copy the preferences from the anonymous user to this
         * new user
         */
        $anonUser = $this->_userDao->getUser($this->_settings->get('nonauthenticated_userid'));
        $tmpUser = array_merge($anonUser, $tmpUser);
        $tmpUser['prefs']['newspotdefault_tag'] = $user['username'];
        $this->_userDao->setUser($tmpUser);

        // and add the user to the default set of groups as configured
        $this->_userDao->setUserGroupList($tmpUser['userid'], $this->_settings->get('newuser_grouplist'));

        // now copy the users' filters to the new user
        $this->_daoFactory->getUserFilterDao()->copyFilterList($this->_settings->get('nonauthenticated_userid'), $tmpUser['userid']);

        return $tmpUser['userid'];
    }

    // addUser()

    /*
     * Update a user's group membership
     */
    public function setUserGroupList($user, $groupList)
    {
        $this->_userDao->setUserGroupList($user['userid'], $groupList);
    }

    // setUserGroupList

    /*
     * Update a userid's password
     */
    public function setUserPassword($userarr)
    {
        // Convert the password to an passhash
        $userarr['passhash'] = Services_User_Util::passToHash($this->_settings->get('pass_salt'), $userarr['newpassword1']);

        $this->_userDao->setUserPassword($userarr);
    }

    // setUserPassword

    /*
     * Update a user's API key
     */
    public function resetUserApi($user)
    {
        $user['apikey'] = md5(Services_User_Util::generateUniqueId());

        $this->_userDao->setUser($user);

        $result = new Dto_FormResult('success');
        $result->addData('apikey', $user['apikey']);

        return $result;
    }

    // setUserApi

    /*
     * Merge an array recursively, overwriting
     * existing values
     *
     * Code copied from
     *    http://nl3.php.net/manual/en/function.array-merge-recursive.php#106985
     */
    public function array_merge_recursive_overwrite()
    {
        $arrays = func_get_args();
        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            reset($base); //important
            //while (list($key, $value) = @each($array)) { // Deprecated php 7.2
            foreach ($array as $key => $value) {
                if (is_array($value) && @is_array($base[$key])) {
                    $base[$key] = $this->array_merge_recursive_overwrite($base[$key], $value);
                } else {
                    $base[$key] = $value;
                } // else
            } // while / for each
        } // foreach

        return $base;
    }

    // array_merge_recursive_overwrite

    /*
     * Cleanup of user preferences
     */
    public function cleanseUserPreferences($prefs, $anonSkel, $tmplSkel)
    {
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
         */
        $anonSkel['count_newspots'] = (isset($prefs['count_newspots'])) ? true : false;
        $anonSkel['mouseover_subcats'] = (isset($prefs['mouseover_subcats'])) ? true : false;
        $anonSkel['keep_seenlist'] = (isset($prefs['keep_seenlist'])) ? true : false;
        $anonSkel['auto_markasread'] = (isset($prefs['auto_markasread'])) ? true : false;
        $anonSkel['keep_downloadlist'] = (isset($prefs['keep_downloadlist'])) ? true : false;
        $anonSkel['keep_watchlist'] = (isset($prefs['keep_watchlist'])) ? true : false;
        $anonSkel['show_filesize'] = (isset($prefs['show_filesize'])) ? true : false;
        $anonSkel['show_reportcount'] = (isset($prefs['show_reportcount'])) ? true : false;
        $anonSkel['show_nzbbutton'] = (isset($prefs['show_nzbbutton'])) ? true : false;
        $anonSkel['show_multinzb'] = (isset($prefs['show_multinzb'])) ? true : false;
        $anonSkel['show_avatars'] = (isset($prefs['show_avatars'])) ? true : false;

        $notifProviders = Notifications_Factory::getActiveServices();
        foreach ($notifProviders as $notifProvider) {
            $anonSkel['notifications'][$notifProvider]['enabled'] = (isset($prefs['notifications'][$notifProvider]['enabled'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['watchlist_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['watchlist_handled'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['nzb_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['nzb_handled'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['retriever_finished'] = (isset($prefs['notifications'][$notifProvider]['events']['retriever_finished'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['report_posted'] = (isset($prefs['notifications'][$notifProvider]['events']['report_posted'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['spot_posted'] = (isset($prefs['notifications'][$notifProvider]['events']['spot_posted'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['user_added'] = (isset($prefs['notifications'][$notifProvider]['events']['user_added'])) ? true : false;
            $anonSkel['notifications'][$notifProvider]['events']['newspots_for_filter'] = (isset($prefs['notifications'][$notifProvider]['events']['newspots_for_filter'])) ? true : false;
        } // foreach

        // When nzbhandling settings are not entered at all, we default to disable
        if (!isset($prefs['nzbhandling'])) {
            $anonSkel['nzbhandling'] = ['action' => 'disable',
                'prepare_action'                 => 'merge', ];
        } // if

        /*
         * We add the users' template specific settings to the basic
         * skeleton of settings so those settings aren't removed.
         */
        unset($anonSkel['template_specific']);
        $tmplSkel = ['template_specific' => $tmplSkel];
        $anonSkel = $this->array_merge_recursive_overwrite($tmplSkel, $anonSkel);

        /*
         * Unset any keys in the preferences which aren't available
         * in the preferences template (anonyuser)
         */
        foreach (array_diff_key($prefs, $anonSkel) as $keys => $values) {
            unset($prefs[$keys]);
        } // foreach

        /*
         * Of course array_merge_recursive doesn't do what one would
         * expect it to do and merge embedded arrays by combining them
         * instead of overwriting key values...
         */
        $prefs = $this->array_merge_recursive_overwrite($anonSkel, $prefs);

        return $prefs;
    }

    // cleanseUserPreferences

    /*
     * Validate user preferences
     */
    public function validateUserPreferences($prefs, $currentPrefs)
    {
        $result = new Dto_FormResult();

        // Define several arrays with valid settings
        $validDateFormats = ['human', '%a, %d-%b-%Y (%H:%M)', '%d-%m-%Y (%H:%M)'];
        $validTemplates = array_keys($this->_settings->get('valid_templates'));
        $validDefaultSorts = ['', 'stamp'];
        $validLanguages = array_keys($this->_settings->get('system_languages'));

        // Check per page setting
        $prefs['perpage'] = (int) $prefs['perpage'];
        if (($prefs['perpage'] < 2) || ($prefs['perpage'] > 250)) {
            $result->addError(_('Invalid preference value (perpage)'));
        } // if

        // Controleer basis settings
        if (in_array($prefs['date_formatting'], $validDateFormats) === false) {
            $result->addError(_('Invalid user preference value (date_formatting)'));
        } // if

        if (in_array($prefs['normal_template'], $validTemplates) === false) {
            $result->addError(_('Invalid user preference value (template)'));
        } // if

        if (in_array($prefs['mobile_template'], $validTemplates) === false) {
            $result->addError(_('Invalid user preference value (template)'));
        } // if

        if (in_array($prefs['tablet_template'], $validTemplates) === false) {
            $result->addError(_('Invalid user preference value (template)'));
        } // if

        if (in_array($prefs['user_language'], $validLanguages) === false) {
            $result->addError(_('Invalid user preference value (language)'));
        } // if

        if (in_array($prefs['defaultsortfield'], $validDefaultSorts) === false) {
            $result->addError(_('Invalid user preference value (defaultsortfield)'));
        } // if

        // when an sabnzbd host is entered, it has to be a valid URL
        if (($prefs['nzbhandling']['action'] == 'client-sabnzbd') || ($prefs['nzbhandling']['action'] == 'push-sabnzbd')) {
            $tmpHost = parse_url($prefs['nzbhandling']['sabnzbd']['url']);

            if (($tmpHost === false) | (!isset($tmpHost['scheme'])) || (($tmpHost['scheme'] != 'http') && ($tmpHost['scheme'] != 'https'))) {
                $result->addError(_('sabnzbd host is not a valid URL'));
            } // if

            // SABnzbd URL should always end with a s slash
            if (substr($prefs['nzbhandling']['sabnzbd']['url'], -1) !== '/') {
                $prefs['nzbhandling']['sabnzbd']['url'] .= '/';
            } // if
        } // if

        // when an nzbget host is entered, it has to be a valid URL
        if ($prefs['nzbhandling']['action'] == 'nzbget') {
            if (empty($prefs['nzbhandling']['nzbget']['host'])) {
                $result->addError(_('Host entered for nzbget is not valid'));
            } // if

            if (empty($prefs['nzbhandling']['nzbget']['port'])) {
                $result->addError(_('Port entered for nzbget is not valid'));
            } // if
        } // if

        // when an nzbvortex host is entered, it has to be a valid URL
        if ($prefs['nzbhandling']['action'] == 'nzbvortex') {
            if (empty($prefs['nzbhandling']['nzbvortex']['host'])) {
                $result->addError(_('Host entered for NZBVortex is not valid'));
            } // if

            if (empty($prefs['nzbhandling']['nzbvortex']['port'])) {
                $result->addError(_('Port entered for NZBVortex is not valid'));
            } // if

            if (empty($prefs['nzbhandling']['nzbvortex']['apikey'])) {
                $result->addError(_('API-Key entered for NZBVortex is not valid'));
            } // if
        } // if

        // Twitter tokens are never posted by the form, but they shouldn't be tossed out
        $prefs['notifications']['twitter']['screen_name'] = $currentPrefs['notifications']['twitter']['screen_name'];
        $prefs['notifications']['twitter']['access_token'] = $currentPrefs['notifications']['twitter']['access_token'];
        $prefs['notifications']['twitter']['access_token_secret'] = $currentPrefs['notifications']['twitter']['access_token_secret'];
        $prefs['notifications']['twitter']['request_token'] = $currentPrefs['notifications']['twitter']['request_token'];
        $prefs['notifications']['twitter']['request_token_secret'] = $currentPrefs['notifications']['twitter']['request_token_secret'];

        // We don't want to save megabyts of CSS, so put a limit to the size
        if (strlen($prefs['customcss'] > 1024 * 10)) {
            $result->addError(_('Custom CSS is too large'));
        } // if

        // We don't want to save megabytes of default newspot body, so limit it
        if (strlen($prefs['newspotdefault_tag'] > 90)) {
            $result->addError(_('Default value for a spots\' tag is too long'));
        } // if

        if (strlen($prefs['newspotdefault_body'] > 9000)) {
            $result->addError(_('Default value for a spots\' body is too long'));
        } // if

        // When a 'runcommand' or 'save' action is chosen, 'local_dir' is a mandatry setting
        if (($prefs['nzbhandling']['action'] == 'save') || ($prefs['nzbhandling']['action'] == 'runcommand')) {
            if (empty($prefs['nzbhandling']['local_dir'])) {
                $result->addError(_('When NZB handling is either "save" or "runcommand" the directory must be entered'));
            } // if
        } // if

        // When a 'runcommand' action is chosen, 'command' is a mandatry setting
        if ($prefs['nzbhandling']['action'] == 'runcommand') {
            if (empty($prefs['nzbhandling']['command'])) {
                $result->addError(_('When NZB handling is "runcommand" a command must be entered'));
            } // if
        } // if

        // For the 'growl' notification provider, a host is mandatory
        if ($prefs['notifications']['growl']['enabled']) {
            if (empty($prefs['notifications']['growl']['host'])) {
                $result->addError(_('Growl notifications require a growl host to be entered'));
            } // if
        } // if

        // 'Notify My Android' requires an API key
        if ($prefs['notifications']['nma']['enabled']) {
            if (empty($prefs['notifications']['nma']['api'])) {
                $result->addError(_('"Notify My Android" notifications require an API key'));
            } // if
        } // if

        // 'Prowl' requires an API key
        if ($prefs['notifications']['prowl']['enabled']) {
            if (empty($prefs['notifications']['prowl']['apikey'])) {
                $result->addError(_('"Prowl" notifications require an API key to be entered'));
            } // if
        } // if

        // To use Twitter, an twitter account should be defined
        if ($prefs['notifications']['twitter']['enabled']) {
            if (empty($prefs['notifications']['twitter']['access_token']) || empty($prefs['notifications']['twitter']['access_token_secret'])) {
                $result->addError(_('To use twitter you need to enter and validate a twitter account'));
            } // if
        } // if

        /* Make sure a valid value for minimum_reportcount is entered */
        if ((!is_numeric($prefs['minimum_reportcount'])) || ($prefs['minimum_reportcount']) > 10) {
            $result->addError(_('Invalid value for minimum_reportcount'));
        } // if

        /*
         * We want to return the fixed up preferences to the caller
         */
        $result->addData('prefs', $prefs);

        return $result;
    }

    // validateUserPreferences

    /*
     * Validate the user record. Might be used for both adding and changing
     */
    public function validateUserRecord($user, $isEdit)
    {
        $result = new Dto_FormResult();

        // Make sure the username is valid
        if (!$isEdit) {
            if (!$this->validUsername($user['username'])) {
                $result->addError(_('Invalid username chosen'));
            } // if
        } // if

        // Check a firstname is entered
        if (strlen($user['firstname']) < 2) {
            $result->addError(_('Not a valid firstname'));
        } // if

        // Check a lastname is entered
        if (strlen($user['lastname']) < 2) {
            $result->addError(_('Not a valid lastname'));
        } // if

        // Make sure a valid password is entered for existing users
        if ((strlen($user['newpassword1'] > 0)) && ($isEdit)) {
            if (strlen($user['newpassword1']) < 5) {
                $result->addError(_('Entered password is too short'));
            } // if
        } // if

        // Make sure a valid password is entered for new users
        if ((strlen($user['newpassword1']) < 5) && (!$isEdit)) {
            $result->addError(_('Entered password is too short'));
        } // if

        // and make sure the passwords match
        if ($user['newpassword1'] != $user['newpassword2']) {
            $result->addError(_('Passwords do not match'));
        } // if

        // check the mailaddress
        if (!filter_var($user['mail'], FILTER_VALIDATE_EMAIL)) {
            $result->addError(_('Not a valid email address'));
        } // if

        // and make sure the mailaddress is unique among all users
        $result->mergeResult($this->validateUserEmailExists($user));

        return $result;
    }

    // validateUserRecord

    public function validateUserEmailExists($user)
    {
        $result = new Dto_FormResult();

        // and make sure the mailaddress is unique among all users
        $emailExistResult = $this->_userDao->userEmailExists($user['mail']);
        if (($emailExistResult !== $user['userid']) && ($emailExistResult !== false)) {
            $result->addError(_('Mailaddress is already in use'));
        } // if

        return $result;
    }

    // validateUserEmailExists

    private function cleanseEditForm($editForm)
    {
        /* Make sure the preferences aren't set using this page as it might override security */
        $validFields = ['firstname', 'lastname', 'mail', 'newpassword1', 'newpassword2', 'grouplist', 'prefs'];
        foreach ($editForm as $key => $value) {
            if (in_array($key, $validFields) === false) {
                unset($editForm[$key]);
            } // if
        } // foreach

        return $editForm;
    }

    // cleanseEditForm

    public function updateUserRecord($user, array $groupList, $allowEditGroupMembership)
    {
        $result = new Dto_FormResult('success');
        $spotUser = $this->getUser($user['userid']);

        // Remove any non-valid fields from the array
        $user = $this->cleanseEditForm($user);

        /* Make sure we the user to be editted can be found */
        if ($spotUser === false) {
            $result->addError(sprintf(_('User %d can not be found'), $user['userid']));
        } // if
        $spotUser = array_merge($spotUser, $user);

        /* now make sure the entered data is somewhat valid */
        if ($result->isSuccess()) {
            $result = $this->validateUserRecord($spotUser, true);
        } // if

        if ($result->isSuccess()) {
            // actually update the user record
            $this->setUser($spotUser);

            /*
             * Update the users' password, but only when
             * a new password is given
             */
            if (!empty($spotUser['newpassword1'])) {
                $this->setUserPassword($spotUser);
            } // if

            /*
             * Did we get an groupmembership list? If so,
             * try to update it as well
             */
            if (!empty($groupList)) {
                // make sure there is at least one group
                if (count($groupList) < 1) {
                    $result->addError(_('A user must be member of at least one group'));
                } else {
                    // Mangle the current group membership to a common format
                    $tobeGroupList = [];
                    foreach ($groupList as $value) {
                        $tobeGroupList[] = $value['groupid'];
                    } // foreach

                    // and mangle the new requested group membership
                    $groupMembership = $this->getUserGroupMemberShip($spotUser['userid']);
                    $currentGroupList = [];
                    foreach ($groupMembership as $value) {
                        if ($value['ismember']) {
                            $currentGroupList[] = $value['id'];
                        } // if
                    } // foreach

                    /*
                     * Try to compare the grouplist with the current
                     * grouplist. If the grouplist changes, the user
                     * needs change group membership permissions
                     */
                    sort($currentGroupList, SORT_NUMERIC);
                    sort($tobeGroupList, SORT_NUMERIC);

                    /*
                     * If the groupmembership list changes, lets make sure
                     * the user has the specific permission
                     */
                    $groupDiff = (count($currentGroupList) != count($tobeGroupList));
                    for ($i = 0; $i < count($currentGroupList) && !$groupDiff; $i++) {
                        $groupDiff = ($currentGroupList[$i] != $tobeGroupList[$i]);
                    } // for

                    if ($groupDiff) {
                        if ($allowEditGroupMembership) {
                            $this->setUserGroupList($spotUser, $groupList);
                        } else {
                            $result->addError(_('Changing group membership is not allowed'));
                        } // else
                    } // if
                } // if
            } // if
        } // if

        return $result;
    }

    // updateUserRecord

    /*
     * Set the users' public and private keys
     */
    public function setUserRsaKeys($user, $privateKey, $publicKey)
    {
        $this->_userDao->setUserRsaKeys($user['userid'], $privateKey, $publicKey);
    }

    // setUserRsaKeys

    /*
     * Validate a group record
     */
    public function validateSecGroup(Dto_FormResult $result, array $group)
    {
        // Remove any lingering spaces
        $group['name'] = trim($group['name']);

        // Ensure a gorupname is given and it is not too short
        if (strlen($group['name']) < 3) {
            $result->addError(_('Invalid groupname'));
        } // if

        /*
         * Now list all security groups to make sure the groupname
         * is unique.
         *
         * This is not the most efficient way to do stuff, but we
         * do not expect dozens of security groups so this is acceptable
         */
        $secGroupList = $this->_userDao->getGroupList(null);
        foreach ($secGroupList as $secGroup) {
            if ($secGroup['name'] == $group['name']) {

                /*
                 * If we are editing, allow ourselves to be a 'duplicate'
                 */
                if (!isset($group['id'])) {
                    $group['id'] = -1;
                } // if

                if ($secGroup['id'] != $group['id']) {
                    $result->addError(_('Name is already in use'));
                } // if
            } // if
        } // foreach

        return $result;
    }

    // validateSecGroup

    /*
     * Returns the users' group membership
     */
    public function getUserGroupMemberShip($userId)
    {
        return $this->_userDao->getGroupList($userId);
    }

    // getUserGroupMemberShip

    /*
     * Removes a permission from a securitygroup
     */
    public function removePermFromSecGroup($groupId, $perm)
    {
        $result = new Dto_FormResult();
        $result = $this->allowedToEditGroup($result, $groupId);

        if ($result->isSuccess()) {
            $this->_userDao->removePermFromSecGroup($groupId, $perm);
        } // if

        return $result;
    }

    // removePermFromSecGroup

    /*
     * Sets a speific permission in a group to either allow or deny
     */
    public function setDenyForPermFromSecGroup($groupId, $perm)
    {
        $result = new Dto_FormResult();
        $result = $this->allowedToEditGroup($result, $groupId);

        if ($result->isSuccess()) {
            $this->_userDao->setDenyForPermFromSecGroup($groupId, $perm);
        } // if

        return $result;
    }

    // setDenyForPermFromSecGroup

    /*
     * Adds a permission to an security group
     */
    public function addPermToSecGroup($groupId, $perm)
    {
        $result = new Dto_FormResult();
        $result = $this->allowedToEditGroup($result, $groupId);

        if (!$result->isSuccess()) {
            return $result;
        } // if

        // Remove any superfluous spaces
        $perm['objectid'] = trim($perm['objectid']);

        /*
         * Make sure this specific permission is unique in the group
         *
         * We do not check the deny here, because we do not want
         * groups with both a deny and an allow setting as the results
         * would be undefined
         */
        $groupPerms = $this->_userDao->getGroupPerms($groupId);
        foreach ($groupPerms as $groupPerm) {
            if (($groupPerm['permissionid'] == $perm['permissionid']) &&
                ($groupPerm['objectid'] == $perm['objectid'])) {

                // Duplicate permission
                $result->addError(_('Permission already exists in this group'));
            } // if
        } // foreach

        // Add the permission to the group
        if ($result->isSuccess()) {
            $this->_userDao->addPermToSecGroup($groupId, $perm);
        } // if

        return $result;
    }

    // addPermToSecGroup

    /*
     * Update a group record
     */
    public function setSecGroup($groupId, $groupName)
    {
        $result = new Dto_FormResult();
        $result = $this->allowedToEditGroup($result, $groupId);

        $group = ['name' => trim($groupName), 'id' => $groupId];
        $result = $this->validateSecGroup($result, $group);

        if ($result->isSuccess()) {
            $this->_userDao->setSecurityGroup($group);
        } // if

        return $result;
    }

    // setSecGroup

    /*
     * Add an security group
     */
    public function addSecGroup($groupName)
    {
        $result = new Dto_FormResult();

        /* Remove extra spaces from the groupname */
        $group = ['name' => trim($groupName)];
        $result = $this->validateSecGroup($result, $group);

        if ($result->isSuccess()) {
            $this->_userDao->addSecurityGroup($groupName);
        } // if

        return $result;
    }

    // addSecGroup

    /*
     * Retrieve a group record
     */
    public function getSecGroup($groupId)
    {
        $tmpGroup = $this->_userDao->getSecurityGroup($groupId);
        if (!empty($tmpGroup)) {
            return $tmpGroup[0];
        } else {
            return false;
        } // else
    }

    // getSecGroup

    /*
     * Make sure we can edit this group record
     */
    public function allowedToEditGroup(Dto_FormResult $result, $groupId)
    {
        /*
         * Make sure the security group exists, and can be editted
         */
        $secGroup = $this->getSecGroup($groupId);
        if (empty($secGroup)) {
            $result->addError(_("Group doesn't exist"));
        } elseif ($groupId < 6) {
            $result->addError(_('Built-in groups can not be edited'));
        } // else

        return $result;
    }

    // allowedToEditGroup

    /*
     * Removes a group record
     */
    public function removeSecGroup($groupId)
    {
        $result = new Dto_FormResult();
        $result = $this->allowedToEditGroup($result, $groupId);

        if ($result->isSuccess()) {
            $this->_userDao->removeSecurityGroup($groupId);
        } // else

        return $result;
    }

    // removeSecGroup

    /**
     * Retrieves an user record.
     *
     * @returns array|bool
     */
    public function getUser($userid)
    {
        return $this->_userDao->getUser($userid);
    }

    // getUser()

    /*
     * Update a user record (does not change the password)
     */
    public function setUser($user)
    {
        /*
         * We always assume the password is not set using
         * this function, hence the password is never updated
         * by setUser()
         */
        $this->_userDao->setUser($user);
    }

    // setUser()

    /*
     * Removes an user record
     */
    public function removeUser($userId)
    {
        $result = new Dto_FormResult('success');

        // Do not allow the builtin accounts to be deleted
        if ($userId <= SPOTWEB_ADMIN_USERID) {
            $result->addError(_('Admin and Anonymous can not be deleted'));
        } else {
            $this->_userDao->deleteUser($userId);
        } // else

        return $result;
    }

    // removeUser()

    /*
     * Retrieves an RSA key from the users' record.
     */
    public function getUserPrivateRsaKey($userId)
    {
        return $this->_userDao->getUserPrivateRsaKey($userId);
    }

    // getUserPrivateRsaKey

    /*
     * Changes the avatar of this user
     */
    public function changeAvatar($userId, $imageString)
    {
        $result = new Dto_FormResult();

        /*
         * Don't allow images larger than 4000 bytes
         */
        if (strlen($imageString) > 4000) {
            $result->addError(_('An avatar image has a maximum of 4000 bytes'));
        } // if

        /*
         * Make sure the image can be read, and stuff
         */
        $svc_ImageUtil = new Services_Image_Util();
        $dimensions = $svc_ImageUtil->getImageDimensions($imageString);
        if ($dimensions === false) {
            $result->addError(_('Invalid avatar image was supplied'));
        } // if

        /*
         * If the user supplied an BMP file, convert it to a
         * JPEG file
         */
        if ($dimensions['isbmp']) {
            $svc_ImageBmpConverter = new Services_Image_BmpConverter();
            $imageString = $svc_ImageBmpConverter->convertBmpImageStringToJpeg($imageString, $dimensions);
        } // if

        if ($result->isSuccess()) {
            /*
             * We store the images base64 encoded
             */
            $imageString = base64_encode($imageString);

            /*
             * and update the database
             */
            $this->_userDao->setUserAvatar($userId, $imageString);
        } // if

        return $result;
    }

    // changeAvatar

    /*
     * Blacklist a specific spotter
     */
    public function addSpotterToList($currentUser, $spotterId, $origin, $idtype)
    {
        $result = new Dto_FormResult();

        if (($idtype < 0) || ($idtype > 2)) {
            /* Invalid id type, dont allow this */
            return null;
        } // if

        if (!$this->allowedToPost($currentUser)) {
            $result->addError(_('User is not allowed to maintain spotstatelist'));
        } else {
            $this->_daoFactory->getBlackWhiteListDao()->addSpotterToList($spotterId, $currentUser['userid'], $origin, $idtype);
        } // else

        return $result;
    }

    // addSpotterToList

    /*
     * Removes a specific spotter from the blacklis
     */
    public function removeSpotterFromList($currentUser, $spotterId)
    {
        $result = new Dto_FormResult();

        if (!$this->allowedToPost($currentUser)) {
            $result->addError(_('User is not allowed to maintain spotstatelist'));
        } else {
            $this->_daoFactory->getBlackWhiteListDao()->removeSpotterFromList($spotterId, $currentUser['userid']);
        } // else

        return $result;
    }

    // removeSpotterFromList

    /*
     * Clears the users' download list
     */
    public function clearDownloadList($ourUserId)
    {
        $this->_daoFactory->getSpotStateListDao()->clearDownloadList($ourUserId);
    }

    // clearDownloadList

    /*
     * Mark all spots as read
     */
    public function markAllAsRead($ourUserId)
    {
        return $this->_daoFactory->getSpotStateListDao()->markAllAsRead($ourUserId);
    }

    // markAllAsRead
} // class Services_User_Record
