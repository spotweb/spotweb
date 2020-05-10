<?php

class Services_Posting_Spot
{
    private $_daoFactory;
    private $_settings;
    private $_nntp_post;
    private $_nntp_hdr;
    private $_spotValidator;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;
        $this->_nntp_post = new Services_Nntp_SpotPosting(Services_Nntp_EnginePool::pool($settings, 'post'));
        $this->_nntp_hdr = new Services_Nntp_SpotPosting(Services_Nntp_EnginePool::pool($settings, 'hdr'));

        $this->_spotValidator = new Services_Posting_Validator();
    }

    // ctor

    /*
     * Post a spot to the usenet server.
     */
    public function postSpot(Services_User_Record $svcUserRecord, array $user, array $spot, $imageFilename, $nzbFilename)
    {
        $result = new Dto_FormResult();
        $spotDao = $this->_daoFactory->getSpotDao();

        // Make sure the anonymous user and reserved usernames cannot post content
        if (!$svcUserRecord->allowedToPost($user)) {
            $result->addError(_('You need to login to be able to post spots'));
        } // if

        // Retrieve the users' private key
        $user['privatekey'] = $svcUserRecord->getUserPrivateRsaKey($user['userid']);

        $hdr_newsgroup = $this->_settings->get('hdr_group');
        $bin_newsgroup = $this->_settings->get('nzb_group');

        /*
         * We'll get the messageid's with <>'s but we always strip
         * them in Spotweb, so remove them
         */
        $spot['newmessageid'] = substr($spot['newmessageid'], 1, -1);

        /*
        $hdr_newsgroup = 'alt.test';
        $bin_newsgroup = 'alt.test';
        */

        // If the hashcash doesn't match, we will never post it
        if (substr(sha1('<'.$spot['newmessageid'].'>'), 0, 4) != '0000') {
            $result->addError(_('Hash was not calculated properly'));
        } // if

        // Verify several properties from the caller
        $result->addData('spot', $spot);
        $result = $this->_spotValidator->verifyTitle($result);
        $result = $this->_spotValidator->verifyBody($result);
        $result = $this->_spotValidator->verifyCategories($result);
        $result = $this->_spotValidator->verifyWebsite($result);
        $result = $this->_spotValidator->verifyTag($result);

        /*
         * Retrieve the spot information from the result,
         * and remove it again. We do not want to send the
         * whole spot back to the caller
         */
        $spot = $result->getData('spot');
        $result->removeData('spot');

        // Read the contents of image so we can check it
        $imageContents = file_get_contents($imageFilename);

        // the image should be below 1MB
        if (strlen($imageContents) > 1024 * 1024) {
            $result->addError(_('Uploaded image is too large (maximum 1MB)'));
        } // if

        /*
         * Get some image information, if it fails, this is an
         * error as well
         */
        $tmpGdImageSize = getimagesize($imageFilename);
        if ($tmpGdImageSize === false) {
            $result->addError(_('Uploaded image was not recognized as an image'));
        } else {
            $imageInfo = ['width' => $tmpGdImageSize[0],
                'height'          => $tmpGdImageSize[1], ];
        } // if

        /*
         * Load the NZB file as an XML file so we can make sure
         * it's a valid XML and NZB file and we can determine the
         * filesize
         */
        $nzbFileContents = file_get_contents($nzbFilename);
        $nzbXml = simplexml_load_string($nzbFileContents);

        // Do some basic sanity checking for some required NZB elements
        if (empty($nzbXml->file)) {
            $result->addError(_('Incorrect NZB file'));
        } // if

        // and determine the total filesize
        $spot['filesize'] = 0;
        foreach ($nzbXml->file as $file) {
            foreach ($file->segments->segment as $seg) {
                $spot['filesize'] += (int) $seg['bytes'];
            } // foreach
        } // foreach

        /*
         * Make sure we didn't use this messageid recently or at all, this
         * prevents people from not recalculating the hashcash in order to spam
         * the system
         */
        if (!$spotDao->isNewSpotMessageIdUnique($spot['newmessageid'])) {
            $result->addError(_('Replay attack!?'));
        } // if

        // Make sure a newmessageid contains a certain length
        if (strlen($spot['newmessageid']) < 10) {
            $result->addError(_('MessageID too short!?'));
        } // if

        // We require the keyid 7 because it is selfsigned
        $spot['key'] = 7;

        // Poster's  username
        $spot['poster'] = $user['username'];

        // actually post the spot
        if ($result->isSuccess()) {
            /*
             * Retrieve the image information and post the image to
             * the appropriate newsgroup so we have the messageid list of
             * images
             */
            $imgSegmentList = $this->_nntp_post->postBinaryMessage($user, $bin_newsgroup, $imageContents, '');
            $imageInfo['segments'] = $imgSegmentList;

            // Post the NZB file to the appropriate newsgroups
            $nzbSegmentList = $this->_nntp_post->postBinaryMessage($user, $bin_newsgroup, gzdeflate($nzbFileContents), '');

            // Convert the current Spotnet info, to an XML structure
            $spotCreator = new Services_Format_Creation();
            $spotXml = $spotCreator->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);
            $spot['spotxml'] = $spotXml;

            // And actually post to the newsgroups
            $this->_nntp_post->postFullSpot(
                $user,
                $this->_settings->get('privatekey'),  // Server private key
                                           $hdr_newsgroup,
                $spot
            );
            $spotDao->addPostedSpot($user['userid'], $spot, $spotXml);
        } // if

        return $result;
    }

    // postSpot
} // Services_Posting_Spot
