<?php

interface Dao_Comment {

	function isCommentMessageIdUnique($messageid);
	function removeExtraComments($messageId);
	function addPostedComment($userId, $comment);
	function matchCommentMessageIds($hdrList);
	function addComments($comments, $fullComments = array());	
	function addFullComments($fullComments);
	function getCommentsFull($userId, $nntpRef);
	function getNewCommentCountFor($nntpRefList, $ourUserId);
	function markCommentsModerated($commentMsgIdList);
	function removeComments($commentMsgIdList);
	function expireCommentsFull($expireDays);

} # Dao_Comment
