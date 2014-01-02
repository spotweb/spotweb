<?php

class Services_Collections_Create {
    /**
     * @var Dao_Factory
     */
    private $_daoFactory;

    public function __construct(Dao_Factory $daoFacory) {
        $this->_daoFactory = $daoFacory;
    } // ctor

    /**
     * Create collections starting from a specific id, and
     * running until we are at the end
     *
     * @var $startingPoint int
     * @var $cb callable
     */
    public function createCollections($startingPoint, $cb) {
        $increment = 1000;
        /*
         * Create a faked parse search, so we can re-use existing infrastructure
         */
        $parsedSearch = array(
            'sortFields' => array(array('field' => 'id', 'direction' => 'asc')),
            'additionalJoins' => array(),
            'additionalTables' => array(),
            'additionalFields' => array()
        );

        /*
         * Actually fetch the spots
        */
        $svcProvSpotList = new Services_Providers_SpotList($this->_daoFactory->getSpotDao());

        while (true) {
            if ($cb !== null) {
                $cb('start', $startingPoint, $increment);
            } // if

            /**
             * Get the current list of spots
             */
            $parsedSearch['filter'] = ' (s.id > ' . (int) ($startingPoint) . ') ' .
                                      ' AND (s.collectionid IS NULL) ' .
                                      ' AND (s.category <> 2) ' .                       # Games are never made into collections
                                      ' AND (s.category <> 3) ' .                       # applications are neither
//                ' AND (s.messageid = \'P9bqBip2OdsoOhLTgAuDQ@spot.net\') ' .
                                      ' AND (NOT ((s.category = 0) AND (s.subcatz = \'z3|\')))'; # exclude porn as well
            $dbSpotList = $svcProvSpotList->fetchSpotList(0,
                0,
                $increment,
                $parsedSearch);
            $startingPoint += $increment;

            /*
             * Parse the spots and get an collection id for it
             */
            $dbSpotList['list'] = $this->createCollectionsFromList($dbSpotList['list']);
            if (empty($dbSpotList['list'])) {
                if ($cb !== null) {
                    $cb('finish', $startingPoint, $increment);
                } // if

                break ;
            } // if

            /*
             * now update the database
             */
            $dbConnection = $this->_daoFactory->getConnection();
            $dbConnection->beginTransaction();
            foreach($dbSpotList['list'] as $spot) {
                $dbConnection->exec('UPDATE spots SET collectionid = :collectionid WHERE messageid = :messageid',
                    array(
                        ':collectionid' => array($spot['collectionid'], PDO::PARAM_INT),
                        ':messageid' => array($spot['messageid'], PDO::PARAM_INT),
                    ));
            } // foreach
            $dbConnection->commit();

            if ($cb !== null) {
                $cb('finish', $startingPoint, $increment);
            } // if
        } // while

    } // createCollections

    /**
     * Create collections from a list of spot(headers)
     *
     * @param array $spotDbList
     * @return array
     */
    public function createCollectionsFromList(array $spotDbList) {
        /*
         * Loop through all Spots, and try to get the most appropriate
         * parsed title out of it, so we can later reuse that as an unique
         * identifier for the collection info.
         */
        foreach($spotDbList as & $spot) {
            $spot['collectionInfo'] = Services_ParseCollections_Factory::factory($spot)->parseSpot();
        } // foreach
        unset($spot);

        /*
         * Now try to find collection id's in the database for those, and set that collection
         * id to the database
         */
        $spotDbList = $this->_daoFactory->getCollectionsDao()->getCollectionIdList($spotDbList);
        foreach($spotDbList as & $spot) {
            if ($spot['collectionInfo'] != null) {
                $spot['collectionid'] = $spot['collectionInfo']->getId();
            } else {
                $spot['collectionid'] = null;
            } // else
        } // foreach
        unset($spot);

        return $spotDbList;
    } // createCollections

} // class Services_Collections_Create