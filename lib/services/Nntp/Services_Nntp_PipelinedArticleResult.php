<?php

/*
 * Spotweb adapter for the private rvdv/nntp-based comments pipeline.
 *
 * Upstream reference: https://github.com/robinvdvleuten/php-nntp
 * Copyright: Robin van der Vleuten <robin@webstronauts.com>
 * Licence: MIT, retained in lib/thirdparty/rvdv-nntp/LICENSE.
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
