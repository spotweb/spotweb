<?php
require_once "lib/notifications/prowl/Connector.php";
require_once "lib/notifications/prowl/Message.php";
require_once "lib/notifications/prowl/Security/PassthroughFilterImpl.php";
require_once "lib/notifications/prowl/Security/Secureable.php";

class Notifications_prowl extends Notifications_abs {
	private $_secret;
	var $prowlObj;

	function __construct($host, $username, $secret) {
		$this->prowlObj = new \Prowl\Connector();
		$this->_secret = $secret;
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($appName, $type, $title, $body, $sourceUrl) {
		$this->prowlObj = new \Prowl\Connector();
		$oMsg = new \Prowl\Message();
		$oMsg->addApiKey($this->_secret);
		$oMsg->setApplication($appName);
		$oMsg->setEvent($title);
		$oMsg->setDescription($body);

		$oFilter = new \Prowl\Security\PassthroughFilterImpl();
		$this->prowlObj->setFilter($oFilter);
		$this->prowlObj->setIsPostRequest(true);
		$oResponse = $this->prowlObj->push($oMsg);
	} # sendMessage

} # SpotsNotifications