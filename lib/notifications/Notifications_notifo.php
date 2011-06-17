<?php
require_once "lib/notifications/notifo/Notifo_API.php";

class Notifications_notifo extends Notifications_abs {
	var $notifoObj;

	function __construct($host, $username, $secret) {
		$this->notifoObj = new Notifo_API($username, $secret);
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($appName, $type, $title, $body, $sourceUrl) {
		$params = array('label' => $appName,
						'title' => $title,
						'msg' => $body,
						'uri' => $sourceUrl);
		$response = $this->notifoObj->send_notification($params);
	} # sendMessage

} # SpotsNotifications