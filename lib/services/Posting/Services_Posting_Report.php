<?php

class Services_Posting_Report
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
     * Post a spam report
     */
    public function postSpamReport(Services_User_Record $svcUserRecord, array $user, array $report)
    {
        $result = new Dto_FormResult();
        $spotReportDao = $this->_daoFactory->getSpotReportDao();

        // Make sure the anonymous user and reserved usernames cannot post content
        if (!$svcUserRecord->allowedToPost($user)) {
            $result->addError(_('You need to login to be able to report spam'));
        } // if

        // Retrieve the users' private key
        $user['privatekey'] = $svcUserRecord->getUserPrivateRsaKey($user['userid']);

        // Make sure no spam report has already been posted by this user to prevent flooding
        if ($spotReportDao->isReportPlaced($report['inreplyto'], $user['userid'])) {
            $result->addError(_('This spot has already been reported'));
        } // if

        /*
         * We'll get the messageid's with <>'s but we always strip
         * them in Spotweb, so remove them
         */
        $report['newmessageid'] = substr($report['newmessageid'], 1, -1);

        // retrieve the spot this is a report of
        $svcProvFullSpot = new Services_Providers_FullSpot($this->_daoFactory->getSpotDao(), $this->_nntp_hdr);
        $fullSpot = $svcProvFullSpot->fetchFullSpot($report['inreplyto'], $user['userid']);

        // we won't bother when the hashcash is not properly calculcated
        if (substr(sha1('<'.$report['newmessageid'].'>'), 0, 4) != '0000') {
            $result->addError(_('Hash was not calculated properly'));
        } // if

        // Body cannot be empty or very short
        $report['body'] = trim($report['body']);
        if (strlen($report['body']) < 2) {
            $result->addError(_('Please provide a reason why this Spot should be reported'));
        } // if

        // controleer dat de messageid waarop we replyen overeenkomt
        // met het newMessageid om replay-attacks te voorkomen.
        $replyToPart = substr($report['inreplyto'], 0, strpos($report['inreplyto'], '@'));

        if (substr($report['newmessageid'], 0, strlen($replyToPart)) != $replyToPart) {
            $result->addError(_('Replay attack!?'));
        } // if

        /*
         * Make sure the random message we require in the system has not been
         * used recently to prevent one calculated hashcash to be reused again
         * and again
         */
        if (!$spotReportDao->isReportMessageIdUnique($report['newmessageid'])) {
            $result->addError(_('Replay attack!?'));
        } // if

        // Make sure a newmessageid consists of a certain length
        if (strlen($report['newmessageid']) < 10) {
            $result->addError(_('MessageID too short!?'));
        } // if

        /*
         * Body is UTF-8 (we instruct the browser to do everything in UTF-*), but
         * usenet wants its body in UTF-8.
         *
         * The database requires UTF8 again, so we keep seperate bodies for
         * the database and for the system
         */
        $dbReport = $report;
        $report['body'] = utf8_decode($report['body']);
        $report['title'] = 'REPORT <'.$report['inreplyto'].'> '.$fullSpot['title'];

        // en post daadwerkelijk de report
        if ($result->isSuccess()) {
            $this->_nntp_post->reportSpotAsSpam(
                $user,
                $this->_settings->get('privatekey'),  // Server private key
                                           $this->_settings->get('report_group'),
                $report
            );
            $spotReportDao->addPostedReport($user['userid'], $dbReport);
        } // if

        return $result;
    }

    // postSpamReport
} // class Services_Posting_Report
