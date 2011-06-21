<?php
class SpotNotifications {
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
	const notifytype_retriever_finished		= 'retriever_finished';
	const notifytype_user_added				= 'user_added';

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
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

	function sendNzbHandled($action, $fullSpot) {
		switch ($action) {
			case 'save'	  			: $title = 'NZB opgeslagen!';		$body = $fullSpot['title'] . ' opgeslagen in ' . $this->_currentSession['user']['prefs']['nzbhandling']['local_dir']; break;
			case 'runcommand'		: $title = 'Programma gestart!';	$body = $this->_currentSession['user']['prefs']['nzbhandling']['command'] . ' gestart voor ' . $fullSpot['title']; break;
			case 'push-sabnzbd' 	: 
			case 'client-sabnzbd' 	: $title = 'NZB verstuurd!';		$body = $fullSpot['title'] . ' verstuurd naar SABnzbd+'; break;
			case 'nzbget'			: $title = 'NZB verstuurd!';		$body = $fullSpot['title'] . ' verstuurd naar NZBGet'; break;
			default					: return;
		} # switch
		$this->newSingleMessage($this->_currentSession, SpotNotifications::notifytype_nzb_handled, 'Single', $title, $body);
	} # sendNzbHandled

	function sendRetrieverFinished($newSpotCount, $newCommentCount) {
		if ($newSpotCount > 0) {
			$body = ($newSpotCount == 1) ? "Er is " . $newSpotCount . " spot" : "Er zijn " . $newSpotCount . " spots";
			if ($newCommentCount > 0) {
				$body .= " en " . $newCommentCount;
				$body .= ($newCommentCount == 1) ? " reactie" : " reacties";
			} # if
			$body .= " opgehaald.";

			$this->newMultiMessage(SpotNotifications::notifytype_retriever_finished, 'Nieuwe spots opgehaald!', $body);
		} # if
	} # sendRetrieverFinished

	function sendUserAdded($username, $password) {
		$this->newMultiMessage(SpotNotifications::notifytype_user_added, 'Gebruiker toegevoegd!', 'Gebruiker ' . $username . ' met wachtwoord ' . $password . ' is toegevoegd.');
	} # sendUserAdded

	function sendNewUserMail($user) {
		# Omdat het versturen van dit bericht expliciet is opgegeven, worden er
		# geen security-checks gedaan voor de ontvanger.
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_services, 'email')) {
			$title = "Spotweb registratie";
			$body = "Hallo " . $user['firstname'] . " " . $user['lastname'] . "," . PHP_EOL . PHP_EOL;
			$body .= "Er is zojuist een account voor je aangemaakt op " . $this->_settings->get('spotweburl') . "." . PHP_EOL;
			$body .= "Je kunt inloggen met de volgende gegevens:" . PHP_EOL . PHP_EOL;
			$body .= "Gebruikersnaam:\t\t" . $user['username'] . PHP_EOL;
			$body .= "Wachtwoord:\t\t" . $user['newpassword1'] . PHP_EOL . PHP_EOL;
			$body .= "Met vriendelijke groet," . PHP_EOL . $this->_currentSession['user']['firstname'] . " " . $this->_currentSession['user']['lastname'];

			$user['prefs']['notifications']['email']['sender'] = $this->_currentSession['user']['mail'];
			$user['prefs']['notifications']['email']['receiver'] = $user['mail'];
			$this->_notificationServices['email'] = Notifications_Factory::build('Spotweb', 'email', $user['prefs']['notifications']['email']);
			$this->_notificationServices['email']->sendMessage('Single', $title, $body, $this->_settings->get('spotweburl'));
		} # if
	} # sendNewUserMail

	function newSingleMessage($user, $objectId, $type, $title, $body) {
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
						$this->_db->addNewNotification($tmpUser['user']['userid'], $objectId, $type, $title, $body);
						break;
					} # if
				} # if
			} # foreach
		} # if

		if ($type == 'Single') {
			$this->sendNowOrLater($tmpUser['user']['userid']);
		} # if
	} # newSingleMessage

	function newMultiMessage($objectId, $title, $body) {
		$userArray = $this->_db->listUsers("", 0, 9999999);
		foreach ($userArray['list'] as $user['user']) {
			$this->newSingleMessage($user, $objectId, 'Multi', $title, $body);
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
					$notificationService->sendMessage($newMessage['type'], $newMessage['title'], $newMessage['body'], $spotweburl);
				} # foreach

				# Alle services resetten, deze mogen niet hergebruikt worden
				$this->_notificationServices = array();

				$this->_db->markNotificationSent($newMessage['id']);
			} # foreach message
		} # foreach user
	} # sendMessages

} # SpotsNotifications
