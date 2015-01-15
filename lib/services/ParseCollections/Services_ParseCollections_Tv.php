<?php

class Services_ParseCollections_Tv extends Services_ParseCollections_Movies {

    /**
     * Parses an given Spot, and returns an Dto_CollectionInfo object,
     * with all the necessary fields
     *
     * @internal param array $spot
     * @returns Dto_CollectionInfo
     */
    function parseSpot() {
        /*
         * Try to prevent obvious spam from creating useless collections
         */
        if ($this->checkForSpam()) {
            return null;
        } // if

        /*
         * We use the exact same parsing as for Movies, but we do
         * want to use our own collection type id which makes it easier
         * for scrapers and the like
         */
        $collInfo = parent::parseSpot();
        if ($collInfo !== null) {
            $collInfo->setCatType(Dto_CollectionInfo::CATTYPE_TV);
        } // if

        return $collInfo;
    } // parseSpot


} 