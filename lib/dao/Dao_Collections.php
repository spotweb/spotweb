<?php

interface Dao_Collections {

    /**
     * Returns a list of collectionId's depending on the list of titles the user
     * passes to this function. Will automatically add those collection titles if
     * necessary.
     *
     * @param $spotList
     * @return array
     */
    function getCollectionIdList(array $spotList);

    function loadCollectionCache(array $titles);
} # Dao_Collections