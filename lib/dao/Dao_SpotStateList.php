<?php

interface Dao_SpotStateList {

	function markAllAsRead($ourUserId);
	function clearDownloadList($ourUserId);
	function cleanSpotStateList();
	function removeFromWatchList($messageid, $ourUserId);
	function addToWatchList($messageid, $ourUserId);
	function addToSeenList($messageid, $ourUserId);
	function addToDownloadList($messageid, $ourUserId);
	
} # Dao_SpotStateList
