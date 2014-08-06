<?php

class Services_ParseCollections_Books extends Services_ParseCollections_Abstract {

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
         * For books we just assume the title starts with the name of the wrtier, a dash, and then
         * the rest.
         *
         * eg:
         *      "Tessa de Loo - Tweelingen"
         *      "Nora Roberts - De villa"
         */
        $title = $this->prepareTitle($this->spot['title']);
        $collInfo = $this->parseYearEpisodeSeason($this->spot);

        $tmpPos = strpos($title, '-');
        if ($tmpPos !== false) {
            /*
             * There is a specific artist
             */
            $title = substr($title, 0, $tmpPos);
        } // if

        if ($collInfo === null) {
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_BOOKS, $this->prepareCollName($title), null, null, null, null, null);
        } else {
            $collInfo->setCatType(Dto_CollectionInfo::CATTYPE_BOOKS);
            if (($tmpPos !== false) && ($tmpPos < strlen($collInfo->getTitle()))) {
                $collInfo->setTitle($this->prepareCollName($title));
            } // if

            return $collInfo;
        } // else
    } // parseSpot()

}
