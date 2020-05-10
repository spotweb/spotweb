<?php

class Notifications_Prowl extends Notifications_abs
{
    private $_apikey;
    private $_appName;
    public $prowlObj;

    public function __construct($appName, array $dataArray)
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $this->prowlObj = new \Prowl\Connector();
        } // if

        $this->_appName = $appName;
        $this->_apikey = $dataArray['apikey'];
    }

    // ctor

    public function register()
    {
    }

    // register

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $oMsg = new \Prowl\Message();
            $oMsg->addApiKey($this->_apikey);
            $oMsg->setApplication($this->_appName);
            $oMsg->setEvent($title);
            $oMsg->setDescription($body);

            $oFilter = new \Prowl\Security\PassthroughFilterImpl();
            $this->prowlObj->setFilter($oFilter);
            $this->prowlObj->setIsPostRequest(true);
            $this->prowlObj->push($oMsg);
        } // if
    }

    // sendMessage
} // Notifications_Prowl
