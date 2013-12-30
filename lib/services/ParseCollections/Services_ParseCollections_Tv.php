<?php

class Services_ParseCollections_Tv extends Services_ParseCollections_Abstract {

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
        $title = strtolower($this->spot['title']);

        /*
         * Try to parse the season part of the title. This can be either in the form of S1, S01, 1, 2012, etc.
         *
         * Test strings:
         *      "Miss Fisher's Murder Mysteries S02E06"
         *      "Goede Tijden, Slechte Tijden Seizoen 24 Aflevering 4811 02-12-2013 Repost"
         *      "WWE.Friday.Night.Smackdown.2013.12.06.720p.HDTV.x264-WEmpire"
         */
        $episode = null;
        $season = null;
        $year = null;

        if (preg_match('/[ \-,.][sS]([0-9]{1,2})[ \-,.]?[e]([0-9]{1,2})([ \-,.]|$)/', $title, $matches) ||
            preg_match('/[ \-,.]([0-9]{1,2})\/([0-9]{1,2})([ \-,.]|$)/', $title, $matches)) {
            /* Goede Tijden Slechte Tijden - S24E67 Dinsdag 03-12-2013 RTL Lounge */
            /* Goede Tijden Slechte Tijden - 01/24 */
            $season = $matches[1];
            $episode = $matches[2];
        } elseif (preg_match('/[ \-,.](season|seizoen|s)[ \-,.]([0-9]{1,4})[ \-,.]?(episode|ep|aflevering|afl)[ \-,.]([0-9]{1,5})([ \-,.]|$)/', $title, $matches)) {
            /* "Goede Tijden, Slechte Tijden Seizoen 24 Aflevering 4811 02-12-2013 Repost" */
            $season = $matches[2];
            $episode = $matches[4];
        } elseif (preg_match('/[ \-,.](episode|ep|aflevering|afl)[ \-,.]([0-9]{1,5})[ \-,.]?(season|seizoen|s)[ \-,.]([0-9]{1,4})([ \-,.]|$)/', $title, $matches)) {
            /* "Sons of Anarchy Episode 12 Season 6 Released Dec 3th 2013" */
            $episode = $matches[2];
            $season = $matches[4];
        } elseif (preg_match('/[ \-,.](season|seizoen|serie|s)[ \-,.]{0,3}([0-9]{1,4})([ \-,.]|$)/', $title, $matches)) {
            /*
             * United States of Tara S03
             * Star Trek Voyager - Seizoen 3,
             * monogatari series second season - 22 [720p][aac] [deadfish]
             * the good wife s5 disc 2 nl subs
             */
            $season = $matches[2];
            $episode = null;
        } elseif (preg_match('/[ \-,.](episode|ep|aflevering|afl|epsiode)[ \-,.]*([0-9]{1,5})([ \-,.]|$)/', $title, $matches)) {
            /*
             * beschuldigd afl 65
             * heartless city (2013) tv serie "asian - south korea". == eng subs == episode 11 ==
             * van god los iii afl.1-2
             * the blacklist episode 10
             */
            $season = null;
            $episode = $matches[2];
        } elseif (preg_match('/[\[ \-,.]([0-9]{4})[\-.\/ ]([0-9]{1,2})[\-.\/ ]([0-9]{1,2})([\] \-,.]|$)/', $title, $matches)) {
            /* "WWE.Friday.Night.Smackdown.2013.12.06.720p.HDTV.x264-WEmpire" */
            /* WWE.Friday.Night.Smackdown.2013.12.6.HDTV.x264-DX */
            /* craig ferguson 2013 12 02 betty white hdtv x264-batv */
            /* rtl 7 darts; players championship finals [20131201] */
            $year = $matches[1];
            $season = $matches[2];
            $episode = $matches[3];
        } elseif (preg_match('/[ \-,.\(\[]([0-9]{1,2})[\-.\/ ]([0-9]{2})[\-.\/ ]([0-9]{2,4})([\)\] \-,.]|$)/', $title, $matches)) {
            /* THE BOLD AND THE BEAUTIFUL Vrijdag 06-12-2013 */
            /* NBA RS: 05-12-13 Memphis Grizzlies @ Los angeles Clippers */
            /* nederland zingt 2013 - dvd 23 (23.11.2013 - 01.12.2013) */
            /* reportage 1-12-2013 */
            $season = $matches[1];
            $episode = $matches[2];
            $year = $matches[3];
        } elseif (preg_match('/[ \-,.](18|19|20)([0-9]{2})([ \-,.]|$)/', $title, $matches)) {
            /*
             * blah blah 1920
             * blah blah 2020
             */
            $episode = null;
            $season = $matches[1] . $matches[2];
        } else {
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_TV, $this->prepareCollName($this->spot['title']), null, null, null);
        } // if

        $titleStr = substr($this->spot['title'], 0, strpos($title, $matches[0]));
        return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_TV, $this->prepareCollName($titleStr), $season, $episode, $year);
    } // parseSpot
}
