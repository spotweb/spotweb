<?php
require_once "lib/notifications/nma/class.nma.php"; // https://github.com/uskr/NMAPHP

class Notifications_NMA extends Notifications_abs {
	private $_api;
	private $_appName;
	var $nmaObj;

	function __construct($appName, array $dataArray) {
		$this->nmaObj = new NotifyMyAndroid();
		$this->_appName = $appName;
		$this->_api = $dataArray['api'];
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($type, $title, $body, $sourceUrl) {
		$params = array('apikey' => $this->_api,
						'priority' => 0,
						'application' => $this->_appName,
						'event' => $title,
						'description' => $body);
		$response = $this->nmaObj->push($params);
	} # sendMessage

} # Notifications_NMA