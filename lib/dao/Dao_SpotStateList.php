<?php

interface Dao_SpotStateList
{
    public function markAllAsRead($ourUserId);

    public function clearDownloadList($ourUserId);

    public function cleanSpotStateList();

    public function removeFromWatchList($messageid, $ourUserId);

    public function addToWatchList($messageid, $ourUserId);

    public function addToSeenList($messageid, $ourUserId);

    public function addToDownloadList($messageid, $ourUserId);
} // Dao_SpotStateList
