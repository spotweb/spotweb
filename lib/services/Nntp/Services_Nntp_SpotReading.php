<?php

class Services_Nntp_SpotReading
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
     * Parse an header and extract specific fields
     * from it
     */
    private function parseHeader($headerList, $tmpAr)
    {
        /*
         * Interprets the header fields in a global way
         */
        foreach ($headerList as $hdr) {
            $keys = explode(':', $hdr);

            switch (strtolower($keys[0])) {
                case 'from': $tmpAr['fromhdr'] = mb_convert_encoding(trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: '))), 'ISO-8859-1', 'UTF-8');
                    break;
                case 'date': $tmpAr['stamp'] = strtotime(substr($hdr, strlen('Date: ')));
                    break;
                case 'x-xml': $tmpAr['fullxml'] .= substr($hdr, 7);
                    break;
                case 'x-user-signature': $tmpAr['user-signature'] = $this->_spotParseUtil->spotUnprepareBase64(substr($hdr, 18));
                    break;
                case 'x-xml-signature': $tmpAr['xml-signature'] = $this->_spotParseUtil->spotUnprepareBase64(substr($hdr, 17));
                    break;
                case 'x-newsreader': $tmpAr['newsreader'] = substr($hdr, 14);
                    break;
                case 'x-user-avatar': $tmpAr['user-avatar'] .= substr($hdr, 15);
                    break;
                case 'x-user-key':
                    $xml = simplexml_load_string(substr($hdr, 12));
                    if ($xml !== false) {
                        $tmpAr['user-key']['exponent'] = (string) $xml->Exponent;
                        $tmpAr['user-key']['modulo'] = (string) $xml->Modulus;
                    } // if
                    break;
                 // x-user-key
            } // switch
        } // foreach

        /*
         * Add newsreader (if present) to xml to be saved in fullspots
         */
        if ((!empty($tmpAr['fullxml'])) && (!empty($tmpAr['newsreader']))) {
            @$xml = simplexml_load_string($tmpAr['fullxml']);
            if ($xml == false) {
                $tmpAr['fullxml'] = preg_replace("/'([a-z,A-Z])/", "' $1", $tmpAr['fullxml']);
                @$xml = simplexml_load_string($tmpAr['fullxml']);
            }
            if ($xml !== false) {
                $extra = $xml->addChild('Extra');
                $extra->addchild('Newsreader', $tmpAr['newsreader']);
                $tmpAr['fullxml'] = (string) $xml->asXML();
            }
        }

        return $tmpAr;
    }

    // parseHeader

    /*
     * Callback function for sorting of comments on date
     */
    private function cbCommentDateSort($a, $b)
    {
        if ($a['stamp'] == $b['stamp']) {
            return 0;
        } // if

        return ($a['stamp'] < $b['stamp']) ? -1 : 1;
    }

    // cbCommentDateSort

    /*
     * Returns a list of comments
     */
    public function readComments($commentList)
    {
        $comments = [];
        $spotSigning = Services_Signing_Base::factory();

        /*
         * We retrieve all comments from the server
         */
        foreach ($commentList as $comment) {
            try {
                $commentTpl = ['fromhdr' => '', 'stamp' => 0, 'user-signature' => '',
                    'user-key'           => ['exponent' =>'', 'Modulo' => ''], 'spotterid' => '', 'verified' => false,
                    'user-avatar'        => '', 'fullxml' => '', 'messageid' => $comment['messageid'], 'newsreader' => '', ];

                SpotTiming::start('NntpSpotReading::readComments()->getArticle call');
                $article = array_merge($commentTpl, $this->_nntpEngine->getArticle('<'.$comment['messageid'].'>'));
                SpotTiming::stop('NntpSpotReading::readComments()->getArticle call');
                $tmpAr = $this->parseHeader($article['header'], $article);

                /*
                 * Validate the XML signature of this comment
                 */
                $tmpAr['verified'] = $spotSigning->verifyComment($tmpAr);
                if ($tmpAr['verified']) {
                    $tmpAr['spotterid'] = $this->_spotParseUtil->calculateSpotterId($tmpAr['user-key']['modulo']);
                } // if

                // encode the body for UTF8 and transform it from an array to an EOL delimited string
                $tmpAr['body'] = mb_convert_encoding(implode("\r\n", $tmpAr['body']), 'ISO-8859-1', 'UTF-8');

                /*
                 * Some comments are not actual comments but incorreclty posted NZB
                 * files and stuff. Basically, we limit the length of comments
                 * if they are too large to prevent memory issues.
                 */
                if (strlen($tmpAr['body']) > (1024 * 10)) {
                    $tmpAr['body'] = substr($tmpAr['body'], 0, 1024 * 10);
                } // if

                $comments[] = $tmpAr;
            } catch (Exception $x) {
                /*
                 * Sometimes retrieval of a comment fails. This is weird because the comments
                 * are retrieved from the server using XOVER, but we just ignore the
                 * error as there is no way to handle this really.
                 */
            } // catch
        } // foreach

        // Sort comments on date
        usort($comments, [$this, 'cbCommentDateSort']);

        return $comments;
    }

    // readComments

    /*
     * Returns an image poted to the newsgroup
     */
    public function readBinary($segmentList, $compressed)
    {
        $bin = '';

        foreach ($segmentList as $seg) {
            $bin .= implode('', $this->_nntpEngine->getBody('<'.$seg.'>'));
        } // foreach

        if ($compressed) {
            /*
             * We do this in two function calls, to make sure we do not need
             * two copies of this potentially very large string in memory.
             * This can save an Out-of-memory error.
             */
            $bin = $this->_spotParseUtil->unspecialZipStr($bin);

            return gzinflate($bin);
        } else {
            return $this->_spotParseUtil->unspecialZipStr($bin);
        } // else
    }

    // readBinary

    /*
     * Retrieve the fullspot from the NNTP server
     */
    public function readFullSpot($msgId)
    {
        // initialize some variables
        $spotSigning = Services_Signing_Base::factory();

        $spot = ['fullxml'   => '',
            'user-signature' => '',
            'user-key'       => ['exponent' => '', 'modulo' => ''],
            'verified'       => false,
            'messageid'      => $msgId,
            'spotterid'      => '',
            'xml-signature'  => '',
            'moderated'      => 0,
            'user-avatar'    => '',
            'newsreader'     => '', ];

        /*
         * Retrieve the header of the given spot
         */
        $header = $this->_nntpEngine->getHeader('<'.$msgId.'>');
        $spot = array_merge($spot, $this->parseHeader($header, $spot));

        /*
         * Validate the XML signature of the spot
         */
        $spot['verified'] = $spotSigning->verifyFullSpot($spot);

        /*
         * if the spot is verified, lets calculate the spotterid as well
         * so the user can safely store it in the database
         */
        if ($spot['verified']) {
            $spot['spotterid'] = $this->_spotParseUtil->calculateSpotterId($spot['user-key']['modulo']);
        } // if

        /*
         * Some spots are very large bcause they are spammy. if so, we skip them to
         * prevent memory and database issues
         */
        if (strlen($spot['fullxml']) > (1024 * 50)) {
            return false;
        } // if

        /*
         * Parse the XML structure of the spot, technically not necessary
         */
        $spotParser = new Services_Format_Parsing();
        $spot = array_merge($spotParser->parseFull($spot['fullxml']), $spot);

        return $spot;
    }

    // readFullSpot
} // Services_Nntp_SpotReading
