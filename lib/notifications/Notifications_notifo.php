<?php
require_once "lib/notifications/notifo/Notifo_API.php";

class Notifications_prowl extends SpotNotifications {
	var $notifoObj;

	function __construct($username, $apikey) {
		$this->notifoObj = new Notifo_API($username, $apikey);
	} # ctor

	function sendMessage($type, $title, $body) {
		$params = array('label' => 'Spotweb',
						'title' => $title,
						'msg' => $body,
						'uri' => $this->_settings->get('spotweburl'));
		$response = $this->notifoObj->send_notification($params);
	} # sendMessage

} # SpotsNotifications