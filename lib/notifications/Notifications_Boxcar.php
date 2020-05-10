<?php

class Notifications_Boxcar extends Notifications_abs
{
    private $_dataArray;
    private $_appName;
    public $boxcarObj;

    public function __construct($appName, array $dataArray)
    {
        $this->boxcarObj = new boxcar_api($dataArray['api_key'], $dataArray['api_secret']);
        $this->_appName = $appName;
        $this->_dataArray = $dataArray;
    }

    // ctor

    public function register()
    {
        $this->boxcarObj->invite($this->_dataArray['email']);
    }

    // register

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        $this->boxcarObj->notify($this->_dataArray['email'], $this->_appName, $body, null, null, $sourceUrl);
    }

    // sendMessage
} // Notifications_Boxcar
