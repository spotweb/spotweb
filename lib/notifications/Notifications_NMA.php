<?php

class Notifications_NMA extends Notifications_abs
{
    private $_api;
    private $_appName;
    public $nmaObj;

    public function __construct($appName, array $dataArray)
    {
        $this->nmaObj = new NotifyMyAndroid();
        $this->_appName = $appName;
        $this->_api = $dataArray['api'];
    }

    // ctor

    public function register()
    {
    }

    // register

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        $params = ['apikey' => $this->_api,
            'priority'      => 0,
            'application'   => $this->_appName,
            'event'         => $title,
            'description'   => $body, ];
        $this->nmaObj->push($params);
    }

    // sendMessage
} // Notifications_NMA
