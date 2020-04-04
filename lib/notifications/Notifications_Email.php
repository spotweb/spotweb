<?php

class Notifications_Email extends Notifications_abs
{
    private $_dataArray;
    private $_appName;

    public function __construct($appName, array $dataArray)
    {
        $this->_appName = $appName;
        $this->_dataArray = $dataArray;
    }

    // ctor

    public function register()
    {
    }

    // register

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        $header = 'From: '.$this->_appName.' <'.$this->_dataArray['sender'].">\r\n";
        $body = wordwrap($body, 70);
        mail($this->_dataArray['receiver'], $title, $body, $header);
    }

    // sendMessage
} // Notifications_Email
