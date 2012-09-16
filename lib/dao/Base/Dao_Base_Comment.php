<?php

class Dao_Base_Comment implements Dao_Comment {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Comment object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor

	/* 
	 * Makes sure an messageid is not already used for a posting of a comment
	 */
	function isCommentMessageIdUnique($messageid) {
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM commentsposted WHERE messageid = '%s'",
						Array($messageid));
		
		return (empty($tmpResult));
	} # isCommentMessageIdUnique
	
	/*
	 * Remove extra comments
	 */
	function removeExtraComments($messageId) {
		# Retrieve the database id for the messageid given
		$commentId = $this->_conn->singleQuery("SELECT id FROM commentsxover WHERE messageid = '%s'", Array($messageId));
		
		/* 
		* If this spot doesn't exist, we have some kind of logical error
		* or database corruption. Cry about it
		*/
		if (empty($commentId)) {
			throw new Exception("Our highest comment is not in the database!?");
		} # if

		/* 
		 * remove all spots later inserted than the last spot we have
		 * retrieved from the usenet server.
		 */
		$this->_conn->modify("DELETE FROM commentsxover WHERE id > %d", Array($commentId));
	} # removeExtraComments

	/*
	 * Saves the posted comment of the user to the database
	 */
	function addPostedComment($userId, $comment) {
		$this->_conn->modify(
				"INSERT INTO commentsposted(ouruserid, messageid, inreplyto, randompart, rating, body, stamp)
					VALUES('%d', '%s', '%s', '%s', '%d', '%s', %d)", 
				Array((int) $userId,
					  $comment['newmessageid'],
					  $comment['inreplyto'],
					  $comment['randomstr'],
					  (int) $comment['rating'],
					  $comment['body'],
					  (int) time()));
	} # addPostedComment
 
 	/*
	 * Match set of comments
	 */
	function matchCommentMessageIds($hdrList) {
		/* 
		 * Ignore commentsfull on purpose. If one is missing, we
		 * will retrieve it later when a spot is actually opened
		 */
		$idList = array('comment' => array(), 'fullcomment' => array());

		/* 
		 * When no messageid's are given, bail out immediatly
		 */
		if (count($hdrList) == 0) {
			return $idList;
		} # if

		/*
		 * Prepare the list of messageid's we want to match
		 */
		$msgIdList = $this->_conn->arrayValToInOffset($hdrList, 'Message-ID', 1, -1);
		$rs = $this->_conn->arrayQuery("SELECT messageid AS comment, '' AS fullcomment FROM commentsxover WHERE messageid IN (" . $msgIdList . ")
											UNION
					 				    SELECT '' as comment, messageid AS fullcomment FROM commentsfull WHERE messageid IN (" . $msgIdList . ")");

		/*
		 * split out the query in either a full comment or a comment,
		 * for simple and fast matching in callers of this code
		 */
		foreach($rs as $msgids) {
			if (!empty($msgids['comment'])) {
				$idList['comment'][$msgids['comment']] = 1;
			} # if

			if (!empty($msgids['fullcomment'])) {
				$idList['fullcomment'][$msgids['fullcomment']] = 1;
			} # if
		} # foreach

		return $idList;
	} # matchCommentMessageIds

	/*
	 * Insert commentref, 
	 *   messageid is the actual messageid of the comment
	 *   nntpref is the messageid of the spot this comment belongs to
	 */
	function addComments($comments, $fullComments = array()) {
		$this->_conn->batchInsert($comments,
								  "INSERT INTO commentsxover(messageid, nntpref, spotrating, stamp) VALUES ",
								  "('%s', '%s', %d, %d)",
								  Array('messageid', 'nntpref', 'rating', 'stamp')
								  );

		if (!empty($fullComments)) {
			$this->addFullComments($fullComments);
		} # if
	} # addComments

	/*
	 * Insert commentfull, assumes there is already an entry in commentsxover
	 */
	function addFullComments($fullComments) {
		/* 
		 * We process the fullcomments array to make sure
		 * its in the proper format for inserting into the
		 * database
		 */
		foreach($fullComments as &$comment) {
			/*
			 * Cut off the from header so we don't overflow the database field,
			 * and prepare other fields for database storage
			 */
			$comment['fromhdr'] = substr($comment['fromhdr'], 0, 127);
			$comment['user-key'] = serialize($comment['user-key']);
			$comment['body'] = substr(implode("\r\n", $comment['body']), 0, 65534);
			$comment['verified'] = $this->_conn->bool2dt($comment['verified']);
		} # foreach

		$this->_conn->batchInsert($fullComments,
								  "INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, spotterid, body, verified, avatar) VALUES ",
								  "('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s')",
								  Array('messageid', 'fromhdr', 'stamp', 'user-signature', 'user-key', 'spotterid', 'body', 'verified', 'user-avatar')
								  );
	} # addFullComments

	/*
	 * Retrieves the full comments 
	 */
	function getCommentsFull($userId, $nntpRef) {
		SpotTiming::start(__FUNCTION__);

		# eactually retrieve the comment
		$commentList = $this->_conn->arrayQuery("SELECT c.messageid AS messageid, 
														(f.messageid IS NOT NULL) AS havefull,
														f.fromhdr AS fromhdr, 
														f.stamp AS stamp, 
														f.usersignature AS \"user-signature\", 
														f.userkey AS \"user-key\", 
														f.spotterid AS spotterid, 
														f.body AS body, 
														f.verified AS verified,
														c.spotrating AS spotrating,
														c.moderated AS moderated,
														f.avatar as \"user-avatar\",
														bl.idtype AS idtype
													FROM commentsfull f 
													RIGHT JOIN commentsxover c on (f.messageid = c.messageid)
													LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = f.spotterid) AND (bl.doubled = '%s'))
													WHERE c.nntpref = '%s' AND ((bl.spotterid IS NULL) OR (((bl.ouruserid = " . $this->_conn->safe( (int) $userId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 2)))
													ORDER BY c.id", array($this->_conn->bool2dt(false), $nntpRef));
		$commentListCount = count($commentList);
		for($i = 0; $i < $commentListCount; $i++) {
			if ($commentList[$i]['havefull']) {
				$commentList[$i]['user-key'] = unserialize($commentList[$i]['user-key']);
				$commentList[$i]['body'] = explode("\r\n", $commentList[$i]['body']);
			} # if
		} # for

		SpotTiming::stop(__FUNCTION__);
		return $commentList;
	} # getCommentsFull

	/*
	 * Returns the amount of new comments since 'stamp' for all 
	 * comments belonging to spot 'nntpRefList'
	 */
	function getNewCommentCountFor($nntpRefList, $ourUserId) {
		if (count($nntpRefList) == 0) {
			return array();
		} # if

		/*
		 * Actually run the query
		 */
		$tmp = $this->_conn->arrayQuery("SELECT COUNT(nntpref) AS ccount, nntpref FROM commentsxover AS cx
									LEFT JOIN spotstatelist sl ON (sl.messageid = cx.nntpref) 
												AND (sl.ouruserid = %d)
									WHERE nntpref IN (" . $this->_conn->arrayKeyToIn($nntpRefList) . ") 
 										  AND (cx.stamp > sl.seen) 
								   GROUP BY nntpref",
								   Array((int) $ourUserId));
		$commentCount = array();
		foreach($tmp as $cCount) {
			$commentCount[$cCount['nntpref']] = $cCount['ccount'];
		} # foreach
		
		return $commentCount;
	} # getNewCommentCountFor

	/*
	 * Removes a comment from the database
	 */
	function removeComments($commentMsgIdList) {
		if (count($commentMsgIdList) == 0) {
			return;
		} # if

		$msgIdList = $this->_conn->arrayKeyToIn($commentMsgIdList);

		$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM commentsxover WHERE messageid IN (" . $msgIdList . ")");
	} # removeComments

	/*
	 * Markeer een comment in de db moderated
	 */
	function markCommentsModerated($commentMsgIdList) {
		if (count($commentMsgIdList) == 0) {
			return;
		} # if

		$this->_conn->modify("UPDATE commentsxover SET moderated = '%s' WHERE messageid IN (" . $this->_conn->arrayKeyToIn($commentMsgIdList) . ")", Array($this->_conn->bool2dt(true)));
	} # markCommentsModerated

	/*
	 * Removes items from te commentsfull table older than a specific amount of days
	 */
	function expireCommentsFull($expireDays) {
		return $this->_conn->modify("DELETE FROM commentsfull WHERE stamp < %d", Array((int) time() - ($expireDays*24*60*60)));
	} # expireCommentsFull


} # Dao_Base_Comment
