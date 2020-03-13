<?php

interface Dao_Spot
{
    public function getSpots($ourUserId, $pageNr, $limit, $parsedSearch);

    public function getSpotHeader($msgId);

    public function getFullSpot($messageId, $ourUserId);

    public function updateSpotRating($spotMsgIdList);

    public function updateSpotCommentCount($spotMsgIdList);

    public function updateSpotReportCount($spotMsgIdList);

    public function removeSpots($spotMsgIdList);

    public function markSpotsModerated($spotMsgIdList);

    public function deleteSpotsRetention($retention);

    public function addSpots($spots, $fullSpots = []);

    public function updateSpotInfoFromFull($fullSpot);

    public function addFullSpots($fullSpots);

    public function updateSpot($fullSpot, $editor);

    public function getOldestSpotTimestamp();

    public function matchSpotMessageIds($hdrList);

    public function getSpotCount($sqlFilter);

    public function getSpotCountPerHour($limit);

    public function getSpotCountPerWeekday($limit);

    public function getSpotCountPerMonth($limit);

    public function getSpotCountPerCategory($limit);

    public function removeExtraSpots($messageId);

    public function addPostedSpot($userId, $spot, $fullXml);

    public function expireSpotsFull($expireDays);

    public function isNewSpotMessageIdUnique($messageid);

    public function getMaxMessageTime();

    public function getMaxMessageId($headers);

    public function getQuerystr($extendedFieldList, $additionalTableList, $additionalJoinList, $ourUserId, $criteriaFilter, $sortList, $limit, $offset);
} // Dao_Spot
