<?php

interface Dao_Spot {

	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch);
	function getSpotHeader($msgId);
	function getFullSpot($messageId, $ourUserId);
	function updateSpotRating($spotMsgIdList);
	function updateSpotCommentCount($spotMsgIdList);
	function updateSpotReportCount($spotMsgIdList);
	function removeSpots($spotMsgIdList);
	function markSpotsModerated($spotMsgIdList);
	function deleteSpotsRetention($retention);
	function addSpots($spots, $fullSpots = array());
	function updateSpotInfoFromFull($fullSpot);
	function addFullSpots($fullSpots);
	function getOldestSpotTimestamp();
	function matchSpotMessageIds($hdrList);
	function getSpotCount($sqlFilter);
	function getSpotCountPerHour($limit);
	function getSpotCountPerWeekday($limit);
	function getSpotCountPerMonth($limit);
	function getSpotCountPerCategory($limit);
	function removeExtraSpots($messageId);
	function addPostedSpot($userId, $spot, $fullXml);
	function expireSpotsFull($expireDays);
	function isNewSpotMessageIdUnique($messageid);
	function getMaxMessageTime();
	function getMaxMessageId($headers);

} # Dao_Spot

