<?php

/*
 * Internal Spotweb NNTP pipeline transport.
 *
 * This file contains original Spotweb transport/error handling code. It does
 * not copy source from external NNTP packages.
 */

class Services_Nntp_PipelinedFetchException extends NntpException
{
    private $_terminalResults;
    private $_unresolvedMessageIds;
    private $_errorClass;

    public function __construct($message, $code, array $terminalResults, array $unresolvedMessageIds, $errorClass = 'transport')
    {
        parent::__construct($message, $code);
        $this->_terminalResults = $terminalResults;
        $this->_unresolvedMessageIds = $unresolvedMessageIds;
        $this->_errorClass = $errorClass;
    }

    public function terminalResults()
    {
        return $this->_terminalResults;
    }

    public function unresolvedMessageIds()
    {
        return $this->_unresolvedMessageIds;
    }

    public function errorClass()
    {
        return $this->_errorClass;
    }
}
