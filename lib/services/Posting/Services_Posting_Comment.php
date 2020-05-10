<?php

class Services_Posting_Comment
{
    private $_daoFactory;
    private $_settings;
    private $_nntp_post;
    private $_nntp_hdr;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;
        $this->_nntp_post = new Services_Nntp_SpotPosting(Services_Nntp_EnginePool::pool($settings, 'post'));
        $this->_nntp_hdr = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($settings, 'hdr'));
    }

    // ctor

    /*
     * Post a comment
     */
    public function postComment(Services_User_Record $svcUserRecord, array $user, array $comment)
    {
        $result = new Dto_FormResult();
        $commentDao = $this->_daoFactory->getCommentDao();

        // Make sure the anonymous user and reserved usernames cannot post content
        if (!$svcUserRecord->allowedToPost($user)) {
            $result->addError(_('You need to login to be able to post comments'));
        } // if

        // Retrieve the users' private key
        $user['privatekey'] = $svcUserRecord->getUserPrivateRsaKey($user['userid']);

        /*
         * We'll get the messageid's with <>'s but we always strip
         * them in Spotweb, so remove them
         */
        $comment['newmessageid'] = substr($comment['newmessageid'], 1, -1);

        // we won't bother when the hashcash is not properly calculcated
        if (substr(sha1('<'.$comment['newmessageid'].'>'), 0, 4) != '0000') {
            $result->addError(_('Hash was not calculated properly'));
        } // if

        // Body cannot be either empty or very short
        $comment['body'] = trim($comment['body']);
        if (strlen($comment['body']) < 2) {
            $result->addError(_('Please enter a comment'));
        } // if
        if (strlen($comment['body']) > (1024 * 10)) {
            $result->addError(_('Comment is too long'));
        } // if

        // Rating must be within range
        if (($comment['rating'] > 10) || ($comment['rating'] < 0)) {
            $result->addError(_('Invalid rating'));
        } // if

        /*
         * The "newmessageid" is based upon the messageid we are replying to,
         * this is to make sure a user cannot reuse an calculated hashcash
         * for an spam attack on different posts
         */
        $replyToPart = substr($comment['inreplyto'], 0, strpos($comment['inreplyto'], '@'));

        if (substr($comment['newmessageid'], 0, strlen($replyToPart)) != $replyToPart) {
            $result->addError(_('Replay attack!?'));
        } // if

        /*
         * Make sure the random message we require in the system has not been
         * used recently to prevent one calculated hashcash to be reused again
         * and again
         */
        if (!$commentDao->isCommentMessageIdUnique($comment['newmessageid'])) {
            $result->addError(_('Replay attack!?'));
        } // if

        // Make sure a newmessageid contains a certain length
        if (strlen($comment['newmessageid']) < 10) {
            $result->addError(_('MessageID too short!?'));
        } // if

        // Retrieve the spot to which we are commenting
        $svcProvFullSpot = new Services_Providers_FullSpot($this->_daoFactory->getSpotDao(), $this->_nntp_hdr);
        $fullSpot = $svcProvFullSpot->fetchFullSpot($comment['inreplyto'], $user['userid']);

        // Add the title as a comment property
        $comment['title'] = 'Re: '.$fullSpot['title'];

        /*
         * Body is UTF-8 (we instruct the browser to do everything in UTF-8), but
         * usenet wants its body in iso-8859-1.
         *
         * The database requires UTF8 again, so we keep seperate bodies for
         * the database and for the system
         */
        $dbComment = $comment;
        $comment['body'] = utf8_decode($comment['body']);

        // and actually post the comment
        if ($result->isSuccess()) {
            try {
                $this->_nntp_post->postComment(
                    $user,
                    $this->_settings->get('privatekey'),  // Server private key
                                               $this->_settings->get('comment_group'),
                    $comment
                );

                $commentDao->addPostedComment($user['userid'], $dbComment);
            } catch (Exception $x) {
                $result->addError($x->getMessage());
            } // catch
        } // if

        return $result;
    }

    // postComment
} // class Services_Posting_Comment
