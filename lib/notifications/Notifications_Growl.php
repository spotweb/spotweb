<?php
require_once "lib/notifications/growl/class.growl.php"; // https://github.com/tylerhall/php-growl

class Notifications_Growl extends Notifications_abs {
	var $growlObj;

	function __construct($appName, array $dataArray) {
		$this->growlObj = new Growl($dataArray['host'], $dataArray['password'], $appName);
	} # ctor

	function register() {
		$this->growlObj->addNotification('Single');
		$this->growlObj->addNotification('Multi');
		$this->growlObj->register();
	} # register

	function sendMessage($type, $title, $body, $sourceUrl) {
		$this->growlObj->notify($type, $title, $body);
	} # sendMessage

} # Notifications_Growl
