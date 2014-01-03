<?php

class Services_ParseCollections_Music extends Services_ParseCollections_Abstract {

    /**
     * Parses an given Spot, and returns an Dto_CollectionInfo object,
     * with all the necessary fields
     *
     * @internal param array $spot
     * @returns Dto_CollectionInfo
     */
    function parseSpot() {
        /*
         * For music we just assume the title starts with the name of the artist, a dash, and then
         * the rest.
         *
         * eg:
         *      Dodie Stevens - The Ultimate Collection
         *      Tomes - Greatest Hits
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
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MUSIC, $this->prepareCollName($title), null, null, null, null, null);
        } else {
            $collInfo->setCatType(Dto_CollectionInfo::CATTYPE_MUSIC);
            $collInfo->setTitle($this->prepareCollName($title));

            return $collInfo;
        } // else
    }
}