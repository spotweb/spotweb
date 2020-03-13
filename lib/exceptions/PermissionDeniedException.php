<?php

class PermissionDeniedException extends CustomException
{
    private $_permId = -1;
    private $_object = '';

    public function __construct($message = null, $code = 0)
    {
        $this->_permId = $message;
        $this->_object = $code;
        parent::__construct('Permission denied ['.$message.'] for objectid ['.$code.']', 5);
    }

    // ctor

    public function getPermId()
    {
        return $this->_permId;
    }

    // getPermId

    public function getObject()
    {
        return $this->_object;
    }

    // getObject
} // class
