<?php
require_once "lib/notifications/prowl/Connector.php";

class Notifications_prowl extends SpotNotifications {
	var $growlObj;

	function __construct($apikey) {
		$this->prowlObj = new \Prowl\Connector();
	} # ctor

	function sendMessage($type, $title, $body) {
		$this->prowlObj = new \Prowl\Connector();
		$oMsg = new \Prowl\Message();
		$oMsg->addApiKey($user['prefs']['notifications']['prowl']['apikey']);
		$oMsg->setApplication('Spotweb');
		$oMsg->setEvent($title);
		$oMsg->setDescription($body);

		$oFilter = new \Prowl\Security\PassthroughFilterImpl();
		$this->prowlObj->setFilter($oFilter);
		$this->prowlObj->setIsPostRequest(true);
		$oResponse = $this->prowlObj->push($oMsg);
	} # sendMessage

} # SpotsNotifications