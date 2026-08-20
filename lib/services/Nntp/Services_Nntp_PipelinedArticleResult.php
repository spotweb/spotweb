<?php

/*
 * Internal Spotweb NNTP pipeline transport.
 *
 * This file contains original Spotweb transport/result code. It does not copy
 * source from external NNTP packages.
 */

class Services_Nntp_PipelinedArticleResult
{
    public $messageId;
    public $code;
    public $message;
    public $header = [];
    public $body = [];

    public function __construct($messageId, $code, $message, array $header = [], array $body = [])
    {
        $this->messageId = $messageId;
        $this->code = (int) $code;
        $this->message = $message;
        $this->header = $header;
        $this->body = $body;
    }

    public function found()
    {
        return $this->code === 220;
    }

    public function article()
    {
        return [
            'header' => $this->header,
            'body'   => $this->body,
        ];
    }
}
