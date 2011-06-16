<?php
class SpotNotifications {
	private $_spotSec;
	private $_currentSession;
	private $_settings;
	private $_db;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
	} # ctor

	function register() {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, '')) {
			if ($this->_currentSession['user']['prefs']['notifications']['growl']['enabled']) {
				if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, 'growl')) {
					$this->notificationServices['growl'] = new Notifications_growl($this->_currentSession['user']['prefs']['notifications']['growl']['host'], false, $this->_currentSession['user']['prefs']['notifications']['growl']['password']);
				} # if
			} # if
		} # if

		foreach($this->notificationServices as $notificationService) {
			$notificationService->register();
		} # foreach
	} # register

	function sendNzbHandled($action, $fullSpot) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'nzb_handled')) {
			switch ($action) {
				case 'save'	  			: $title = 'NZB opgeslagen!';		$body = $fullSpot['title'] . ' opgeslagen in ' . $this->_currentSession['user']['prefs']['nzbhandling']['local_dir']; break;
				case 'runcommand'		: $title = 'Programma gestart!';	$body = $this->_currentSession['user']['prefs']['nzbhandling']['command'] . ' gestart voor ' . $fullSpot['title']; break;
				case 'push-sabnzbd' 	: 
				case 'client-sabnzbd' 	: $title = 'NZB verstuurd!';		$body = $fullSpot['title'] . ' verstuurd naar SABnzbd+'; break;
				case 'nzbget'			: $title = 'NZB verstuurd!';		$body = $fullSpot['title'] . ' verstuurd naar NZBGet'; break;
				default					: return;
			} # switch
			$this->newSingleMessage($this->_currentSession['user']['userid'], 'nzb_handled', 'Single', $title, $body);
		} # if
	} # sendNzbHandled

	function sendRetrieverFinished() {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			$this->newMultiMessage('retriever_finished', 'Multi', 'Spots opgehaald!', 'Nieuwe spots zijn met succes opgehaald.');
		} # if
	} # sendRetrieverFinished

	# TODO: deze functie opvragen vanaf betreffende actie en melding goed zetten
	function sendUserAdded($username) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			$this->newMultiMessage('user_added', 'Multi', 'Gebruiker toegevoegd!', 'Gebruiker ' . $username . ' is toegevoegd.');
		} # if
	} # sendUserAdded
	
	function newSingleMessage($userId, $objectId, $type, $title, $body) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, '')) {
			foreach (array('email', 'growl', 'libnotify', 'notifo', 'prowl') as $notifProvider) {
				if ($this->_currentSession['user']['prefs']['notifications'][$notifProvider]['enabled'] && $this->_currentSession['user']['prefs']['notifications'][$notifProvider]['events'][$objectId]) {
					$this->_db->addNewNotification($userId, $objectId, $type, $title, $body);
					break;
				} # if
			} # foreach
		} # if

		$this->sendNowOrLater($userId);
	} # newSingleMessage

	function newMultiMessage($objectId, $type, $title, $body) {
		$userArray = $this->_db->listUsers("", 0, 9999999);
		foreach ($userArray['list'] as $user) {
			# Omdat we vanuit listUsers() niet alle velden meekrijgen
			# vragen we opnieuw het user record op
			$user = $this->_db->getUser($user['userid']);
			$security = new SpotSecurity($this->_db, $this->_settings, $user);

			if ($security->allowed(SpotSecurity::spotsec_send_notifications, '')) {
				foreach (array('email', 'growl', 'libnotify', 'notifo', 'prowl') as $notifProvider) {
					if ($user['prefs']['notifications'][$notifProvider]['enabled'] && $user['prefs']['notifications'][$notifProvider]['events'][$objectId]) {
						if ($security->allowed(SpotSecurity::spotsec_send_notifications, $notifProvider)) {
							$this->_db->addNewNotification($user['userid'], $objectId, $type, $title, $body);
							break;
						} # if
					} # if
				} # foreach
			} # if
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

				if ($user['prefs']['notifications']['growl']['enabled'] && $user['prefs']['notifications']['growl']['events'][$objectId]) {
					if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'growl')) {
						$this->notificationServices['growl'] = new Notifications_growl($user['prefs']['notifications']['growl']['host'], false, $user['prefs']['notifications']['growl']['password']);
					} # if
				} # Growl

				# TODO libnotify-library toevoegen en aanspreken
				if ($user['prefs']['notifications']['libnotify']['enabled'] && $user['prefs']['notifications']['libnotify']['events'][$objectId]) {
					if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'libnotify')) {
						//$this->notificationServices['libnotify'] = new Notifications_libnotify(false, false, false);
					} # if
				} # libnotify

				if ($user['prefs']['notifications']['notifo']['enabled'] && $user['prefs']['notifications']['notifo']['events'][$objectId]) {
					if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'notifo')) {
						$this->notificationServices['notifo'] = new Notifications_notifo(false, $user['prefs']['notifications']['notifo']['username'], $user['prefs']['notifications']['notifo']['api']);
					} # if
				} # Notifo

				# Prowl gebruikt namespaces, welke geintroduceerd werden met PHP 5.3
				if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
					if ($user['prefs']['notifications']['prowl']['enabled'] && $user['prefs']['notifications']['prowl']['events'][$objectId]) {
						if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'prowl')) {
							$this->notificationServices['prowl'] = new Notifications_prowl(false, false, $user['prefs']['notifications']['prowl']['apikey']);
						} # if
					} # if
				} # Prowl

				# Hier wordt het bericht pas echt verzonden
				foreach($this->notificationServices as $notificationService) {
					$appName = 'Spotweb';
					$notificationService->sendMessage($appName, $newMessage['type'], $newMessage['title'], $newMessage['body'], $this->_settings->get('spotweburl'));
				} # foreach

				# Alle services resetten, deze mogen niet hergebruikt worden
				unset($this->notificationServices);

				$this->_db->markNotificationSent($newMessage['id']);
			} # foreach message
		} # foreach user
	} # sendMessages

} # SpotsNotifications