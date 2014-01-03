<?php
require_once "lib/notifications/pushover/Pushover.php"; // https://github.com/kryap/php-pushover

class Notifications_Pushover extends Notifications_abs {
     private $_connection;
     var $pushoverObj;

     function __construct($appName, array $dataArray) {
          $this->pushoverObj = new Pushover();
          $this->_connection = array('appkey' => $dataArray['appkey'], 'userkey' => $dataArray['userkey']);
     } # ctor

     function register() {
          return;
     } # register

     function sendMessage($type, $title, $body, $sourceUrl) {
          $this->pushoverObj->setToken($this->_connection['appkey']);
          $this->pushoverObj->setUser($this->_connection['userkey']);
          $this->pushoverObj->setTitle($title);
          $this->pushoverObj->setUrl($sourceUrl);
          $this->pushoverObj->setMessage($body);
          $this->pushoverObj->send();
     } # sendMessage

} # Notifications_Pushover
