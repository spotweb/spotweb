<?php
class SpotNotifications {
	private $_notificationTemplate = array();
	private $_notificationServices = array();
	private $_spotSecTmp;
	private $_spotSec;
	private $_currentSession;
	private $_settings;
	private $_db;

	/*
	 * Constants used for securing the system
	 */
	const notifytype_nzb_handled			= 'nzb_handled';
	const notifytype_watchlist_handled		= 'watchlist_handled';
	const notifytype_retriever_finished		= 'retriever_finished';
	const notifytype_spot_posted			= 'spot_posted';
	const notifytype_user_added				= 'user_added';

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
		$this->_notificationTemplate = new SpotNotificationTemplate($this->_db, $this->_settings, $this->_currentSession);
	} # ctor

	function register() {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, '')) {
			$notifProviders = Notifications_Factory::getActiveServices();
			foreach ($notifProviders as $notifProvider) {
				if ($this->_currentSession['user']['prefs']['notifications'][$notifProvider]['enabled']) {
					if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, $notifProvider)) {
						$this->_notificationServices[$notifProvider] = Notifications_Factory::build('Spotweb', $notifProvider, $this->_currentSession['user']['prefs']['notifications'][$notifProvider]);
					} # if
				} # if
			} # foreach
		} # if

		foreach($this->_notificationServices as $notificationService) {
			$notificationService->register();
		} # foreach
	} # register

	function sendWatchlistHandled($action, $messageid) {
		$spot = $this->_db->getSpotHeader($messageid);
		switch ($action) {
			case 'remove'	: $notification = $this->_notificationTemplate->template('watchlist_removed', array('spot' => $spot)); break;
			case 'add'		: $notification = $this->_notificationTemplate->template('watchlist_added', array('spot' => $spot)); break;
		} # switch
		$this->newSingleMessage($this->_currentSession, SpotNotifications::notifytype_watchlist_handled, 'Single', $notification);
	} # sendWatchlistHandled

	function sendNzbHandled($action, $spot) {
		switch ($action) {
			case 'save'				: $notification = $this->_notificationTemplate->template('nzb_save', array('spot' => $spot, 'nzbhandling' => $this->_currentSession['user']['prefs']['nzbhandling'])); break;
			case 'runcommand'		: $notification = $this->_notificationTemplate->template('nzb_runcommand', array('spot' => $spot, 'nzbhandling' => $this->_currentSession['user']['prefs']['nzbhandling'])); break;
			case 'push-sabnzbd' 	: 
			case 'client-sabnzbd'	: $notification = $this->_notificationTemplate->template('nzb_sabnzbd', array('spot' => $spot)); break;
			case 'nzbget'			: $notification = $this->_notificationTemplate->template('nzb_nzbget', array('spot' => $spot)); break;
			default					: return;
		} # switch
		
		$this->newSingleMessage($this->_currentSession, SpotNotifications::notifytype_nzb_handled, 'Single', $notification);
	} # sendNzbHandled

	function sendRetrieverFinished($newSpotCount, $newCommentCount, $newReportCount) {
		if ($newSpotCount > 0) {
			$notification = $this->_notificationTemplate->template('retriever_finished', array('newSpotCount' => $newSpotCount, 'newCommentCount' => $newCommentCount, 'newReportCount' => $newReportCount));
			$this->newMultiMessage(SpotNotifications::notifytype_retriever_finished, $notification);
		} # if
	} # sendRetrieverFinished

	function sendSpotPosted($spot) {
		$notification = $this->_notificationTemplate->template('spot_posted', array('spot' => $spot));
		$this->newSingleMessage($this->_currentSession, SpotNotifications::notifytype_spot_posted, 'Single', $notification);
	} # sendSpotPosted

	function sendUserAdded($username, $password) {
		$notification = $this->_notificationTemplate->template('user_added', array('username' => $username, 'password' => $password));
		$this->newMultiMessage(SpotNotifications::notifytype_user_added, $notification);
	} # sendUserAdded

	function sendNewUserMail($user) {
		# Omdat het versturen van dit bericht expliciet is opgegeven, worden er
		# geen security-checks gedaan voor de ontvanger.
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, 'email')) {
			$notification = $this->_notificationTemplate->template('user_added_email', array('user' => $user, 'adminUser' => $this->_currentSession['user']));

			$user['prefs']['notifications']['email']['sender'] = $this->_currentSession['user']['mail'];
			$user['prefs']['notifications']['email']['receiver'] = $user['mail'];
			$this->_notificationServices['email'] = Notifications_Factory::build('Spotweb', 'email', $user['prefs']['notifications']['email']);
			$this->_notificationServices['email']->sendMessage('Single', $notification['title'], implode(PHP_EOL, $notification['body']), $this->_settings->get('spotweburl'));
			$this->_notificationServices = array();
		} # if
	} # sendNewUserMail

	function newSingleMessage($user, $objectId, $type, $notification) {
		# Aangezien het niet zeker kunnen zijn als welke user we dit stuk
		# code uitvoeren, halen we voor de zekerheid opnieuw het user record op
		$tmpUser['user'] = $this->_db->getUser($user['user']['userid']);
		$tmpUser['security'] = new SpotSecurity($this->_db, $this->_settings, $tmpUser['user']);
		$this->_spotSecTmp = $tmpUser['security'];

		if ($this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_services, '')) {
			$notifProviders = Notifications_Factory::getActiveServices();
			foreach ($notifProviders as $notifProvider) {
				if ($tmpUser['user']['prefs']['notifications'][$notifProvider]['enabled'] && $tmpUser['user']['prefs']['notifications'][$notifProvider]['events'][$objectId]) {
					if ($this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_types, '') &&
						$this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_types, $objectId) &&
						$this->_spotSecTmp->allowed(SpotSecurity::spotsec_send_notifications_services, $notifProvider)
					) {
						$this->_db->addNewNotification($tmpUser['user']['userid'], $objectId, $type, $notification['title'], implode(PHP_EOL, $notification['body']));
						break;
					} # if
				} # if
			} # foreach
		} # if

		if ($type == 'Single') {
			$this->sendNowOrLater($tmpUser['user']['userid']);
		} # if
	} # newSingleMessage

	function newMultiMessage($objectId, $notification) {
		$userArray = $this->_db->listUsers("", 0, 9999999);
		foreach ($userArray['list'] as $user['user']) {
			$this->newSingleMessage($user, $objectId, 'Multi', $notification);
		} # foreach

		$this->sendNowOrLater(0);
	} # newMultiMessage

	function sendNowOrLater($userId) {
		# TODO: optioneel maken of berichten direct worden verstuurd of via cron
		# Tot die tijd versturen we ze direct
		$this->sendMessages($userId);
	} # sendNowOrLater

	function sendMessages($userId) {
		if ($userId == 0) {
			$userList = $this->_db->listUsers("", 0, 9999999);
		} else {
			$thisUser = $this->_db->getUser($userId);
			$userList['list'] = array($thisUser);
		} # else

		foreach ($userList['list'] as $user) {
			# Omdat we vanuit listUsers() niet alle velden meekrijgen
			# vragen we opnieuw het user record op
			$user = $this->_db->getUser($user['userid']);
			$security = new SpotSecurity($this->_db, $this->_settings, $user);

			# Om e-mail te kunnen versturen hebben we iets meer data nodig
			$adminUsr = $this->_db->getUser(SPOTWEB_ADMIN_USERID);
			$user['prefs']['notifications']['email']['sender'] = $adminUsr['mail'];
			$user['prefs']['notifications']['email']['receiver'] = $user['mail'];

			# Twitter heeft ook extra settings nodig
			$user['prefs']['notifications']['twitter']['consumer_key'] = $this->_settings->get('twitter_consumer_key');
			$user['prefs']['notifications']['twitter']['consumer_secret'] = $this->_settings->get('twitter_consumer_secret');

			$newMessages = $this->_db->getUnsentNotifications($user['userid']);
			foreach ($newMessages as $newMessage) {
				$objectId = $newMessage['objectid'];
				$spotweburl = ($this->_settings->get('spotweburl') == 'http://mijnuniekeservernaam/spotweb/') ? '' : $this->_settings->get('spotweburl');

				$notifProviders = Notifications_Factory::getActiveServices();
				foreach ($notifProviders as $notifProvider) {
					if ($user['prefs']['notifications'][$notifProvider]['enabled'] && $user['prefs']['notifications'][$notifProvider]['events'][$objectId]) {
						if ($security->allowed(SpotSecurity::spotsec_send_notifications_services, $notifProvider)) {
							$this->_notificationServices[$notifProvider] = Notifications_Factory::build('Spotweb', $notifProvider, $user['prefs']['notifications'][$notifProvider]);
						} # if
					} # if
				} # foreach

				# nu wordt het bericht pas echt verzonden
				foreach($this->_notificationServices as $notificationService) {
					$notificationService->sendMessage($newMessage['type'], utf8_decode($newMessage['title']), utf8_decode($newMessage['body']), $spotweburl);
				} # foreach

				# Alle services resetten, deze mogen niet hergebruikt worden
				$this->_notificationServices = array();

				# Als dit bericht ging over het aanmaken van een nieuwe user, verwijderen we
				# het plaintext wachtwoord uit de database uit veiligheidsoverwegingen.
				if ($objectId == SpotNotifications::notifytype_user_added) {
					$body = explode(" ", $newMessage['body']);
					$body[4] = '[deleted]';
					$newMessage['body'] = implode(" ", $body);
				} # if

				$newMessage['sent'] = 1;
				$this->_db->updateNotification($newMessage);
			} # foreach message
		} # foreach user
	} # sendMessages

} # SpotsNotifications

