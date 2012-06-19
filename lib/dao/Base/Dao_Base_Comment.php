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
		# vraag eerst het id op
		$commentId = $this->_conn->singleQuery("SELECT id FROM commentsxover WHERE messageid = '%s'", Array($messageId));
		
		# als deze spot leeg is, is er iets raars aan de hand
		if (empty($commentId)) {
			throw new Exception("Our highest comment is not in the database!?");
		} # if

		# en wis nu alles wat 'jonger' is dan deze spot
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
		# We negeren commentsfull hier een beetje express, als die een 
		# keer ontbreken dan fixen we dat later wel.
		$idList = array('comment' => array(), 'fullcomment' => array());

		# geen message id's gegeven? vraag het niet eens aan de db
		if (count($hdrList) == 0) {
			return $idList;
		} # if

		# Prepare the list of messageid's we want to match
		$msgIdList = $this->_conn->arrayValToInOffset($hdrList, 'Message-ID', 1, -1);

		# Omdat MySQL geen full joins kent, doen we het zo
		$rs = $this->_conn->arrayQuery("SELECT messageid AS comment, '' AS fullcomment FROM commentsxover WHERE messageid IN (" . $msgIdList . ")
											UNION
					 				    SELECT '' as comment, messageid AS fullcomment FROM commentsfull WHERE messageid IN (" . $msgIdList . ")");

		# en lossen we het hier op
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
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addComments($comments, $fullComments = array()) {
		$this->_conn->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		$chunks = array_chunk($comments, 100);

		foreach($chunks as $comments) {
			$insertArray = array();

			foreach($comments as $comment) {
				$insertArray[] = vsprintf("('%s', '%s', %d, %d)",
						 Array($this->_conn->safe($comment['messageid']),
							   $this->_conn->safe($comment['nntpref']),
							   $this->_conn->safe($comment['rating']),
							   $this->_conn->safe($comment['stamp'])));
			} # foreach

			# Actually insert the batch
			if (!empty($insertArray)) {
				$this->_conn->modify("INSERT INTO commentsxover(messageid, nntpref, spotrating, stamp)
									  VALUES " . implode(',', $insertArray), array());
			} # if
		} # foreach
		$this->_conn->commit();

		if (!empty($fullComments)) {
			$this->addFullComments($fullComments);
		} # if
	} # addComments

	/*
	 * Insert commentfull, assumes there is already an entry in commentsxover
	 */
	function addFullComments($fullComments) {
		$this->_conn->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		$chunks = array_chunk($fullComments, 100);

		foreach($chunks as $fullComments) {
			$insertArray = array();

			foreach($fullComments as $comment) {
				# Kap de verschillende strings af op een maximum van 
				# de datastructuur, de unique keys kappen we expres niet af
				$comment['fromhdr'] = substr($comment['fromhdr'], 0, 127);

				$insertArray[] = vsprintf("('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s')",
						Array($this->_conn->safe($comment['messageid']),
							  $this->_conn->safe($comment['fromhdr']),
							  $this->_conn->safe($comment['stamp']),
							  $this->_conn->safe($comment['user-signature']),
							  $this->_conn->safe(serialize($comment['user-key'])),
							  $this->_conn->safe($comment['spotterid']),
							  $this->_conn->safe(implode("\r\n", $comment['body'])),
							  $this->_conn->bool2dt($comment['verified']),
							  $this->_conn->safe($comment['user-avatar'])));
			} # foreach

			# Actually insert the batch
			$this->_conn->modify("INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, spotterid, body, verified, avatar)
								  VALUES " . implode(',', $insertArray), array());
		} # foreach

		$this->_conn->commit();
	} # addFullComments

	/*
	 * Retrieves the full comments 
	 */
	function getCommentsFull($userId, $nntpRef) {
		SpotTiming::start(__FUNCTION__);

		# en vraag de comments daadwerkelijk op
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

		# bereid de lijst voor met de queries in de where
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
