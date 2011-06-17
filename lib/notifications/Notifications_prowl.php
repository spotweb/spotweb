<?php
/*
 * We includen de libraries hier en in deze volgorde om te voorkomen
 * dat de autoclass loader triggered, die snapt namelijk op dit moment
 * nog niets van namespaces en dan gaat het mis 
 */
require_once "lib/notifications/prowl/Connector.php";
require_once "lib/notifications/prowl/Message.php";
require_once "lib/notifications/prowl/Security/Secureable.php";
require_once "lib/notifications/prowl/Response.php";
require_once "lib/notifications/prowl/Security/PassthroughFilterImpl.php";

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