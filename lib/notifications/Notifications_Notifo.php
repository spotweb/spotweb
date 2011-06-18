<?php
require_once "lib/notifications/notifo/Notifo_API.php";

class Notifications_Notifo extends Notifications_abs {
	private $_appName;
	var $notifoObj;

	function __construct($appName, array $dataArray) {
		$this->notifoObj = new Notifo_API($dataArray['username'], $dataArray['api']);
		$this->_appName = $appName;
	} # ctor

	function register() {
		return;
	} # register

	function sendMessage($type, $title, $body, $sourceUrl) {
		$params = array('label' => $this->_appName,
						'title' => $title,
						'msg' => $body,
						'uri' => $sourceUrl);
		$response = $this->notifoObj->send_notification($params);
	} # sendMessage

} # Notifications_Notifo
