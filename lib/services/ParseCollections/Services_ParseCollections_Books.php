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
         * For books we just assume the title starts with the name of the wrtier, a dash, and then
         * the rest.
         *
         * eg:
         *      "Tessa de Loo - Tweelingen"
         *      "Nora Roberts - De villa"
         */
        $title = $this->prepareTitle($this->spot['title']);
        $tmpPos = strpos($title, '-');
        if ($tmpPos === false) {
            /*
             * we might be dealing with some sort of date in the article, lets
             * use that then.
             */
            if (preg_match('/[ \-,.\(\[]([0-9]{1,2})[\-.\/ ]([0-9]{2})[\-.\/ ]([0-9]{2,4})([\)\] \-,.]|$)/', $title, $matches)) {
                /* Ad vrijdag 10 06 2011 */
                $episode = $matches[1];
                $season = $matches[2];
                $year = $matches[3];
                $bookTitle = substr($title, 0, strpos($title, $matches[0]));

                return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_BOOKS, $this->prepareCollName($bookTitle), $season, $episode, $year);
            } else {
                return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_BOOKS, $this->prepareCollName($title), null, null, null);
            } // else
        } else {
            $author = substr($title, 0, $tmpPos);
            return new Dto_CollectionInfo(Dto_CollectionInfo::CATTYPE_BOOKS, $this->prepareCollName($author), null, null, null, null);
        } // else

        return null;
    } // parseSpot()

}
