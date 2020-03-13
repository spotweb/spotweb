<?php

define('GROWL_SOCK', 'fsock');

class Notifications_Growl extends Notifications_abs
{
    private $_connection;
    public $growlObj;

    public function __construct($appName, array $dataArray)
    {
        $this->growlObj = new Growl($appName);
        $this->_connection = ['address' => $dataArray['host'], 'password' => $dataArray['password']];
    }

    // ctor

    public function register()
    {
        $this->growlObj->addNotification('Single');
        $this->growlObj->addNotification('Multi');
        $this->growlObj->register($this->_connection);
    }

    // register

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        $this->growlObj->notify($this->_connection, $type, $title, $body);
    }

    // sendMessage
} // Notifications_Growl
