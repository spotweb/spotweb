<?php
require_once "lib/notifications/notifo/Notifo_API.php";

class Notifications_prowl extends Notifications_abs {
	var $notifoObj;

	function __construct($host, $username, $secret) {
		$this->notifoObj = new Notifo_API($username, $secret);
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($type, $title, $body) {
		$params = array('label' => 'Spotweb',
						'title' => $title,
						'msg' => $body,
						'uri' => $this->_settings->get('spotweburl'));
		$response = $this->notifoObj->send_notification($params);
	} # sendMessage

} # SpotsNotifications