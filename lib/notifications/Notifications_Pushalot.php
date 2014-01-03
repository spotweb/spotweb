<?php
require_once "lib/notifications/pushalot/Pushalot.php"; // https://github.com/sibbl/Pushalot-PHP

class Notifications_Pushalot extends Notifications_abs {
     var $pushalotObj;
     var $appName;

     function __construct($appName, array $dataArray) {
          $this->appName     = $appName;
          $this->pushalotObj = new Pushalot($dataArray['auth_token']);
     } # ctor

     function register() {
          return;
     } # register

     function sendMessage($type, $title, $body, $sourceUrl) {
          $params = array(
             'Title'=>$title,
             'Body'=>$body
          );
          
          if (!empty($sourceUrl)) $parmas['Link'] = $sourceUrl;
          
          $this->pushalotObj->sendMessage($params);
     } # sendMessage

} # Notifications_Pushalot
