<?php

class Dao_Base_Comment implements Dao_Comment
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Comment object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Makes sure an messageid is not already used for a posting of a comment
     */
    public function isCommentMessageIdUnique($messageId)
    {
        $tmpResult = $this->_conn->singleQuery(
            'SELECT messageid FROM commentsposted WHERE messageid = :messageid',
            [
                ':messageid' => [$messageId, PDO::PARAM_STR],
            ]
        );

        return empty($tmpResult);
    }

    // isCommentMessageIdUnique

    /*
     * Remove extra comments
     */
    public function removeExtraComments($messageId)
    {
        $commentId = $this->_conn->singleQuery(
            'SELECT id FROM commentsxover WHERE messageid = :messageid',
            [
                ':messageid' => [$messageId, PDO::PARAM_STR],
            ]
        );

        /*
        * If this comment doesn't exist, we have some kind of logical error
        * or database corruption. Cry about it
        */
        if (empty($commentId)) {
            throw new Exception('Our highest comment is not in the database!?');
        } // if

        /*
         * remove all spots later inserted than the last spot we have
         * retrieved from the usenet server.
         */
        $this->_conn->modify(
            'DELETE FROM commentsxover WHERE id > :id',
            [
                ':id' => [$commentId, PDO::PARAM_INT],
            ]
        );
    }

    // removeExtraComments

    /*
     * Saves the posted comment of the user to the database
     */
    public function addPostedComment($userId, $comment)
    {
        $this->_conn->modify(
            'INSERT INTO commentsposted(ouruserid, messageid, inreplyto, randompart, rating, body, stamp)
					VALUES(:ouruserid, :newmessageid, :inreplyto, :randompart, :rating, :body, :stamp)',
            [
                ':ouruserid'    => [$userId, PDO::PARAM_INT],
                ':newmessageid' => [$comment['newmessageid'], PDO::PARAM_STR],
                ':inreplyto'    => [$comment['inreplyto'], PDO::PARAM_STR],
                ':randompart'   => [$comment['randomstr'], PDO::PARAM_STR],
                ':rating'       => [$comment['rating'], PDO::PARAM_INT],
                ':body'         => [$comment['body'], PDO::PARAM_STR],
                ':stamp'        => [time(), PDO::PARAM_INT],
            ]
        );
    }

    // addPostedComment

    /*
     * Match set of comments
     */
    public function matchCommentMessageIds($hdrList)
    {
        /*
         * Ignore commentsfull on purpose. If one is missing, we
         * will retrieve it later when a spot is actually opened
         */
        $idList = ['comment' => [], 'fullcomment' => []];

        /*
         * When no messageid's are given, bail out immediatly
         */
        if (count($hdrList) == 0) {
            return $idList;
        } // if

        /*
         * Prepare the list of messageid's we want to match
         */
        $msgIdList = $this->_conn->arrayValToIn($hdrList, 'Message-ID');
        $rs = $this->_conn->arrayQuery("SELECT messageid AS comment, '' AS fullcomment FROM commentsxover WHERE messageid IN (".$msgIdList.")
											UNION
					 				    SELECT '' as comment, messageid AS fullcomment FROM commentsfull WHERE messageid IN (".$msgIdList.')');

        /*
         * split out the query in either a full comment or a comment,
         * for simple and fast matching in callers of this code
         */
        foreach ($rs as $msgids) {
            if (!empty($msgids['comment'])) {
                $idList['comment'][$msgids['comment']] = 1;
            } // if

            if (!empty($msgids['fullcomment'])) {
                $idList['fullcomment'][$msgids['fullcomment']] = 1;
            } // if
        } // foreach

        return $idList;
    }

    // matchCommentMessageIds

    /*
     * Insert commentref,
     *   messageid is the actual messageid of the comment
     *   nntpref is the messageid of the spot this comment belongs to
     */
    public function addComments($comments, $fullComments = [])
    {
        $this->_conn->batchInsert(
            $comments,
            'INSERT INTO commentsxover(messageid, nntpref, spotrating, stamp) VALUES ',
            [PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_INT],
            ['messageid', 'nntpref', 'rating', 'stamp']
        );

        if (!empty($fullComments)) {
            $this->addFullComments($fullComments);
        } // if
    }

    // addComments

    /*
     * Insert commentfull, assumes there is already an entry in commentsxover
     */
    public function addFullComments($fullComments)
    {
        /*
         * We process the fullcomments array to make sure
         * its in the proper format for inserting into the
         * database
         */
        foreach ($fullComments as &$comment) {
            /*
             * Cut off the from header so we don't overflow the database field,
             * and prepare other fields for database storage
             */
            $comment['fromhdr'] = substr($comment['fromhdr'], 0, 127);
            $comment['user-key'] = serialize($comment['user-key']);
            $comment['body'] = substr($comment['body'], 0, (1024 * 10));
            $comment['verified'] = (int) $comment['verified'];
            $comment['stamp'] = (int) $comment['stamp'];

            /*
             * Make sure we only store valid utf-8
             */
            $comment['body'] = mb_convert_encoding($comment['body'], 'UTF-8', 'UTF-8');
        } // foreach

        $this->_conn->batchInsert(
            $fullComments,
            'INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, spotterid, body, verified, avatar) VALUES ',
            [PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR],
            ['messageid', 'fromhdr', 'stamp', 'user-signature', 'user-key', 'spotterid', 'body', 'verified', 'user-avatar']
        );
    }

    // addFullComments

    /*
     * Retrieves the full comments
     */
    public function getCommentsFull($userId, $nntpRefs)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $refs = $this->_conn->arrayKeyToIn($nntpRefs);

        // eactually retrieve the comment
        $commentList = $this->_conn->arrayQuery(
            'SELECT c.messageid AS messageid, 
														(f.messageid IS NOT NULL) AS havefull,
														f.fromhdr AS fromhdr, 
														f.stamp AS stamp, 
														f.usersignature AS "user-signature", 
														f.userkey AS "user-key", 
														f.spotterid AS spotterid, 
														f.body AS body, 
														f.verified AS verified,
														c.spotrating AS spotrating,
														c.moderated AS moderated,
														f.avatar as "user-avatar",
														bl.idtype AS idtype
													FROM commentsxover c 
													LEFT JOIN commentsfull f on (f.messageid = c.messageid)
													LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = f.spotterid) AND (bl.doubled = :doubled))
													WHERE c.nntpref IN ('.$refs.') AND ((bl.spotterid IS NULL) OR (((bl.ouruserid = :ouruserid) OR (bl.ouruserid = -1)) AND (bl.idtype = 2)))
													ORDER BY c.id',
            [
                ':doubled'   => [false, PDO::PARAM_BOOL],
                ':ouruserid' => [$userId, PDO::PARAM_INT],
            ]
        );
        $commentListCount = count($commentList);
        for ($i = 0; $i < $commentListCount; $i++) {
            if ($commentList[$i]['havefull']) {
                $commentList[$i]['user-key'] = unserialize($commentList[$i]['user-key']);
            } // if
        } // for

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__);

        return $commentList;
    }

    // getCommentsFull

    /*
     * Returns the amount of new comments since 'stamp' for all
     * comments belonging to spot 'nntpRefList'
     */
    public function getNewCommentCountFor($nntpRefList, $ourUserId)
    {
        if (count($nntpRefList) == 0) {
            return [];
        } // if

        /*
         * Actually run the query
         */
        $tmp = $this->_conn->arrayQuery(
            'SELECT COUNT(nntpref) AS ccount, nntpref FROM commentsxover AS cx
									LEFT JOIN spotstatelist sl ON (sl.messageid = cx.nntpref) 
												AND (sl.ouruserid = :ouruserid)
									WHERE nntpref IN ('.$this->_conn->arrayKeyToIn($nntpRefList, 'messageid').')
 										  AND (cx.stamp > sl.seen) 
								   GROUP BY nntpref',
            [
                ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
            ]
        );

        $commentCount = [];
        foreach ($tmp as $cCount) {
            $commentCount[$cCount['nntpref']] = $cCount['ccount'];
        } // foreach

        return $commentCount;
    }

    // getNewCommentCountFor

    /*
     * Removes a comment from the database
     */
    public function removeComments($commentMsgIdList)
    {
        if (count($commentMsgIdList) == 0) {
            return;
        } // if

        $msgIdList = $this->_conn->arrayKeyToInForComments($commentMsgIdList);

        if ($msgIdList !== false) {
            $this->_conn->modify('DELETE FROM commentsfull WHERE messageid IN ('.$msgIdList.')');
            $this->_conn->modify('DELETE FROM commentsxover WHERE messageid IN ('.$msgIdList.')');
        }
    }

    // removeComments

    /*
     * Markeer een comment in de db moderated
     */
    public function markCommentsModerated($commentMsgIdList)
    {
        if (count($commentMsgIdList) == 0) {
            return;
        } // if

        $tmplist = $this->_conn->arrayKeyToInForComments($commentMsgIdList);
        if (strlen($tmplist) > 0) {
            $this->_conn->modify(
                'UPDATE commentsxover SET moderated = :moderated WHERE messageid IN ('.$tmplist.')',
                [
                    ':moderated' => [true, PDO::PARAM_BOOL],
                ]
            );
        }
    }

    // markCommentsModerated

    /*
     * Removes items from te commentsfull table older than a specific amount of days
     */
    public function expireCommentsFull($expireDays)
    {
        return $this->_conn->modify(
            'DELETE FROM commentsfull WHERE stamp < :stamp',
            [
                ':stamp' => [time() - ($expireDays * 24 * 60 * 60), PDO::PARAM_INT],
            ]
        );
    }

    // expireCommentsFull
} // Dao_Base_Comment
