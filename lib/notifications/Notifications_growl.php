<?php
require_once "lib/notifications/growl/class.growl.php";

class Notifications_growl extends Notifications_abs {
	var $growlObj;

	function __construct($host, $username, $secret) {
		$this->growlObj = new Growl($host, $secret, 'Spotweb');
	} # ctor

	function register() {
		$this->growlObj->addNotification('Single');
		$this->growlObj->addNotification('Multi');
		$this->growlObj->register();
	} # register

	function sendMessage($type, $title, $body) {
		$this->growlObj->notify($type, $title, $body);
	} # sendMessage

} # SpotsNotifications