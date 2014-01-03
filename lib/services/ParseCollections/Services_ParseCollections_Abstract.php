<?php

abstract class Services_ParseCollections_Abstract {
    /**
     * Record holding the spot information
     *
     * @var array
     */
    protected $spot;

    /**
     * @param array $spot
     */
    public function __construct(array $spot) {
        $this->spot = $spot;
    } // ctor

    /**
     * Parses an given Spot, and returns an Dto_CollectionInfo object,
     * with all the necessary fields
     *
     * @internal param array $spot
     * @returns Dto_CollectionInfo
     */
    abstract function parseSpot();

    /**
     * Cleans up an title and lowercases it
     *
     * @param string $title
     * @returns string
     */
    protected function prepareTitle($title) {
        /*
         * Decode HTML entities, this is normally done during
         * Spot parsing, but we have a lot of legacy spots.
         */
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

        /*
         * Replcae common tags and stuff we do not want to do anything with.
         * We allow the string to proceed with a slash, so things like (1920/1020p/ac4) works as well
         */
        $title = preg_replace("/\\b(\\/)?(x264|hdtv|xvid|hd|720p|avchd|bluray|mkvh264aac|1080p|1080i|dutch|repost|basp|" .
                                   "ac3|dts|nederlands|rescan|nl sub|pal|ipad|iphone|psp|mp4|dd5.1|" .
                                   "ntsc|bd50|3d|480p|half sbs|dvd5|dvd9|rental|nl|bollywood|divx|x264)\\b/i", "", $title);

        /*
         * Replace empty parenthesis, might be caused by aboves replacement
         */
        $title = str_replace(array("[]", "()"), "", $title);

        return mb_strtolower($title, 'UTF-8');
    } // prepareTitle

    /**
     * Parses the year/episode/season out of a spot in the
     * generic way.
     *
     * @param $spot
     * @return null|Dto_CollectionInfo
     */
    protected function parseYearEpisodeSeason($spot) {
        $episode = null;
        $season = null;
        $year = null;
        $currentPart = null;
        $totalParts = null;

        $title = $this->prepareTitle($this->spot['title']);

        /*
         * Try to parse the titles
         */
        if (preg_match('/([\*\(\[])[ ]?([0-9]{4})[ ]?([\)\]\*])/', $title, $matches)) {
            /*
             * Blah blah (2013)
             * Blah blah *2013*
             * Blah Twest [2014]
             * The big hit(1998)
             * Clash of the Titans  ( 1981 )
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
        } // if
        if (!empty($matches)) {
            $posYearFound = strpos($title, $matches[0]);
        } else {
            $posYearFound = strlen($title);
        } // else

        /*
         * Try to parse the 'currentpart' and 'totalparts' stuff,
         * basically these are volume x of y kind of information.
         */
        if (preg_match('/[ \)\[\(\*\-,.](disc|disk|dvd|cd|vol|volume|deel|part)[ \(\*\-,.]?([0-9]{1,3})([ \/\-,.]|(van|of|t\/m|v|tm)|[ \(\*\-,.])+([0-9]{1,3})([ \]\*\-,.\)]|$)/', $title, $matches)) {
            /* History channel the universe seizoen 2 dvd 2/5 */
            /* Maria wern fatal contamination dvd 2 van 2 */
            /* Geert mak in europa tv serie deel 3 van 6  */
            /* Testament van de eighties various artists [dvd 1 van 5] */
            /* Piet pienter en bert bibber (deel 12) rescan */
            /* John Denver - Around The World Live(DVDBox)DVD2-5 */
            $currentPart = $matches[2];
            $totalParts = $matches[5];
        } elseif (preg_match('/[ \)\(\*\-]([0-9]{1,3})([ \(\*\/\-,]|(van|of|t\/m))+([0-9]{1,3})([\]\*\-\) ]|$)/', $title, $matches)) {
            /* Last days of ww2 3 weekly episodes (6 van 6) */
            /* Hitler s warriors 4 of 6 udet */
            $currentPart = $matches[1];
            $totalParts = $matches[4];
        } elseif (preg_match('/[ \[\(\*\-,.](disc|disk|dvd|cd|vol|volume|deel|part)[ \(\*\-,.]?([0-9]{1,3})([ \]\*\-,.\)]|$)/', $title, $matches)) {
            $totalParts = null;
            $currentPart = $matches[2];
        } // else if
        if (!empty($matches)) {
            $posPartOfFound = strpos($title, $matches[0]);
        } else {
            $posPartOfFound = strlen($title);
        } // else

        /*
         * try to parse the episode stuff
         */
        if (preg_match('/[ \(\*\-,.][sS]([0-9]{1,2})[ \-,.]?[e]([0-9]{1,2})([ \*\-,.\)]|$)/', $title, $matches)) {
            /* Goede Tijden Slechte Tijden - S24E67 Dinsdag 03-12-2013 RTL Lounge */
            $season = $matches[1];
            $episode = $matches[2];
        } elseif (preg_match('/[ \(\*\-,.][s]([0-9]{1,2})[ \/\-,.]?([d]|dvd)([0-9]{1,2})([ \*\-,.\)]|$)/', $title, $matches)) {
            /* Beverly hills 90210 s7d4 */
            /* Seaquest dsv s1/d2 */
            $season = $matches[1];
            $currentPart = $matches[3];
        } elseif (preg_match('/[ \-,.](season|seizoen|s)[ \-,.]([0-9]{1,4})[ \-,.]?(episode|ep|aflevering|afl)[ \-,.]([0-9]{1,5})([ \-,.]|$)/', $title, $matches)) {
            /* "Goede Tijden, Slechte Tijden Seizoen 24 Aflevering 4811 02-12-2013 Repost" */
            $season = $matches[2];
            $episode = $matches[4];
        } elseif (preg_match('/[ \-,.](episode|ep|aflevering|afl)[ \-,.]([0-9]{1,5})[ \-,.]?(season|seizoen|s)[ \-,.]([0-9]{1,4})([ \-,.]|$)/', $title, $matches)) {
            /* "Sons of Anarchy Episode 12 Season 6 Released Dec 3th 2013" */
            $episode = $matches[2];
            $season = $matches[4];
        } elseif (preg_match('/[ \(\-,.](season|seizoen|serie|s)[ \-,.]{0,3}([0-9]{1,4})([ \-,.]|$)/', $title, $matches)) {
            /*
             * United States of Tara S03
             * Star Trek Voyager - Seizoen 3,
             * monogatari series second season - 22 [720p][aac] [deadfish]
             * the good wife s5 disc 2 nl subs
             * George gently (season 3 complete)
             */
            $season = $matches[2];
            $episode = null;
        } elseif (preg_match('/[ \-,.](episode|ep|aflevering|afl|epsiode|week|nr)[ \-,.]*([0-9]{1,5})([ \-,.]|$)/', $title, $matches)) {
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
        } // elseif


        if ($season == null && $episode == null && $year == null && $currentPart == null && $totalParts == null) {
            return null;
        } else {
            if (!empty($matches)) {
                $posSeasonFound = strpos($title, $matches[0]);
            } else {
                $posSeasonFound = strlen($title);
            } // else

            $titleStr = substr($title, 0, min($posYearFound, $posSeasonFound, $posPartOfFound));
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_MOVIES,
                                            $this->prepareCollName($titleStr),
                                            $season,
                                            $episode,
                                            $year,
                                            $currentPart,
                                            $totalParts);
        } // else
    } // parseYearEpisodeSeason

    /**
     * Cleans up an collection name
     *
     * @param string $collName
     * @returns string Cleaned up collection name
     */
    protected function prepareCollName($collName) {
        $tmpName = mb_convert_encoding($collName, 'UTF-8', 'UTF-8');
        $tmpName = str_replace(array(
                                    '.',
                                    ':',            // Remove any colons
                                    '-',
                                    '_',
                                    '=',
                               ),
                               ' ',
                               $tmpName);
        $tmpName = preg_replace('/\s+/', ' ', $tmpName);
        $tmpName = trim($tmpName, " \t\n\r\0\x0B-=");
        $tmpName = mb_strtolower($tmpName, 'UTF-8');
        $tmpName = mb_strtoupper(mb_substr($tmpName, 0, 1)) . mb_substr($tmpName, 1);

        return $tmpName;
    } // prepareCollName

} // Series_Collections_Abstract