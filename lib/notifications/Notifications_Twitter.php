<?php
require_once "lib/notifications/twitter/twitteroauth.php";

class Notifications_Twitter extends Notifications_abs {
	private $dataArray;
	private $_appName;
	var $twitterObj;

	function __construct($appName, array $dataArray) {
		$this->_appName = $appName;
		$this->dataArray = $dataArray;
	} # ctor

	function register() {
		return;
	} # register

	function requestAuthorizeURL() {
		$this->twitterObj = new TwitterOAuth($this->dataArray['consumer_key'], $this->dataArray['consumer_secret']);
		$request_token = $this->twitterObj->getRequestToken();

		switch ($this->twitterObj->http_code) {
			case 200	: $registerURL = $this->twitterObj->getAuthorizeURL($request_token); break;
			default		: $registerURL = '';
		} # switch

		return array($this->twitterObj->http_code, $request_token, $registerURL);
	} # requestAuthorizeURL

	function verifyPIN($pin) {
		$this->twitterObj = new TwitterOAuth($this->dataArray['consumer_key'], $this->dataArray['consumer_secret'], $this->dataArray['request_token'], $this->dataArray['request_token_secret']);
		$access_token = $this->twitterObj->getAccessToken($pin);

		switch ($this->twitterObj->http_code) {
			case 200	: break;
			default		: $access_token = array();
		} # switch

		return array($this->twitterObj->http_code, $access_token);
	} # verifyPIN

	function sendMessage($type, $title, $body, $sourceUrl) {
		$this->twitterObj = new TwitterOAuth($this->dataArray['consumer_key'], $this->dataArray['consumer_secret'], $this->dataArray['access_token'], $this->dataArray['access_token_secret']);

		$message = array();
		$message['status'] = substr($this->_appName . ': ' . $body, 0, 140);
		if (empty($sourceUrl)) {
			$this->twitterObj->post('statuses/update', array('status' => $message));
		} else {
			$annotations = array(array('webpage' => array('title' => $this->_appName, 'url' => $sourceUrl)));
			$this->twitterObj->post('statuses/update', array('status' => $message, 'annotations' => json_encode($annotations)));
		} # if
	} # sendMessage

} # Notifications_Twitter
