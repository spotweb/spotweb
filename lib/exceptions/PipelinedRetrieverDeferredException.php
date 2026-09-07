<?php

class PipelinedRetrieverDeferredException extends Exception
{
    private $_outcome;
    private $_stream;

    public function __construct($stream, Services_Nntp_PipelinedFetchOutcome $outcome)
    {
        parent::__construct('Pipelined '.$stream.' retrieval deferred unresolved ARTICLE tail to the next run', -1);
        $this->_stream = $stream;
        $this->_outcome = $outcome;
    }

    public function stream()
    {
        return $this->_stream;
    }

    public function outcome()
    {
        return $this->_outcome;
    }
}
