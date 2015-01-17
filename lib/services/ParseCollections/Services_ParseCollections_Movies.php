<?php

class Services_ParseCollections_Movies extends Services_ParseCollections_Abstract {

    /**
     * Parses an given Spot, and returns an Dto_CollectionInfo object,
     * with all the necessary fields
     *
     * @internal param array $spot
     * @returns Dto_CollectionInfo
     */
    function parseSpot() {
        /*
         * We do not create collections for porn
         */
        if (($this->spot['category'] == 0) && ($this->spot['subcatz'] == 'z3|')) {
            return null;
        } // if

        /*
         * Try to prevent obvious spam from creating useless collections
         */
        if ($this->checkForSpam()) {
            return null;
        } // if

        /*
         * Do the basic year/season/episode parsing
         */
        $collInfo = $this->parseYearEpisodeSeason($this->spot);
        if ($collInfo === null) {
            $title = $this->prepareTitle($this->spot['title']);
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MOVIES, $this->prepareCollName($title), null, null, null, null, null);
        } else {
            /*
             * If a season or episode is defined, its a TV serie for us
             */
            if (($this->spot['subcatz'] == 'z1|') || ($collInfo->getSeason() != null) || ($collInfo->getEpisode() != null)) {
                $collInfo->setCatType(Dto_CollectionInfo::CATTYPE_TV);
            } else {
                $collInfo->setCatType(Dto_CollectionInfo::CATTYPE_MOVIES);
            } // else

            return $collInfo;
        } // else
    } // parseSpot
}