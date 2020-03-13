<?php

class Services_Providers_Comments
{
    private $_commentDao;
    private $_nntpSpotReading;

    /*
     * constructor
     */
    public function __construct(Dao_Comment $commentDao, Services_Nntp_SpotReading $nntpSpotReading)
    {
        $this->_commentDao = $commentDao;
        $this->_nntpSpotReading = $nntpSpotReading;
    }

    // ctor

    /*
     * Callback function to only return verified comments
     */
    private function cbVerifiedOnly($x)
    {
        return $x['verified'];
    }

    // cbVerifiedOnly

    /*
     * Returns a list of commentss
     */
    public function fetchSpotComments($msgId, $prevMsgids, $userId, $start, $length)
    {
        /*
         * Calculate the total amount of comments we want to retrieve
         */
        $totalCommentsNeeded = ($start + $length);

        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        /*
         * Retrieve a list of comments currently in the database, if
         * a full comment already exists we also retrieve it
         */

        $refs = [];
        $refs[$msgId] = 0;

        foreach ($prevMsgids as $spot) {
            $refs[$spot] = 0;
        }

        if (count($refs) == 0) {
            throw new Exception('No msgid specified ');
        }

        $fullComments = $this->_commentDao->getCommentsFull($userId, $refs);

        /*
         * Now we want to know the first comment we haven't retrieved yet, we
         * ignore not verified comments
         */
        $haveFullCount = 0;
        $lastHaveFullOffset = -1;
        $retrievedVerified = 0;
        $fullCommentsCount = count($fullComments);
        for ($i = 0; $i < $fullCommentsCount; $i++) {
            if ($fullComments[$i]['havefull']) {
                $haveFullCount++;
                $lastHaveFullOffset = $i;

                if ($fullComments[$i]['verified']) {
                    $retrievedVerified++;
                } // if
            } // if
        } // for

        /*
         * Retrieve the remaining comments from the NNTP server
         */
        if ($retrievedVerified < $totalCommentsNeeded) {
            /*
             * If we want only part of the comments, we loop till
             * we have just enough comments to satisfy the requested
             * range. We cannot do without the looping because
             * we don't know which comments are verified until
             * they are retrieved
             */
            if (($start > 0) || ($length > 0)) {
                /*
                 * Start retrieving...
                 */
                while (($retrievedVerified < $totalCommentsNeeded) && (($lastHaveFullOffset) < count($fullComments))) {
                    $newComments = [];

                    SpotTiming::start(__FUNCTION__.':nntp:readComments()');
                    //$tempList = $this->_nntpSpotReading->readComments(array_slice($fullComments, $lastHaveFullOffset + 1, $length));

                    /*
                     * We only fetch one comment at a time, to make sure that if the system asks for a range of
                     * comments which cannot be retrieved within the timeout period (eg: 250 comments cannot be
                     * retrieved within 30 seconds), we at least have some comments in the database. This ensures
                     * us that we make some progress at least.
                     */
                    $tempList = $this->_nntpSpotReading->readComments(array_slice($fullComments, $lastHaveFullOffset + 1, 1));
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':nntp:readComments()', [array_slice($fullComments, $lastHaveFullOffset + 1, $length), $start, $length]);

                    $lastHaveFullOffset += 1; // was + $length
                    foreach ($tempList as $comment) {
                        $newComments[] = $comment;
                        if ($comment['verified']) {
                            $retrievedVerified++;
                        } // if
                    } // foreach

                    // add them to the database
                    $this->_commentDao->addFullComments($newComments);
                } // while
            } else {
                $newComments = $this->_nntpSpotReading->getComments(array_slice($fullComments, $lastHaveFullOffset + 1, count($fullComments)));

                // add them to the database
                $this->_commentDao->addFullComments($newComments);
            } // else

            // re-ask the database so we always have the same common format
            $fullComments = $this->_commentDao->getCommentsFull($userId, $refs);
        } // if

        /*
         * Only return verified comments, we are not interested in
         * non-valid comments
         */
        $fullComments = array_filter($fullComments, [$this, 'cbVerifiedOnly']);

        /*
         * Slice the array so we only retrieve which comments were asked.
         */
        if (($start > 0) || ($length > 0)) {
            $fullComments = array_slice($fullComments, $start, $length);
        } // if

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$msgId, $start, $length]);

        return $fullComments;
    }

    // fetchSpotComments()
} // Services_Providers_Comments
