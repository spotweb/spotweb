<?php
require_once "lib/notifications/class.growl.php";
require_once "lib/notifications/Notifo_API.php";
require_once "lib/notifications/prowl/Connector.php";

class SpotsNotifications {
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
				case 'save'	  			: $title = 'NZB opgeslagen!';		$message = $fullSpot['title'] . ' opgeslagen in ' . $this->_currentSession['user']['prefs']['nzbhandling']['local_dir']; break;
				case 'runcommand'		: $title = 'Programma gestart!';	$message = $this->_currentSession['user']['prefs']['nzbhandling']['command'] . ' gestart voor ' . $fullSpot['title']; break;
				case 'push-sabnzbd' 	: 
				case 'client-sabnzbd' 	: $title = 'NZB verstuurd!';		$message = $fullSpot['title'] . ' verstuurd naar SABnzbd+'; break;
				case 'nzbget'			: $title = 'NZB verstuurd!';		$message = $fullSpot['title'] . ' verstuurd naar NZBGet'; break;
			} # switch
			$this->sendMessage('nzb_handled', 'User', $title, $message);
		} # if
	} # sendNzbHandled

	# TODO: deze functie opvragen vanaf betreffende actie
	function sendRetrieverFinished() {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			$this->sendMessage('retriever_finished', 'Admin', 'Spots opgehaald!', 'Nieuwe spots zijn met succes opgehaald.');
		} # if
	} # sendSpotsRetrieved

	# TODO: deze functie opvragen vanaf betreffende actie en melding goed zetten
	function sendUserAdded($user) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, '') && $this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			$this->sendMessage('user_added', 'Admin', 'Gebruiker toegevoegd!', 'Nieuwe spots zijn met succes opgehaald.');
		} # if
	} # sendUserAdded

	function sendMessage($messageType, $name, $title, $message) {
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, '')) {
			if ($this->_notifs['growl']['enabled'] && $this->_notifs['growl']['events'][$messageType]) {
				if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, 'growl')) {
					$growl = new Growl($this->_notifs['growl']['host'], $this->_notifs['growl']['password'], 'Spotweb');
					$growl->notify($name, $title, $message);
				} # if
			} # if

			# TODO libnotify-library toevoegen en aanspreken
			if ($this->_notifs['libnotify']['enabled'] && $this->_notifs['libnotify']['events'][$messageType]) {
				if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, 'libnotify')) {
					//$libnotify->notify($name, $title, $message);
				} # if
			} # if

			if ($this->_notifs['notifo']['enabled'] && $this->_notifs['notifo']['events'][$messageType]) {
				if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, 'notifo')) {
					$notifo = new Notifo_API($this->_notifs['notifo']['username'], $this->_notifs['notifo']['api']);
					$params = array('label' => 'Spotweb',
									'title' => $title,
									'msg' => $message,
									'uri' => $this->_settings->get('spotweburl'));
					$response = $notifo->send_notification($params);
				} # if
			} # if

			if ($this->_notifs['prowl']['enabled'] && $this->_notifs['prowl']['events'][$messageType]) {
				if ($this->_spotSec->allowed(SpotSecurity::spotsec_send_notifications, 'prowl')) {
					$oProwl = new \Prowl\Connector();
					$oMsg = new \Prowl\Message();
					$oMsg->addApiKey($this->_notifs['prowl']['apikey']);
					$oMsg->setApplication('Spotweb');
					$oMsg->setEvent($title);
					$oMsg->setDescription($message);

					$oFilter = new \Prowl\Security\PassthroughFilterImpl();
					$oProwl->setFilter($oFilter);
					$oProwl->setIsPostRequest(true);
					$oResponse = $oProwl->push($oMsg);
				} # if
			} # if
		} # if
	} # register

} # SpotsNotifications