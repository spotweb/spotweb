<?php

class NntpException extends Exception
{
    private $_response = '';
    private $_detail;
    private $_code;
    public function __construct($detail = null, $code = 0, $response = '')
    {
        $this->_detail = $detail;
        $this->_code = $code;
        $this->_response = $response;

        parent::__construct($detail.' [response: "'.$response.'"]', $code);
    }

    // ctor
} // NntpException
