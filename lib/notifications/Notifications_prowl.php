<?php
require_once "lib/notifications/prowl/Connector.php";

class Notifications_prowl extends Notifications_abs {
	var $growlObj;

	function __construct($host, $username, $secret) {
		$this->prowlObj = new \Prowl\Connector();
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($type, $title, $body) {
		$this->prowlObj = new \Prowl\Connector();
		$oMsg = new \Prowl\Message();
		$oMsg->addApiKey($secret);
		$oMsg->setApplication('Spotweb');
		$oMsg->setEvent($title);
		$oMsg->setDescription($body);

		$oFilter = new \Prowl\Security\PassthroughFilterImpl();
		$this->prowlObj->setFilter($oFilter);
		$this->prowlObj->setIsPostRequest(true);
		$oResponse = $this->prowlObj->push($oMsg);
	} # sendMessage

} # SpotsNotifications