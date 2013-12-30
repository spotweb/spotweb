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
        $tmpPos = strpos($this->spot['title'], '-');
        if ($tmpPos === false) {
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MUSIC, $this->prepareCollName($this->spot['title']), null, null, null);
        } else {
            $artist = substr($this->spot['title'], 0, $tmpPos);
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MUSIC, $this->prepareCollName($artist), null, null, null);
        } // else
    }
}