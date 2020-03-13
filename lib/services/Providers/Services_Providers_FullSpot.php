<?php

class Services_Providers_FullSpot
{
    private $_spotDao;
    private $_nntpSpotReading;

    /*
     * constructor
     */
    public function __construct(Dao_Spot $spotDao, Services_Nntp_SpotReading $nntpSpotReading)
    {
        $this->_spotDao = $spotDao;
        $this->_nntpSpotReading = $nntpSpotReading;
    }

    // ctor

    /*
     * Returns a full spot array
     */
    public function fetchFullSpot($msgId, $ourUserId)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        /*
         * First try the database for the spot, because if it
         * is already cached in the database we don't need
         * anything else
         */
        $fullSpot = $this->_spotDao->getFullSpot($msgId, $ourUserId);

        if (empty($fullSpot)) {
            /*
             * When we retrieve a fullspot entry but there is no spot entry the join in our DB query
             * causes us to never get the spot, hence we throw this exception
             */
            $spotHeader = $this->_spotDao->getSpotHeader($msgId);
            if (empty($spotHeader)) {
                throw new Exception('Spot is not in our Spotweb database');
            } // if

            /*
             * Retrieve a full loaded spot from the NNTP server
             */
            $newFullSpot = $this->_nntpSpotReading->readFullSpot($msgId);
            if (!empty($newFullSpot)) {
                $this->_spotDao->addFullSpots([$newFullSpot]);
            } else {
                SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$msgId, $ourUserId, $fullSpot]);

                return false;
            } // else

            /*
             * If the current spotterid is empty, we probably now have a spotterid because
             * we have the fullspot.
             *
             * We now update the 'basic' spot information, like the spotterid but also the
             * title. This is necessary because the XML contains better encoding.
             *
             * For example take the title from spot bdZZdJ3gPxTAmSE@spot.net.
             *
             * We cannot use all information from the XML because because some information just
             * isn't present in the XML file
             */
            $this->_spotDao->updateSpotInfoFromFull($newFullSpot);

            /*
             * We ask our DB to retrieve the fullspot again, this ensures
             * us all information is present and in always the same format
             */
            $fullSpot = $this->_spotDao->getFullSpot($msgId, $ourUserId);
        } // if

        /*
         * We always have to parse the full spot because the database
         * does not contain all information
         */
        $spotParser = new Services_Format_Parsing();
        $parsedXml = $spotParser->parseFull($fullSpot['fullxml']);
        $fullSpot = array_merge($parsedXml, $fullSpot);

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$msgId, $ourUserId, $fullSpot]);

        return $fullSpot;
    }

    // fetchFullSpot
} // Services_Providers_FullSpot
