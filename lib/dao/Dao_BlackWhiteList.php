<?php

interface Dao_BlackWhiteList {

	function removeOldList($listUrl,$idtype);
	function updateExternalList($newlist,$idtype);
	function addSpotterToList($spotterId, $ourUserId, $origin, $idType);
	function removeSpotterFromList($spotterId, $ourUserId);
	function getSpotterList($ourUserId);
	function getBlacklistForSpotterId($userId, $spotterId);

} # Dao_BlackWhiteList
