<?php
class Notifications_Prowl extends Notifications_abs {
	private $_apikey;
	private $_appName;
	var $prowlObj;

	function __construct($appName, array $dataArray) {
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
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

			$this->prowlObj = new \Prowl\Connector();
		} # if

		$this->_appName = $appName;
		$this->_apikey = $dataArray['apikey'];
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($type, $title, $body, $sourceUrl) {
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			$oMsg = new \Prowl\Message();
			$oMsg->addApiKey($this->_apikey);
			$oMsg->setApplication($this->_appName);
			$oMsg->setEvent($title);
			$oMsg->setDescription($body);

			$oFilter = new \Prowl\Security\PassthroughFilterImpl();
			$this->prowlObj->setFilter($oFilter);
			$this->prowlObj->setIsPostRequest(true);
			$oResponse = $this->prowlObj->push($oMsg);
		} # if
	} # sendMessage

} # Notifications_Prowl
