<?php

interface Dao_ModeratedRingBuffer
{
    /**
     * @param array $messageIds
     *
     * @return bool
     */
    public function addToRingBuffer(array $messageIds);

    /**
     * @param array $messageIds
     *
     * @return array
     */
    public function matchAgainst(array $messageIds);

    /**
     * @return void
     */
    public function deleteOldest();
} // interface Dao_ModeratedRingBuffer
