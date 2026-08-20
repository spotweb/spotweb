<?php

class Services_Retriever_CommentsCaptureSink implements Services_Retriever_CommentsSink
{
    private $_existingComments = [];
    private $_existingFullComments = [];
    private $_batches = [];
    private $_articleStatuses = [];

    public function __construct(array $existingComments = [], array $existingFullComments = [])
    {
        foreach ($existingComments as $messageId) {
            $this->_existingComments[$messageId] = 1;
        }
        foreach ($existingFullComments as $messageId) {
            $this->_existingFullComments[$messageId] = 1;
        }
    }

    public function matchCommentMessageIds($hdrList)
    {
        $idList = ['comment' => [], 'fullcomment' => []];
        foreach ($hdrList as $header) {
            $messageId = $header['Message-ID'];
            if (isset($this->_existingComments[$messageId])) {
                $idList['comment'][$messageId] = 1;
            }
            if (isset($this->_existingFullComments[$messageId])) {
                $idList['fullcomment'][$messageId] = 1;
            }
        }

        return $idList;
    }

    public function recordArticleStatus($messageId, $code, $message)
    {
        $this->_articleStatuses[] = [
            'messageid' => $messageId,
            'code'      => (int) $code,
            'message'   => $message,
        ];
    }

    public function commitBatch(array $comments, array $fullComments, array $spotMsgIdList, array $spotMsgIdRatingList, $lastProcessedArtNr, $lastProcessedId)
    {
        foreach ($comments as $comment) {
            $this->_existingComments[$comment['messageid']] = 1;
        }
        foreach ($fullComments as $comment) {
            $this->_existingFullComments[$comment['messageid']] = 1;
        }

        $this->_batches[] = [
            'comments'       => $comments,
            'fullcomments'   => $fullComments,
            'spotrefs'       => array_keys($spotMsgIdList),
            'ratingspotrefs' => array_keys($spotMsgIdRatingList),
            'cursor'         => [
                'articlenr' => (int) $lastProcessedArtNr,
                'messageid' => $lastProcessedId,
            ],
        ];
    }

    public function getCanonicalResult()
    {
        return [
            'article_statuses' => $this->_articleStatuses,
            'batches'          => $this->_batches,
        ];
    }
}
