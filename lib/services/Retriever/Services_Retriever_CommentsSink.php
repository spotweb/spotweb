<?php

interface Services_Retriever_CommentsSink
{
    public function matchCommentMessageIds($hdrList);

    public function recordArticleStatus($messageId, $code, $message);

    public function commitBatch(array $comments, array $fullComments, array $spotMsgIdList, array $spotMsgIdRatingList, $lastProcessedArtNr, $lastProcessedId);
}
