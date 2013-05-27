<?php

interface Dao_ModeratedRingBuffer {

    /**
     * @param array $messageIds
     * @return boolean
     */
    function addToRingBuffer(array $messageIds);

    /**
     * @param array $messageIds
     * @return array
     */
    function matchAgainst(array $messageIds);

    /**
     * @return void
     */
    function deleteOldest();

} # interface Dao_ModeratedRingBuffer
