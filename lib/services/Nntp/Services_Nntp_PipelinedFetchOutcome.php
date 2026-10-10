<?php

/*
 * Internal Spotweb NNTP pipeline transport.
 *
 * This file contains original Spotweb transport/recovery bookkeeping code. It
 * does not copy source from external NNTP packages.
 */

class Services_Nntp_PipelinedFetchOutcome
{
    private $_terminalResults = [];
    private $_unresolvedMessageIds = [];
    private $_errors = [];
    private $_retryCount = 0;
    private $_connectionsOpened = 0;

    public function addTerminalResults(array $results)
    {
        foreach ($results as $result) {
            $this->_terminalResults[] = $result;
        }
    }

    public function setUnresolvedMessageIds(array $messageIds)
    {
        $this->_unresolvedMessageIds = array_values($messageIds);
    }

    public function addError(Exception $exception, $window, $attempt)
    {
        $this->_errors[] = [
            'class'   => ($exception instanceof Services_Nntp_PipelinedFetchException) ? $exception->errorClass() : get_class($exception),
            'code'    => (int) $exception->getCode(),
            'message' => $exception->getMessage(),
            'window'  => (int) $window,
            'attempt' => (int) $attempt,
        ];
    }

    public function incrementRetryCount()
    {
        $this->_retryCount++;
    }

    public function setConnectionsOpened($connectionsOpened)
    {
        $this->_connectionsOpened = (int) $connectionsOpened;
    }

    public function terminalResults()
    {
        return $this->_terminalResults;
    }

    public function unresolvedMessageIds()
    {
        return $this->_unresolvedMessageIds;
    }

    public function errors()
    {
        return $this->_errors;
    }

    public function retryCount()
    {
        return $this->_retryCount;
    }

    public function connectionsOpened()
    {
        return $this->_connectionsOpened;
    }

    public function terminalCount()
    {
        return count($this->_terminalResults);
    }

    public function unresolvedCount()
    {
        return count($this->_unresolvedMessageIds);
    }

    public function hasUnresolved()
    {
        return !empty($this->_unresolvedMessageIds);
    }

    public function toArray()
    {
        return [
            'terminal_count'     => $this->terminalCount(),
            'unresolved_count'   => $this->unresolvedCount(),
            'unresolved_ids'     => $this->_unresolvedMessageIds,
            'retry_count'        => $this->_retryCount,
            'connections_opened' => $this->_connectionsOpened,
            'errors'             => $this->_errors,
        ];
    }
}
