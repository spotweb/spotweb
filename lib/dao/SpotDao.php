<?php

interface SpotDao {
	public function isSpotMessageIdUnique($messageId);							// isNewSpotMessageIdUnique($messageid);
	public function addPostedSpot($userId, $spot, $fullXml);					// addPostedSpot($userId, $spot, $fullXml);

	public function getSpotMaxMessageId();										// getMaxMessageId()
	public function getSpotMaxMessageTime();									// getMaxMessageTime()
	public function getSpotMinMessageTime();									// getOldestSpotTimestamp()

	public function removeSpotsLaterThan($messageId);							// removeExtraSpots

	public function matchSpotMessageIds($hdrList);								// matchSpotMessageIds

	public function getSpotHeader($messageId);									// getSpotHeader

	public function updateSpotRating($msgIdList);								// updateSpotRating
	public function updateSpotCommentCount($msgIdList);							// updateSpotCommentCount
	public function updateSpotReportCount($msgIdList);							// updateSpotReportCount

	public function addSpots($spots, $fullSpots = array());						// addSpots
	public function removeSpots($msgIdList);									// removeSpots

	public function markSpotsModerated($msgIdList);								// markSpotsModerated
	public function deleteSpotsRetention($retention);							// deleteSpotsRetention
	public function updateSpotInfoFromFull($fullSpot);							// updateSpotInfoFromFull
	public function addFullSpots($fullSpots);									// addFullSpots
	public function expireSpotsFull($expireDays);								// expireSpotsFull

	public function getSpotCountPerHour($limit);								// getSpotCountPerHour
	public function getSpotCountPerWeekday($limit);								// getSpotCountPerWeekday
	public function getSpotCountPerCategory($limit);							// getSpotCountPerCategory
} # SpotDao

