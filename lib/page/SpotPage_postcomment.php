<?php

class SpotPage_postcomment extends SpotPage_Abs
{
    private $_inReplyTo;
    private $_commentForm;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_commentForm = $params['commentform'];
        $this->_inReplyTo = $params['inreplyto'];
    }

    // ctor

    public function render()
    {
        // Make sure the result is set to 'not comitted' per default
        $result = new Dto_FormResult('notsubmitted');

        // Validate proper permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_post_comment, '');

        $spotParseUtil = new Services_Format_Util();
        $spotSigning = Services_Signing_Base::factory();
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        /*
         * Make sure we have the template for the comment form
         * so our view can always render properties
         */
        $comment = ['body' => '',
            'rating'       => 0,
            'inreplyto'    => $this->_inReplyTo,
            'newmessageid' => '',
            'randomstr'    => '', ];

        // set the page title
        $this->_pageTitle = 'spot: post comment';

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        $formAction = $this->_commentForm['action'];

        if ($formAction == 'post') {
            // Make sure we use valid forms
            $comment = array_merge($comment, $this->_commentForm);

            // validate whether we can post comments, if so, do this
            $svcPostComment = new Services_Posting_Comment($this->_daoFactory, $this->_settings);
            $result = $svcPostComment->postComment($svcUserRecord, $this->_currentSession['user'], $comment);

            if ($result->isSuccess()) {
                /* Format the body so we can have smilies and stuff be shown in the template */
                $tmpBody = $this->_tplHelper->formatContent($comment['body']);

                /* Try to create the avatar */
                if (!empty($this->_currentSession['user']['avatar'])) {
                    $comment['user-avatar'] = $this->_currentSession['user']['avatar'];
                } else {
                    $comment['user-key'] = $spotSigning->getPublicKey($this->_currentSession['user']['publickey']);
                } // else
                $commentImage = $this->_tplHelper->makeCommenterImageUrl($comment);

                /* and return the result to the system */
                $result->addData('user', $this->_currentSession['user']['username']);
                $result->addData('spotterid', $spotParseUtil->calculateSpotterId($comment['user-key']['modulo']));
                $result->addData('rating', $comment['rating']);
                $result->addData('body', $tmpBody);
                $result->addData('commentimage', $commentImage);
            } // if
        } // if

        //- display stuff -#
        $this->template('postcomment', ['postcommentform' => $comment,
            'result'                                      => $result, ]);
    }

    // render
} // class SpotPage_postcomment
