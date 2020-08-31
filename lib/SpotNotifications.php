<?php

class SpotNotifications
{
    private $_notificationTemplate = [];
    private $_notificationServices = [];
    /**
     * @var SpotSecurity
     */
    private $_spotSecTmp;
    /**
     * @var SpotSecurity
     */
    private $_spotSec;
    /**
     * @var array
     */
    private $_currentSession;
    /**
     * @var Services_Settings_Base
     */
    private $_settings;
    /**
     * @var Dao_Factory
     */
    private $_daoFactory;

    /*
     * Constants used for securing the system
     */
    const notifytype_nzb_handled = 'nzb_handled';
    const notifytype_watchlist_handled = 'watchlist_handled';
    const notifytype_retriever_finished = 'retriever_finished';
    const notifytype_report_posted = 'report_posted';
    const notifytype_spot_posted = 'spot_posted';
    const notifytype_user_added = 'user_added';
    const notifytype_newspots_for_filter = 'newspots_for_filter';

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;
        $this->_currentSession = $currentSession;
        $this->_spotSec = $currentSession['security'];
        $this->_notificationTemplate = new SpotNotificationTemplate($this->_settings, $this->_currentSession);
    }

    // ctor

    /*
     * Some notification providers need explicit registration (eg,
     * a twitter signup/approval). We use this function to provide
     * for this
     */
    public function register()
    {
        if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, '')) {
            // Boxcar requires additional settings
            $this->_currentSession['user']['prefs']['notifications']['boxcar']['api_key'] = $this->_settings->get('boxcar_api_key');
            $this->_currentSession['user']['prefs']['notifications']['boxcar']['api_secret'] = $this->_settings->get('boxcar_api_secret');

            $notifProviders = Notifications_Factory::getActiveServices();
            foreach ($notifProviders as $notifProvider) {
                if ($this->_currentSession['user']['prefs']['notifications'][$notifProvider]['enabled']) {
                    if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, $notifProvider)) {
                        $this->_notificationServices[$notifProvider] = Notifications_Factory::build('Spotweb', $notifProvider, $this->_currentSession['user']['prefs']['notifications'][$notifProvider]);
                    } // if
                } // if
            } // foreach
        } // if

        foreach ($this->_notificationServices as $notificationService) {
            $notificationService->register();
        } // foreach
    }

    // register

    /*
     * Send a notification when an watchlist addition or removal
     * is handled
     */
    public function sendWatchlistHandled($action, $messageid)
    {
        $spotDao = $this->_daoFactory->getSpotDao();
        $spot = $spotDao->getSpotHeader($messageid);

        switch ($action) {
            case 'remove': $notification = $this->_notificationTemplate->template('watchlist_removed', ['spot' => $spot]); break;
            case 'add': $notification = $this->_notificationTemplate->template('watchlist_added', ['spot' => $spot]); break;
        } // switch
        $this->newSingleMessage($this->_currentSession, self::notifytype_watchlist_handled, 'Single', $notification);
    }

    // sendWatchlistHandled

    /*
     * Send a notification when an NZB file is handled by Spotweb
     * Because Spotweb does not handle the download itself, the
     * SpotWeb cannot send this message until the file is actually
     * downloaded, so this message might come too early.
     */
    public function sendNzbHandled($action, $spot)
    {
        switch ($action) {
            case 'save': $notification = $this->_notificationTemplate->template('nzb_save', ['spot' => $spot, 'nzbhandling' => $this->_currentSession['user']['prefs']['nzbhandling']]); break;
            case 'runcommand': $notification = $this->_notificationTemplate->template('nzb_runcommand', ['spot' => $spot, 'nzbhandling' => $this->_currentSession['user']['prefs']['nzbhandling']]); break;
            case 'push-sabnzbd':
            case 'client-sabnzbd': $notification = $this->_notificationTemplate->template('nzb_sabnzbd', ['spot' => $spot]); break;
            case 'nzbget': $notification = $this->_notificationTemplate->template('nzb_nzbget', ['spot' => $spot]); break;
            case 'nzbvortex': $notification = $this->_notificationTemplate->template('nzb_nzbvortex', ['spot' => $spot]); break;
            default: return;
        } // switch

        $this->newSingleMessage($this->_currentSession, self::notifytype_nzb_handled, 'Single', $notification);
    }

    // sendNzbHandled

    /*
     * When a specific user defined filter in Spotweb has new spots
     * we can send the user for this filter a notification.
     */
    public function sendNewSpotsForFilter($userId, $filterTitle, $newSpotCount)
    {
        $notification = $this->_notificationTemplate->template('newspots_for_filter', ['filtertitle' => $filterTitle, 'newCount' => $newSpotCount]);

        echo 'Sending notification to user: '.$userId.' for filter: '.$filterTitle.', it has '.$newSpotCount.' new spots'.PHP_EOL;

        /* and send the message */
        $user = ['user' => ['userid' => $userId],
            'session'   => ['ipaddr' => '127.0.0.1'], ];
        $this->newSingleMessage($user, self::notifytype_newspots_for_filter, 'Single', $notification);
    }

    // sendNewSpotsForFilter

    /*
     * We can notify the user when the retrieve process has done
     * retrieving and actually retrieved new spots.
     */
    public function sendRetrieverFinished($newSpotCount, $newCommentCount, $newReportCount)
    {
        if ($newSpotCount > 0) {
            $notification = $this->_notificationTemplate->template('retriever_finished', ['newSpotCount' => $newSpotCount, 'newCommentCount' => $newCommentCount, 'newReportCount' => $newReportCount]);
            $this->newMultiMessage(self::notifytype_retriever_finished, $notification);
        } // if
    }

    // sendRetrieverFinished

    /*
     * If a spot is reported to be spam or incorect, we can
     * send this notification.
     */
    public function sendReportPosted($messageid)
    {
        $spotDao = $this->_daoFactory->getSpotDao();
        $spot = $spotDao->getSpotHeader($messageid);

        $notification = $this->_notificationTemplate->template('report_posted', ['spot' => $spot]);
        $this->newSingleMessage($this->_currentSession, self::notifytype_report_posted, 'Single', $notification);
    }

    // sendReportPosted

    /*
     * Send a notification after a new spot has been
     * posted
     */
    public function sendSpotPosted($spot)
    {
        $notification = $this->_notificationTemplate->template('spot_posted', ['spot' => $spot]);
        $this->newSingleMessage($this->_currentSession, self::notifytype_spot_posted, 'Single', $notification);
    }

    // sendSpotPosted

    /*
     * send a notification when a new user is added
     */
    public function sendUserAdded($username, $password)
    {
        $notification = $this->_notificationTemplate->template('user_added', ['username' => $username, 'password' => $password]);
        $this->newMultiMessage(self::notifytype_user_added, $notification);
    }

    // sendUserAdded

    /*
     * Send the new user itself a notification mail
     * that his / her account has been created
     */
    public function sendNewUserMail($user)
    {
        // Because sending if the message has been explicitily reqested,
        // we do not run extra security-checks on the receiver.
        if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, 'welcomemail')) {
            $notification = $this->_notificationTemplate->template('user_added_email', ['user' => $user, 'adminUser' => $this->_currentSession['user']]);
            $user['prefs']['notifications']['email']['sender'] = $this->_settings->get('systemfrommail');
            $user['prefs']['notifications']['email']['receiver'] = $user['mail'];
            $this->_notificationServices['email'] = Notifications_Factory::build('Spotweb', 'email', $user['prefs']['notifications']['email']);
            $this->_notificationServices['email']->sendMessage('Single', $notification['title'], implode(PHP_EOL, $notification['body']), $this->_settings->get('spotweburl'), $this->_settings->get('smtp'));
            $this->_notificationServices = [];
        } // if
    }

    // sendNewUserMail

    /*
     * utility function to send a message to one person
     * only
     */
    private function newSingleMessage($user, $objectId, $type, $notification)
    {
        // Because it's not certain which user we are at this point.
        // we once again request the user records.
        $userDao = $this->_daoFactory->getUserDao();
        $notificationDao = $this->_daoFactory->getNotificationDao();

        $tmpUser['user'] = $userDao->getUser($user['user']['userid']);
        $tmpUser['security'] = new SpotSecurity(
            $this->_daoFactory->getUserDao(),
            $this->_daoFactory->getAuditDao(),
            $this->_settings,
            $tmpUser['user'],
            $user['session']['ipaddr']
        );
        $this->_spotSecTmp = $tmpUser['security'];

        if ($this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_services, '')) {
            $notifProviders = Notifications_Factory::getActiveServices();
            foreach ($notifProviders as $notifProvider) {
                if ($tmpUser['user']['prefs']['notifications'][$notifProvider]['enabled'] && $tmpUser['user']['prefs']['notifications'][$notifProvider]['events'][$objectId]) {
                    if ($this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_types, '') &&
                        $this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_types, $objectId) &&
                        $this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_services, $notifProvider)
                    ) {
                        $notificationDao->addNewNotification($tmpUser['user']['userid'], $objectId, $type, $notification['title'], implode(PHP_EOL, $notification['body']));
                        break;
                    } // if
                } // if
            } // foreach
        } // if

        if ($type == 'Single') {
            $this->sendNowOrLater($tmpUser['user']['userid']);
        } // if
    }

    // newSingleMessage

    /*
     * Send a notification to multiple users
     */
    private function newMultiMessage($objectId, $notification)
    {
        $userDao = $this->_daoFactory->getUserDao();
        $userArray = $userDao->getUserList();
        foreach ($userArray as $user['user']) {
            // Create a fake session array
            $user['session'] = ['ipaddr' => ''];

            $this->newSingleMessage($user, $objectId, 'Multi', $notification);
        } // foreach

        $this->sendNowOrLater(0);
    }

    // newMultiMessage

    public function sendNowOrLater($userId)
    {
        $this->sendMessages($userId);
    }

    // sendNowOrLater

    public function sendMessages($userId)
    {
        $userDao = $this->_daoFactory->getUserDao();
        $notificationDao = $this->_daoFactory->getNotificationDao();

        if ($userId == 0) {
            $userList = $userDao->getUserList();
        } else {
            $thisUser = $userDao->getUser($userId);
            $userList = [$thisUser];
        } // else

        foreach ($userList as $user) {
            // Because getUserList() does not provide all fields
            // we once again request the user record
            $user = $userDao->getUser($user['userid']);
            $security = new SpotSecurity(
                $this->_daoFactory->getUserDao(),
                $this->_daoFactory->getAuditDao(),
                $this->_settings,
                $user,
                ''
            );

            // In order to send mail we need some extra data nodig
            $user['prefs']['notifications']['email']['sender'] = $this->_settings->get('systemfrommail');
            $user['prefs']['notifications']['email']['receiver'] = $user['mail'];

            // Twitter need extra settings
            $user['prefs']['notifications']['twitter']['consumer_key'] = $this->_settings->get('twitter_consumer_key');
            $user['prefs']['notifications']['twitter']['consumer_secret'] = $this->_settings->get('twitter_consumer_secret');

            // Also Boxcar
            $user['prefs']['notifications']['boxcar']['api_key'] = $this->_settings->get('boxcar_api_key');
            $user['prefs']['notifications']['boxcar']['api_secret'] = $this->_settings->get('boxcar_api_secret');

            $newMessages = $notificationDao->getUnsentNotifications($user['userid']);
            foreach ($newMessages as $newMessage) {
                $objectId = $newMessage['objectid'];
                $spotweburl = ($this->_settings->get('spotweburl') == 'http://mijnuniekeservernaam/spotweb/') ? '' : $this->_settings->get('spotweburl');
                $notifProviders = Notifications_Factory::getActiveServices();
                foreach ($notifProviders as $notifProvider) {
                    if ($user['prefs']['notifications'][$notifProvider]['enabled'] && $user['prefs']['notifications'][$notifProvider]['events'][$objectId]) {
                        if ($security->allowed(SpotSecurity::spotsec_send_notifications_services, $notifProvider)) {
                            $this->_notificationServices[$notifProvider] = Notifications_Factory::build('Spotweb', $notifProvider, $user['prefs']['notifications'][$notifProvider]);
                        } // if
                    } // if
                } // foreach

                // Now the message really is sent.
                foreach ($this->_notificationServices as $notificationService) {
                    $notificationService->sendMessage($newMessage['type'], utf8_decode($newMessage['title']), utf8_decode($newMessage['body']), $spotweburl, $this->_settings->get('smtp'));
                } // foreach

                // Reset all service these cannot be re-used.
                $this->_notificationServices = [];

                // If this message involved a new user, we remove the
                // plaintext password from the database as a precaution.
                if ($objectId == self::notifytype_user_added) {
                    $body = explode(' ', $newMessage['body']);
                    $body[4] = '[deleted]';
                    $newMessage['body'] = implode(' ', $body);
                } // if

                $newMessage['sent'] = true;
                $notificationDao->updateNotification($newMessage);
            } // foreach message
        } // foreach user
    }

    // sendMessages
} // SpotsNotifications
