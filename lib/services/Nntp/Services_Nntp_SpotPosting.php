<?php

class Services_Nntp_SpotPosting
{
    private $_nntpEngine;
    private $_spotParseUtil;

    /*
     * constructor
     */
    public function __construct(Services_Nntp_Engine $nntpEngine)
    {
        $this->_spotParseUtil = new Services_Format_Util();
        $this->_nntpEngine = $nntpEngine;
    }

    // ctor

    /*
     * Post plain usenet message
     */
    private function postPlainMessage($newsgroup, $message, $additionalHeaders)
    {
        $header = 'Subject: '.utf8_decode($message['title'])."\r\n";
        $header .= 'Newsgroups: '.$newsgroup."\r\n";
        $header .= 'Message-ID: <'.$message['newmessageid'].">\r\n";
        $header .= 'X-Newsreader: SpotWeb v'.SPOTWEB_VERSION."\r\n";
        $header .= "X-No-Archive: yes\r\n";
        $header .= $additionalHeaders;

        return $this->_nntpEngine->post([$header, $message['body']]);
    }

    // postPlainMessage

    /*
     * Post a signed usenet message, we allow for additional headers
     * so this function can be used by anything
     */
    private function postSignedMessage($user, $serverPrivKey, $newsgroup, $message, $additionalHeaders)
    {
        // instantiate necessary objects
        $spotSigning = Services_Signing_Base::factory();

        // also by the SpotWeb server
        $server_signature = $spotSigning->signMessage($serverPrivKey, '<'.$message['newmessageid'].'>');

        $addHeaders = '';

        // Only add the user-signature header if there is none set yet
        if (stripos($additionalHeaders, 'X-User-Signature: ') === false) {
            // sign the messageid
            $user_signature = $spotSigning->signMessage($user['privatekey'], '<'.$message['newmessageid'].'>');

            $addHeaders .= 'X-User-Signature: '.$this->_spotParseUtil->spotPrepareBase64($user_signature['signature'])."\r\n";
            $addHeaders .= 'X-User-Key: '.$spotSigning->pubkeyToXml($user_signature['publickey'])."\r\n";
        } // if

        $addHeaders .= 'X-Server-Signature: '.$this->_spotParseUtil->spotPrepareBase64($server_signature['signature'])."\r\n";
        $addHeaders .= 'X-Server-Key: '.$spotSigning->pubkeyToXml($server_signature['publickey'])."\r\n";
        $addHeaders .= $additionalHeaders;

        return $this->postPlainMessage($newsgroup, $message, $addHeaders);
    }

    // postSignedMessage

    /*
     * Post a binary usenet message
     */
    public function postBinaryMessage($user, $newsgroup, $body, $additionalHeaders)
    {
        $chunkLen = (1024 * 1024);
        $segmentList = [];
        $spotSigning = Services_Signing_Base::factory();

        /*
         * Now start posting chunks of the binary files
         */
        while (strlen($body) > 0) {
            $message = [];

            /*
             * Cut of the first piece of the binary file, and remove it
             * from the source string
             */
            $chunk = substr($body, 0, $chunkLen - 1);
            $body = substr($body, $chunkLen - 1);

            /*
             * Split the body in parts of 900 characters
             */
            $message['body'] = $this->safe_chunk($this->_spotParseUtil->specialZipstr($chunk), 900);

            /*
             * Create an unique messageid and store it so we can return it
             * for the actual Spot creation
             */
            $message['newmessageid'] = $spotSigning->makeRandomStr(32).'@spot.net';
            $message['title'] = md5($message['body']);

            $addHeaders = 'From: '.$user['username'].' <'.trim($user['username']).'@spot.net>'."\r\n";
            $addHeaders .= 'Content-Type: text/plain; charset=ISO-8859-1'."\r\n";
            $addHeaders .= 'Content-Transfer-Encoding: 8bit'."\r\n";
            $addHeaders .= $additionalHeaders;

            /*
             * Actually post the image
             */
            $this->postPlainMessage($newsgroup, $message, $addHeaders);

            $segmentList[] = $message['newmessageid'];
        } // if

        return $segmentList;
    }

    // postBinaryMessage

    /*
     * Post a comment to a spot
     */
    public function postComment($user, $serverPrivKey, $newsgroup, $comment)
    {
        /*
         * Create the comment specific headers
         */
        $addHeaders = 'From: '.$user['username'].' <'.trim($user['username']).'@spot.net>'."\r\n";
        $addHeaders .= 'References: <'.$comment['inreplyto'].">\r\n";
        $addHeaders .= 'X-User-Rating: '.(int) $comment['rating']."\r\n";

        /*
         * And add the X-User-Avatar header if user has an avatar specified
         */
        if (!empty($user['avatar'])) {
            $tmpAvatar = explode("\r\n", $this->safe_chunk($user['avatar'], 900));

            foreach ($tmpAvatar as $avatarChunk) {
                if (strlen(trim($avatarChunk)) > 0) {
                    $addHeaders .= 'X-User-Avatar: '.$avatarChunk."\r\n";
                } // if
            } // foreach
        } // if

        return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $comment, $addHeaders);
    }

    // postComment

    /*
     * Posts a spot file
     */
    public function postFullSpot($user, $serverPrivKey, $newsgroup, $spot)
    {
        // instantiate the necessary objects
        $spotSigning = Services_Signing_Base::factory();

        /*
         * Create the spotnet from header part accrdoing to the following structure:
         *   From: [Nickname] <[PUBLICKEY-MODULO.USERSIGNATURE]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
         */
        $spotHeader = ($spot['category'] + 1).$spot['key']; // Append the category and keyid

        // Process each subcategory and add them to the from header
        foreach ($spot['subcatlist'] as $subcat) {
            $spotHeader .= $subcat[0].str_pad(substr($subcat, 1), 2, '0', STR_PAD_LEFT);
        } // foreach

        $spotHeader .= '.'.$spot['filesize'];
        $spotHeader .= '.'. 10; // some kind of magic number?
        $spotHeader .= '.'.time();
        $spotHeader .= '.'.$spotSigning->makeRandomStr(4);
        $spotHeader .= '.'.$spotSigning->makeRandomStr(3);

        // If a tag is given, add it to the subject
        if (strlen(trim($spot['tag'])) > 0) {
            $spot['title'] = $spot['title'].' | '.$spot['tag'];
        } // if

        // Create the user-signature
        $user_signature = $spotSigning->signMessage($user['privatekey'], '<'.$spot['newmessageid'].'>');
        $header = 'X-User-Signature: '.$this->_spotParseUtil->spotPrepareBase64($user_signature['signature'])."\r\n";
        $header .= 'X-User-Key: '.$spotSigning->pubkeyToXml($user_signature['publickey'])."\r\n";

        // sign the header by using the users' key
        $header_signature = $spotSigning->signMessage($user['privatekey'], $spot['title'].$spotHeader.$spot['poster']);

        // sign the XML with the users' key
        $xml_signature = $spotSigning->signMessage($user['privatekey'], $spot['spotxml']);

        // Extract the users' publickey
        $userPubKey = $spotSigning->getPublicKey($user['privatekey']);

        // Create the From header
        $spotnetFrom = $user['username'].' <'.
                            $this->_spotParseUtil->spotPrepareBase64($userPubKey['modulo']).
                            '.'.
                            $this->_spotParseUtil->spotPrepareBase64($user_signature['signature']).'@';
        $header = 'From: '.$spotnetFrom.$spotHeader.'.'.$this->_spotParseUtil->spotPrepareBase64($header_signature['signature']).">\r\n";

        // Add the Spotnet XML file, but split it in chunks of 900 characters
        $tmpXml = explode("\r\n", $this->safe_chunk($spot['spotxml'], 900));
        foreach ($tmpXml as $xmlChunk) {
            if (strlen(trim($xmlChunk)) > 0) {
                $header .= 'X-XML: '.$xmlChunk."\r\n";
            } // if
        } // foreach
        $header .= 'X-XML-Signature: '.$this->_spotParseUtil->spotPrepareBase64($xml_signature['signature'])."\r\n";

        // post the message
        return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $spot, $header);
    }

    // postFullSpot

    /*
     * Report a post as spam
     */
    public function reportSpotAsSpam($user, $serverPrivKey, $newsgroup, $report)
    {
        /*
         * Create the comment specific headers
         */
        $addHeaders = 'From: '.$user['username'].' <'.trim($user['username']).'@spot.net>'."\r\n";
        $addHeaders .= 'References: <'.$report['inreplyto'].">\r\n";

        return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $report, $addHeaders);
    }

    // reportSpotAsSpam

    /**
     * Function which mirrors chunk_split() of PHP, but tries to avoid
     * putting a whitespace character at the end of the line, because
     * some usenet servers discard that.
     *
     * @param $data
     * @param $maxLen
     * @param string $end
     *
     * @return string
     */
    private function safe_chunk($data, $maxLen, $end = "\r\n")
    {
        /*
         * We have to protect ourselves against having
         * only spaces in the stream, so we start with
         * the half of $maxLen, and work ourway up
         */
        $minLength = ceil($maxLen / 2);
        $totalChunk = '';

        while (strlen($data) > 0) {
            $sChunk = substr($data, 0, $minLength);
            $eChunk = substr($data, $minLength, $minLength);

            $eChunkLen = strlen($eChunk);
            while ((substr($eChunk, $eChunkLen - 1, 1) == ' ') && ($eChunkLen > 0)) {
                $eChunkLen--;
            } // while

            $totalChunk .= $sChunk.substr($eChunk, 0, $eChunkLen).$end;
            $data = substr($data, strlen($sChunk.substr($eChunk, 0, $eChunkLen)));
        } // while

        return $totalChunk;
    }

    // safe_chunk
} // Services_Nntp_SpotPosting
