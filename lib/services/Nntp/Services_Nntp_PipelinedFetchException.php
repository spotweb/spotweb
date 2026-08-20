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

    public static function classify(Exception $exception, $context = 'transport')
    {
        if ($exception instanceof self) {
            return $exception->errorClass();
        }

        $message = strtolower($exception->getMessage());

        if (strpos($message, 'timed out') !== false) {
            return $context.'.timeout';
        }
        if ((strpos($message, 'connection closed') !== false) || (strpos($message, 'eof') !== false)) {
            return $context.'.disconnect';
        }
        if ((strpos($message, 'write failed') !== false) || (strpos($message, 'while writing') !== false)) {
            return $context.'.write';
        }
        if ((strpos($message, 'read failed') !== false) || (strpos($message, 'while reading') !== false)) {
            return $context.'.read';
        }
        if (strpos($message, 'stream_select failed') !== false) {
            return $context.'.select';
        }
        if ((strpos($message, 'unexpected article response') !== false) ||
                (strpos($message, 'without a pending request') !== false) ||
                (strpos($message, 'unexpected nntp response') !== false)) {
            return $context.'.protocol';
        }
        if ((strpos($message, 'connecting to server') !== false) ||
                (strpos($message, 'servername is empty') !== false) ||
                (strpos($message, 'portnumber') !== false)) {
            return $context.'.connect';
        }
        if ((strpos($message, 'starttls') !== false) || (strpos($message, 'crypto') !== false)) {
            return $context.'.tls';
        }
        if (strpos($message, 'auth') !== false) {
            return $context.'.auth';
        }

        return $context.'.other';
    }
}
