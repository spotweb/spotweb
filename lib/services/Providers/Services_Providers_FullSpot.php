<?php

class Services_Providers_FullSpot {
	private $_daoFactory;
	private $_nntpSpotReading;

	/*
	 * constructor
	 */
	public function __construct(Dao_Factory $daoFactory, Services_Nntp_SpotReading $nntpSpotReading) {
		$this->_daoFactory = $daoFactory;
		$this->_nntpSpotReading = $nntpSpotReading;
	}  # ctor
	

	/*
	 * Returns a full spot array
	 */
	function fetchFullSpot($msgId, $ourUserId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		/*
		 * First try the database for the spot, because if it
		 * is already cached in the database we don't need
		 * anything else
		 */
		$fullSpot = $this->_daoFactory->getSpotDao()->getFullSpot($msgId, $ourUserId);
		
		if (empty($fullSpot)) {
            /*
             * When we retrieve a fullspot entry but there is no spot entry the join in our DB query
             * causes us to never get the spot, hence we throw this exception
             */
            $spotHeader = $this->_daoFactory->getSpotDao()->getSpotHeader($msgId);
            if (empty($spotHeader)) {
                throw new Exception("Spot is not in our Spotweb database");
            } # if

            /*
             * Retrieve a full loaded spot from the NNTP server
             */
			$newFullSpot = $this->_nntpSpotReading->readFullSpot($msgId);
            if (!empty($newFullSpot)) {

                $this->_daoFactory->getSpotDao()->addFullSpots( array($newFullSpot) );

            } else {
                SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($msgId, $ourUserId, $fullSpot));

                return false;
            } // else

            /*
             * If the title is changed from the header and the full,
             * we need to update the collection info as well
             */
            if ($newFullSpot['title'] != $spotHeader['title']) {
                $newFullSpot['collectionid'] = null;

                /*
                 * Updat the collectionid to match our new title, this
                 * way we know for sure the title matches with the
                 * actual collection
                 */
                $svcCollCreate = new Services_Collections_Create($this->_daoFactory);
                $tmpList = $svcCollCreate->createCollectionsFromList(array($newFullSpot));

                $newFullSpot = $tmpList[0];
            } else {
                $newFullSpot['collectionid'] = $spotHeader['collectionid'];
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
			$this->_daoFactory->getSpotDao()->updateSpotInfoFromFull($newFullSpot);
			
			/*
			 * We ask our DB to retrieve the fullspot again, this ensures
			 * us all information is present and in always the same format
			 */
			$fullSpot = $this->_daoFactory->getSpotDao()->getFullSpot($msgId, $ourUserId);
		} # if

		/*
		 * We always have to parse the full spot because the database
		 * does not contain all information
		 */
		$spotParser = new Services_Format_Parsing();
		$parsedXml = $spotParser->parseFull($fullSpot['fullxml']);
		$fullSpot = array_merge($parsedXml, $fullSpot);

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($msgId, $ourUserId, $fullSpot));

		return $fullSpot;
	} # fetchFullSpot

	
} # Services_Providers_FullSpot
