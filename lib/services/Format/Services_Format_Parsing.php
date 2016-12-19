<?php

class Services_Format_Parsing
{
    /**
     * There are currently two known methods to which Spots are signed,
     * each having different charachteristics, making it a bit difficult
     * to work with this.
     * The oldest method uses a secret private key and a signing server, we
     * name this method SPOTSIGN_V1. The users' public key is only available
     * in the XML header, not in the From header. This is the preferred method.
     * The second method uses a so-called "self signed" spot (the spotter signs
     * the spots, posts the public key in the header and a hashcash is used to
     * prevent spamming). This method is called SPOTSIGN_V2.
     */
    const SPOTSIGN_V1 = 1;

    /**
     * @see SPOTSIGN_V1;
     */
    const SPOTSIGN_V2 = 2;

    private $spotSigning = null;

    private $util;

    public function __construct()
    {
        $this->spotSigning = Services_Signing_Base::factory();
        $this->util        = new Services_Format_Util();
    }

    /**
     * Parse a full Spot according to the XML structure.
     */
    public function parseFull($xmlStr)
    {
        /**
         * Create a template array so we always have the full fields to prevent ugly notices.
         */
        $tpl_spot = array(
            'category'    => '',
            'website'     => '',
            'image'       => '',
            'sabnzbdurl'  => '',
            'messageid'   => '',
            'searchurl'   => '',
            'description' => '',
            'sub'         => '',
            'filesize'    => '',
            'poster'      => '',
            'tag'         => '',
            'nzb'         => '',
            'title'       => '',
            'filename'    => '',
            'newsgroup'   => '',
            'subcata'     => '',
            'subcatb'     => '',
            'subcatc'     => '',
            'subcatd'     => '',
            'subcatz'     => '',
            'created'     => '',
            'key'         => '',
            'prevMsgids'  => array(),
            'newsreader'  => ''
        );

        // Some legacy spotNet clients create incorrect/invalid multiple segments,
        // we use this crude way to workaround this. GH issue #1608
        if (strpos($xmlStr, 'spot.net></Segment') !== false) {
            $xmlStr = str_replace(
                array('spot.net></Segment>', 'spot.ne</Segment>'),
                array('spot.net</Segment>', 'spot.net</Segment>'),
                $xmlStr
            );
        }

        // Fix up some forgotten entity encoding / cdata sections in the XML
        $xmlStr = $this->correctElmContents(
            $xmlStr,
            array(
                'Title',
                'Description',
                'Image',
                'Tag',
                'Website'
            )
        );

        // Supress errors for corrupt messageids, eg: <evoCgYpLlLkWe97TQAmnV@spot.net>
        $xmltop                  = @(new SimpleXMLElement($xmlStr));
        $xml                     = $xmltop->Posting;
        $tpl_spot['created']     = (string)$xml->Created;
        $tpl_spot['key']         = (string)$xml->Key;
        $tpl_spot['category']    = (string)$xml->Category;
        $tpl_spot['website']     = (string)$xml->Website;
        $tpl_spot['description'] = (string)$xml->Description;
        $tpl_spot['filesize']    = (string)$xml->Size;
        $tpl_spot['poster']      = (string)utf8_encode($xml->Poster);
        $tpl_spot['tag']         = (string)utf8_encode($xml->Tag);
        $tpl_spot['title']       = (string)$xml->Title;

        // Decode HTML special characters, title otherwise search will be broken, description as body in newsgroup.
        $tpl_spot['title']       = html_entity_decode(
            $tpl_spot['title'],
            ENT_QUOTES,
            'UTF-8'
        );
        $tpl_spot['description'] = html_entity_decode(
            $tpl_spot['description'],
            ENT_QUOTES,
            'UTF-8'
        );

        // FTD spots have the filename.
        if (!empty($xml->Filename)) {
            $tpl_spot['filename'] = (string)$xml->Filename;
        }

        // FTD spots have the newsgroup.
        if (!empty($xml->Newsgroup)) {
            $tpl_spot['newsgroup'] = (string)$xml->newsgroup;
        }

        /**
         * Images available can be in the XML in two different ways.
         * Some older spots just have an URL we can use, newer spots
         * have an height/width/messageid(s) pair we use to retrieve the image
         * from
         */
        if (empty($xml->Image->Segment)) {
            $tpl_spot['image'] = (string)$xml->Image;
        } else {
            $tpl_spot['image'] = array(
                'height' => (string)$xml->Image['Height'],
                'width'  => (string)$xml->Image['Width']
            );

            foreach ($xml->xpath('/Spotnet/Posting/Image/Segment') as $seg) {
                // Make sure the messageid's are valid so we do not throw an NNTP error
                if (!$this->util->validMessageId((string)$seg)) {
                    $tpl_spot['image']['segment'] = array();
                    break;
                } else {
                    if (!isset($tpl_spot['image']['segment']) || !is_array($tpl_spot['image']['segment'])) {
                        $tpl_spot['image']['segment'] = array();
                    }
                    $tpl_spot['image']['segment'][] = (string)$seg;
                }
            }
        }

        // Just stitch together the NZB segments.
        foreach ($xml->xpath('/Spotnet/Posting/NZB/Segment') as $seg) {
            if (!$this->util->validMessageId((string)$seg)) {
                $tpl_spot['nzb'] = array();
                break;
            } else {
                if (!isset($tpl_spot['nzb']) || !is_array($tpl_spot['nzb'])) {
                    $tpl_spot['nzb'] = array();
                }
                $tpl_spot['nzb'][] = (string)$seg;
            }
        }

        // PREVSPOTS.
        if (!empty($xml->PREVSPOTS->Spot)) {
            foreach ($xml->xpath('/Spotnet/Posting/PREVSPOTS/Spot') as $seg) {
                // Make sure the messageid's are valid so we do not throw an NNTP error.
                if ($this->util->validMessageId((string)$seg)) {
                    $tpl_spot['prevMsgids'][] = (string)$seg;
                }
            }
        }

        // Extra / newsreader.
        if (!empty($xmltop->Extra->Newsreader)) {
            $tpl_spot['newsreader'] = (string)$xmltop->Extra->Newsreader;
        }

        // Fix the category in the XML array but only for new spots.
        if ((int)$xml->Key != 1) {
            $tpl_spot['category'] = ((int)$tpl_spot['category']) - 1;
        }

        /**
         * For FTD spots an array of subcategories is created. This array is not
         * compatible with that of newer spots so we need two seperate codepaths
         */
        $subcatList = array();

        /**
         * We fix up the category list later in the system, so we just extract the
         * list of subcategories
         */
        if (!empty($xml->SubCat)) {
            foreach ($xml->xpath('/Spotnet/Posting/Category/SubCat') as $sub) {
                $subcatList[] = (string)$sub;
            }
        } else {
            foreach ($xml->xpath('/Spotnet/Posting/Category/Sub') as $sub) {
                $subcatList[] = (string)$sub;
            }
        }

        /**
         * Mangle the several types of subcategory listing to make sure we only
         * have to use one type in the rest of Spotwb
         */
        foreach ($subcatList as $subcat) {
            if (preg_match('/(\d+)([aAbBcCdDzZ])(\d+)/', preg_quote($subcat),
                $tmpMatches)) {
                $subCatVal = strtolower($tmpMatches[2]) . ((int)$tmpMatches[3]);
                $tpl_spot['subcat' . $subCatVal[0]] .= $subCatVal . '|';
            }
        }

        /**
         * subcatz is a subcategory introduced in later Spotnet formats, we prefer to
         * always have this subcategory so we just fake it if it's not listed.
         */
        if (empty($tpl_spot['subcatz'])) {
            $tpl_spot['subcatz'] = SpotCategories::createSubcatZ($tpl_spot['category'],
                $tpl_spot['subcata'] . $tpl_spot['subcatb'] . $tpl_spot['subcatd']);
        }

        // Map deprecated genre categories to their new genre category.
        $tpl_spot['subcatd'] = SpotCategories::mapDeprecatedGenreSubCategories(
            $tpl_spot['category'],
            $tpl_spot['subcatd'], $tpl_spot['subcatz']
        );
        $tpl_spot['subcatc'] = SpotCategories::mapLanguageSubCategories(
            $tpl_spot['category'],
            $tpl_spot['subcatc'], $tpl_spot['subcatz']
        );

        // And return the parsed XML.
        return $tpl_spot;
    }

    /**
     * Some Spotnet clients create invalid XML - see
     * messageid ZOB4WPyqQfcHqykUAES8q@spot.net for example, because
     * it uses an unescaped & not in an CDATA block.
     *
     * @param string $xmlStr
     * @param array  $elems
     *
     * @return mixed
     */
    private function correctElmContents($xmlStr, $elems)
    {
        $cdataStart = '<![CDATA[';
        $cdataEnd   = ']]>';

        // Replace low-ascii characters, see messageid KNCuzvnxJJErJibUAAxQJ@spot.net
        $xmlStr = preg_replace('/[\x00-\x1F]/', '', $xmlStr);

        // and loop through all elements and fix them up
        foreach ($elems as $elementName) {
            // find the element entries
            $startElem = stripos($xmlStr, '<' . $elementName . '>');
            $endElem   = stripos($xmlStr, '</' . $elementName . '>');

            if (($startElem === false) || ($endElem === false)) {
                continue;
            }

            /**
             * Make sure this elements content is not preceeded by the
             * required CDATA header
             */
            if (substr($xmlStr, $startElem + strlen($elementName) + 2, strlen($cdataStart)) !== $cdataStart) {
                $xmlStr = str_replace(
                    array('<' . $elementName . '>', '</' . $elementName . '>'),
                    array(
                        '<' . $elementName . '>' . $cdataStart,
                        $cdataEnd . '</' . $elementName . '>'
                    ),
                    $xmlStr);
            }
        }

        return $xmlStr;
    }

    /**
     * Parse a Spot using only the header information
     */
    public function parseHeader($subj, $from, $date, $messageid, $rsaKeys)
    {
        // Initialize an empty array, we create a basic template in a few.
        $spot = array();

        /**
         * The "From" header is created using the following system:
         *   From: [Nickname] <[RANDOM or PUBLICKEY]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
         *        or
         *   From: [Nickname] <[PUBLICKEY-MODULO.USERSIGNATURE]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
         * First we want to extract everything after the @ but because a nickname could contain an @, we have to mangle it a bit
         */
        $fromInfoPos = strpos($from, '<');
        if ($fromInfoPos === false) {
            return false;
        } else {
            // Remove the posters' name and the <> characters.
            $fromAddress = explode('@', substr($from, $fromInfoPos + 1, -1));

            if (count($fromAddress) < 2) {
                return false;
            }

            $spot['header'] = $fromAddress[1];

            /**
             * It is possible the part before the @ contains both the
             * users' signature as the spots signature as signed by the user
             */
            $headerSignatureTemp      = explode('.', $fromAddress[0]);
            $spot['selfsignedpubkey'] = $this->util->spotUnprepareBase64($headerSignatureTemp[0]);
            if (isset($headerSignatureTemp[1])) {
                $spot['user-signature'] = $this->util->spotUnprepareBase64($headerSignatureTemp[1]);
            }
        }

        /**
         * Initialize some basic variables. We set 'verified' to false so we  can
         * exit this function at any time and the gathered data for this spot up til
         * then is stil ignored.
         */
        $spot['verified']  = false;
        $spot['filesize']  = 0;
        $spot['messageid'] = $messageid;
        $spot['stamp']     = strtotime($date);

        // Split the .-delimited fields into an array so we can mangle it. We require
        // atleast six fields, if any less we can safely assume the spot is invalid.
        $fields = explode('.', $spot['header']);
        if (count($fields) < 6) {
            return false;
        }

        // Extract the fixed fields from the header.
        $spot['poster']    = substr($from, 0, $fromInfoPos - 1);
        $spot['category']  = (substr($fields[0], 0, 1)) - 1.0;
        $spot['keyid']     = (int)substr($fields[0], 1, 1);
        $spot['filesize']  = $fields[1];
        $spot['subcata']   = '';
        $spot['subcatb']   = '';
        $spot['subcatc']   = '';
        $spot['subcatd']   = '';
        $spot['subcatz']   = '';
        $spot['wassigned'] = false;
        $spot['spotterid'] = '';
        $isRecentKey       = $spot['keyid'] <> 1;

        // If the keyid is invalid, abort trying to parse it.
        if ($spot['keyid'] < 0) {
            return false;
        }

        /**
         * Listings of subcategories is dependent on the age of the spot.
         * FTD spots just list all subcategories like: a9b4c0d5d15d11
         * Newer spots always use three characters for each subcategory like: a09b04c00d05d15d11.
         * We really do not care for this, we just parse them using the same code as the
         * first one.
         * We pad $strCatList with an extra set of tokes so we always parse te last category,
         * we make sure any sanitycheck is passed by adding 3 tokens.
         */
        $strCatList    = strtolower(substr($fields[0], 2)) . '!!!';
        $strCatListLen = strlen($strCatList);

        /**
         * Initialize some basic variables to use for sanitychecking (eg: valid subcats)
         */
        $validSubcats = array(
            'a' => true,
            'b' => true,
            'c' => true,
            'd' => true,
            'z' => true
        );
        $tmpCatBuild  = '';

        // And just try to extract all given subcategories.
        for ($i = 0; $i < $strCatListLen; $i++) {
            /*
             * If the current character is not an number, we found the next
             * subcategory. Add the current one to the list, and start
             * parsing the new one
             */
            if ((!is_numeric($strCatList[$i])) && (!empty($tmpCatBuild))) {
                if (isset($validSubcats[$tmpCatBuild[0]])) {
                    $spot['subcat' . $tmpCatBuild[0]] .= $tmpCatBuild[0]
                        . (int)substr(
                            $tmpCatBuild,
                            1
                        )
                        . '|';
                }

                $tmpCatBuild = '';
            }

            $tmpCatBuild .= $strCatList[$i];
        }

        /**
         * subcatz is a subcategory introduced in later Spotnet formats, we prefer to
         * always have this subcategory so we just fake it if it's not listed.
         */
        if (empty($spot['subcatz'])) {
            $spot['subcatz'] = SpotCategories::createSubcatz(
                $spot['category'],
                $spot['subcata'] . $spot['subcatb'] . $spot['subcatd']
            );
        }

        // Map deprecated genre categories to their new genre category.
        $spot['subcatd'] = SpotCategories::mapDeprecatedGenreSubCategories(
            $spot['category'],
            $spot['subcatd'],
            $spot['subcatz']
        );
        $spot['subcatc'] = SpotCategories::mapLanguageSubCategories(
            $spot['category'],
            $spot['subcatc'],
            $spot['subcatz']
        );

        if ((strpos($subj, '=?') !== false) && (strpos($subj, '?=') !== false)) {
            // This is an old format to parse, instantiate the legacy parsing.
            $legacyParser = new Services_Format_ParsingLegacy();

            // Make sure its as simple as possible.
            $subj = str_replace('?= =?', '?==?', $subj);
            $subj = str_replace(
                '\r',
                '',
                trim($legacyParser->oldEncodingParse($subj))
            );
            $subj = str_replace('\n', '', $subj);
        }

        if ($isRecentKey) {
            $tmp = explode('|', $subj);

            $spot['title'] = trim($tmp[0]);
            if (count($tmp) > 1) {
                $spot['tag'] = trim($tmp[1]);
            } else {
                $spot['tag'] = '';
            }
        } else {
            $tmp = explode('|', $subj);
            if (count($tmp) <= 1) {
                $tmp = array($subj);
            }

            $spot['tag'] = trim($tmp[count($tmp) - 1]);

            // remove the tags from the array.
            array_pop($tmp);
            array_pop($tmp);

            $spot['title'] = trim(implode('|', $tmp));

            if ((strpos($spot['title'], chr(0xc2)) !== false) | (strpos($spot['title'], chr(0xc3)) !== false)) {
                // This is an old format to parse, instantiate the legacy parsing
                $legacyParser  = new Services_Format_ParsingLegacy();
                $spot['title'] = trim($legacyParser->oldEncodingParse($spot['title']));
            }
        }

        // Title and poster fields are mandatory, we require it to validate the signature.
        if (((strlen($spot['title']) == 0) || (strlen($spot['poster']) == 0))) {
            return $spot;
        }

        // For any recentkey ( >1) or spots created after year-2010, we require the spot
        // to be signed.
        $mustbeSigned = $isRecentKey | ($spot['stamp'] > 1293870080);
        // If must be signed.
        if ($mustbeSigned) {
            $spot['headersign'] = $fields[count($fields) - 1];
            $spot['wassigned']  = (strlen($spot['headersign']) != 0);
        } // If doesn't need to be signed, pretend that it is.
        else {
            $spot['verified']  = true;
            $spot['wassigned'] = false;
        }

        /**
         * Don't verify spots which are already verified
         */
        if ($spot['wassigned']) {
            if ($spot['keyid'] == 7) {
                // KeyID 7 has a special meaning, it defines a self-signed spot and requires a hashcash.
                $signingMethod = static::SPOTSIGN_V2;
            } else {
                $signingMethod = static::SPOTSIGN_V1;
            }

            switch ($signingMethod) {
                case static::SPOTSIGN_V1:
                    // The signature this header is signed with.
                    $signature = $this->util->spotUnprepareBase64($spot['headersign']);

                    // Make sure the key specified is an actual known key.
                    if (isset($rsaKeys[$spot['keyid']])) {
                        if ($spot['keyid'] == 2 && $spot['filesize'] = 999 && strlen($spot['selfsignedpubkey']) > 50) {
                            // Check personal dispose message.
                            $signature        = $this->util->spotUnprepareBase64($spot['headersign']);
                            $userSignedHash   = sha1(
                                '<' . $spot['messageid'] . '>',
                                false
                            );
                            $spot['verified'] = (substr($userSignedHash, 0, 4) === '0000');
                            if ($spot['verified']) {
                                $userRsaKey = array(
                                    2 => array(
                                        'modulo'   => $spot['selfsignedpubkey'],
                                        'exponent' => 'AQAB'
                                    )
                                );
                                if ($this->spotSigning->verifySpotHeader($spot, $signature, $userRsaKey)) {
                                    $spot['spotterid'] = $this->util->calculateSpotterId($spot['selfsignedpubkey']);
                                }
                            }
                        } else {
                            $spot['verified'] = $this->spotSigning->verifySpotHeader(
                                $spot,
                                $signature,
                                $rsaKeys
                            );
                        }
                    }
                    break;
                case static::SPOTSIGN_V2:
                    // The signature this header is signed with.
                    $signature = $this->util->spotUnprepareBase64($spot['headersign']);

                    $userSignedHash   = sha1(
                        '<' . $spot['messageid'] . '>',
                        false
                    );
                    $spot['verified'] = (substr($userSignedHash, 0, 4) === '0000');

                    //Create a fake RSA keyarray so we can validate it using our standard infrastructure.
                    if ($spot['verified']) {
                        $userRsaKey = array(
                            7 => array(
                                'modulo'   => $spot['selfsignedpubkey'],
                                'exponent' => 'AQAB'
                            )
                        );

                        /**
                         * We cannot use this as a full measure to check the spot's validness yet,
                         * because at least one Spotnet client feeds us invalid data for now.
                         */
                        if ($this->spotSigning->verifySpotHeader($spot, $signature, $userRsaKey)) {
                            // The users' public key (modulo) is posted in the header, lets try this.
                            $spot['spotterid'] = $this->util->calculateSpotterId($spot['selfsignedpubkey']);
                        }
                    }

                    break;
            }

            /**
             * Even more recent spots, contain the users' full publickey
             * in the header. This allows us to uniquely identify and verify
             * the poster of the spot.
             * Try to extract this information.
             */
            if (($spot['verified']) && (!empty($spot['user-signature'])) && (!empty($spot['selfsignedpubkey']))) {
                // Extract the public key.
                $spot['spotterid'] = $this->util->calculateSpotterId($spot['selfsignedpubkey']);
                $spot['user-key']  = array(
                    'modulo'   => $spot['selfsignedpubkey'],
                    'exponent' => 'AQAB'
                );

                // The spot contains the signature in the header of the spot.
                $spot['verified'] = $this->spotSigning->verifyFullSpot($spot);
            }
        }

        /**
         * We convert the title and other fields to UTF8, we cannot
         * do this any earlier because it would break the RSA signature.
         */
        if (($spot !== false) && ($spot['verified'])) {
            $spot['title']  = utf8_encode($spot['title']);
            $spot['poster'] = utf8_encode($spot['poster']);
            $spot['tag']    = utf8_encode($spot['tag']);

            // If a spot is in the future, fix it.
            if (time() < $spot['stamp']) {
                $spot['stamp'] = time();
            }
        }

        return $spot;
    }
}
