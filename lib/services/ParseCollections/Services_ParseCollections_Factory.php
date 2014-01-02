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
                if ($spot['subcatz'] == 'z2|') {
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
