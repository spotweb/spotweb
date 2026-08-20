<?php

require_once __DIR__.'/PipelinedRetrieverDeferredException.php';

class PipelinedCommentsDeferredException extends PipelinedRetrieverDeferredException
{
    public function __construct(Services_Nntp_PipelinedFetchOutcome $outcome)
    {
        parent::__construct('comments', $outcome);
    }
}
