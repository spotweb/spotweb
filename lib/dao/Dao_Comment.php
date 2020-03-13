<?php

interface Dao_Comment
{
    public function isCommentMessageIdUnique($messageid);

    public function removeExtraComments($messageId);

    public function addPostedComment($userId, $comment);

    public function matchCommentMessageIds($hdrList);

    public function addComments($comments, $fullComments = []);

    public function addFullComments($fullComments);

    public function getCommentsFull($userId, $nntpRefs);

    public function getNewCommentCountFor($nntpRefList, $ourUserId);

    public function markCommentsModerated($commentMsgIdList);

    public function removeComments($commentMsgIdList);

    public function expireCommentsFull($expireDays);
} // Dao_Comment
