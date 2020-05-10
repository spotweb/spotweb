<?php

interface Dao_BlackWhiteList
{
    public function removeOldList($listUrl, $idtype);

    public function updateExternalList($newlist, $idtype);

    public function addSpotterToList($spotterId, $ourUserId, $origin, $idType);

    public function removeSpotterFromList($spotterId, $ourUserId);

    public function getSpotterList($ourUserId);

    public function getBlacklistForSpotterId($userId, $spotterId);
} // Dao_BlackWhiteList
