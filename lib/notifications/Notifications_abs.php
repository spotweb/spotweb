<?php
require_once "lib/notifications/growl/class.growl.php";
require_once "lib/notifications/notifo/Notifo_API.php";
require_once "lib/notifications/prowl/Connector.php";

class Notifications_abs {
	private $_notifs;
	private $_spotSec;
	private $_currentSession;
	private $_settings;
	private $_db;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
		$this->_notifs = $currentSession['user']['prefs']['notifications'];
	} # ctor

	function register() {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, '')) {
			if ($this->_notifs['growl']['enabled']) {
				if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, 'growl')) {
					$growl = new Growl($this->_notifs['growl']['host'], $this->_notifs['growl']['password'], 'Spotweb');
					$growl->addNotification('User');
					$growl->addNotification('Admin');
					$growl->register();
				} # if
			} # if
		} # if
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
			$this->newSingleMessage($this->_currentSession['user']['userid'], 'nzb_handled', 'User', $title, $body);
		} # if
	} # sendNzbHandled

	# TODO: deze functie opvragen vanaf betreffende actie
	function sendRetrieverFinished() {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			$this->newMultiMessage('retriever_finished', 'Admin', 'Spots opgehaald!', 'Nieuwe spots zijn met succes opgehaald.');
		} # if
	} # sendSpotsRetrieved

	# TODO: deze functie opvragen vanaf betreffende actie en melding goed zetten
	function sendUserAdded($username) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			$this->newMultiMessage('user_added', 'Admin', 'Gebruiker toegevoegd!', 'Gebruiker ' . $username . ' is toegevoegd.');
		} # if
	} # sendUserAdded
	
	function newSingleMessage($userId, $objectId, $type, $title, $body) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, '')) {
			foreach (array('email', 'growl', 'libnotify', 'notifo', 'prowl') as $notifProvider) {
				if ($this->_notifs[$notifProvider]['enabled'] && $this->_notifs[$notifProvider]['events'][$objectId]) {
					$this->_db->addNewNotification($userId, $objectId, $type, $title, $body);
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
						$growl = new Growl($user['prefs']['notifications']['growl']['host'], $user['prefs']['notifications']['growl']['password'], 'Spotweb');
						$growl->notify($newMessage['type'], $newMessage['title'], $newMessage['body']);
					} # if
				} # Growl

				# TODO libnotify-library toevoegen en aanspreken
				if ($user['prefs']['notifications']['libnotify']['enabled'] && $user['prefs']['notifications']['libnotify']['events'][$objectId]) {
					if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'libnotify')) {
						//$libnotify->notify($newMessage['title'], $newMessage['body']);
					} # if
				} # libnotify

				if ($user['prefs']['notifications']['notifo']['enabled'] && $user['prefs']['notifications']['notifo']['events'][$objectId]) {
					if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'notifo')) {
						$notifo = new Notifo_API($user['prefs']['notifications']['notifo']['username'], $user['prefs']['notifications']['notifo']['api']);
						$params = array('label' => 'Spotweb',
										'title' => $newMessage['title'],
										'msg' => $newMessage['body'],
										'uri' => $this->_settings->get('spotweburl'));
						$response = $notifo->send_notification($params);
					} # if
				} # Notifo

				# Prowl gebruikt namespaces, waarvoor PHP 5.3 geintroduceerd werden met PHP 5.3
				if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
					if ($user['prefs']['notifications']['prowl']['enabled'] && $user['prefs']['notifications']['prowl']['events'][$objectId]) {
						if ($security->allowed(SpotSecurity::spotsec_send_notifications, 'prowl')) {
							$oProwl = new \Prowl\Connector();
							$oMsg = new \Prowl\Message();
							$oMsg->addApiKey($user['prefs']['notifications']['prowl']['apikey']);
							$oMsg->setApplication('Spotweb');
							$oMsg->setEvent($newMessage['title']);
							$oMsg->setDescription($newMessage['body']);

							$oFilter = new \Prowl\Security\PassthroughFilterImpl();
							$oProwl->setFilter($oFilter);
							$oProwl->setIsPostRequest(true);
							$oResponse = $oProwl->push($oMsg);
						} # if
					} # if
				} # Prowl

				$this->_db->markNotificationSent($newMessage['id']);
			} # foreach message
		} # foreach user
	} # sendMessages

} # SpotsNotifications