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
        $tmpPos = strpos($title, '-');
        if ($tmpPos === false) {
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MUSIC, $this->prepareCollName($title), null, null, null, null, null);
        } else {
            $collInfo = $this->parseYearEpisodeSeason($this->spot);
            if ($collInfo === null) {
                $collInfo = new Dto_CollectionInfo(null, null, null, null, null, null, null);
            }

            $artist = substr($title, 0, $tmpPos);
            $collInfo->setCatType(Dto_CollectionInfo::CATTYPE_MUSIC);
            $collInfo->setTitle($this->prepareCollName($artist));
            return $collInfo;
        } // else
    }
}