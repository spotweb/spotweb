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
         * Convert the title to lowercase so we don't have to work with different cases
         */
        $year = null;
        $season = null;
        $episode = null;
        $title = $this->prepareTitle($this->spot['title']);

        /*
         * We do not create collections for porn
         */
        if (($this->spot['category'] == 0) && ($this->spot['subcatz'] == 'z3|')) {
            return null;
        } // if


        /*
         * Try to parse the titles
         */
        if (preg_match('/[ \-,.]([\*\(\[])([0-9]{4})([\)\]\*])/', $title, $matches)) {
            /*
             * Blah blah (2013)
             * * Blah blah *2013*
             * Blah Twest [2014]
             */
            $year = $matches[2];
        } elseif (preg_match('/([\(\[])([0-9]{4})([\/\-\.])([0-9]{4})([\)\]])/', $title, $matches)) {
            /*
             * saints and soldiers: airborne creed (2012/2013) pal
             * wild bill (2011/2013) pal
             * jackpot / arme riddere (2011/2013) pal
             */
            $year = $matches[2];
        } elseif (preg_match('/[ \-,.](18|19|20)([0-9]{2})([ \-,.]|$)/', $title, $matches)) {
            /*
             * blah blah 1920
             * blah blah 2020
             */
            $year = $matches[1] . $matches[2];
        } elseif (preg_match('/[ \-,.](aflevering|episode|part|deel)[ \-,.]*([0-9]{1,4})([ \-,.]|$)/', $title, $matches)) {
            $episode = $matches[2];
        } //

        if ($matches != null) {
            $titleStr = substr($title, 0, strpos($title, $matches[0]));
        } else {
            /*
             * Not recognized by this parser, but sometimes TV series
             * are posted as movies, so try those.
             */
            $svcParseTv = new Services_ParseCollections_Tv($this->spot);
            return $svcParseTv->parseSpot();
        } // else

        return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MOVIES, $this->prepareCollName($titleStr), $season, $episode, $year);
    } // parseSpot
}