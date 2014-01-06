<?php

class Services_ParseCollections_Factory {

    /**
     * Factory method to return the correct Series_Collection_* instance, depending on the
     * category information of the spot.
     *
     * @param array $spot
     * @returns Services_ParseCollections_Abstract
     */
    public static function factory(array $spot) {
        switch($spot['category']) {
            case 0  : {
                if ($spot['subcatz'] == 'z0|') {
                    if (strpos($spot['subcatd'], 'd6|') !== false) {
                        // Documentaries
                        return new Services_ParseCollections_Documentary($spot);
                    } elseif ($spot['subcata'] == 'a5|') {
                        /* Format epub */
                        return new Services_ParseCollections_Books($spot);
                    } elseif ($spot['subcatd'] == 'd13|') {
                        /*
                         * Make a direct comparison on subcatd, because if a spot
                         * matches another catgory as well (eg both music and family),
                         * it is most likely collectionable anyway
                         */
                        return new Services_ParseCollections_MusicVideos($spot);
                    } // elseif

                    // Movies
                    return new Services_ParseCollections_Movies($spot);
                } elseif ($spot['subcatz'] == 'z1|') {
                    // TV Series
                    return new Services_ParseCollections_Tv($spot);
                } elseif ($spot['subcatz'] == 'z2|') {
                    // Books
                    return new Services_ParseCollections_Books($spot);
                } // elseif

                return new Services_ParseCollections_Movies($spot);
            } // case 0
            case 1  : return new Services_ParseCollections_Music($spot);
            case 2  : return new Services_ParseCollections_Games($spot);

            default : return new Services_ParseCollections_None($spot);
        } // switch
    } // factory

}
