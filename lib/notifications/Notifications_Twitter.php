<?php

use Abraham\TwitterOAuth\TwitterOAuth;

class Notifications_Twitter extends Notifications_abs
{
    private $dataArray;
    private $_appName;
    public $twitterObj;

    public function __construct($appName, array $dataArray)
    {
        $this->_appName = $appName;
        $this->dataArray = $dataArray;
    }

    // ctor

    public function register()
    {
    }

    // register

    public function requestAuthorizeURL()
    {
        $this->twitterObj = new TwitterOAuth($this->dataArray['consumer_key'], $this->dataArray['consumer_secret']);
        $request_token = $this->twitterObj->getRequestToken();

        switch ($this->twitterObj->http_code) {
            case 200: $registerURL = $this->twitterObj->getAuthorizeURL($request_token); break;
            default: $registerURL = '';
        } // switch

        return [$this->twitterObj->http_code, $request_token, $registerURL];
    }

    // requestAuthorizeURL

    public function verifyPIN($pin)
    {
        $this->twitterObj = new TwitterOAuth($this->dataArray['consumer_key'], $this->dataArray['consumer_secret'], $this->dataArray['request_token'], $this->dataArray['request_token_secret']);
        $access_token = $this->twitterObj->getAccessToken($pin);

        switch ($this->twitterObj->http_code) {
            case 200: break;
            default: $access_token = [];
        } // switch

        return [$this->twitterObj->http_code, $access_token];
    }

    // verifyPIN

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        $this->twitterObj = new TwitterOAuth($this->dataArray['consumer_key'], $this->dataArray['consumer_secret'], $this->dataArray['access_token'], $this->dataArray['access_token_secret']);

        $message = [];
        $message['status'] = substr($this->_appName.': '.$body, 0, 140);
        if (empty($sourceUrl)) {
            $this->twitterObj->post('statuses/update', ['status' => $message]);
        } else {
            $annotations = [['webpage' => ['title' => $this->_appName, 'url' => $sourceUrl]]];
            $this->twitterObj->post('statuses/update', ['status' => $message, 'annotations' => json_encode($annotations)]);
        } // if
    }

    // sendMessage
} // Notifications_Twitter
