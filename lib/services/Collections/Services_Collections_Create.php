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
     * Create collections from a list of spot(headers)
     *
     * @param array $spotDbList
     * @return array
     */
    function createCollections(array $spotDbList) {
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