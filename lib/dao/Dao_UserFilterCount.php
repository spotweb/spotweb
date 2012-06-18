<?php

interface Dao_UserFilterCount {

	function setCachedFilterCount($userId, $filterHashes);
	function getNewCountForFilters($userId);
	function createFilterCountsForEveryone();
	function getCachedFilterCount($userId);
	function resetFilterCountForUser($userId);
	function updateCurrentFilterCounts();
	function markFilterCountAsSeen($userId);
	
} # Dao_UserFilterCount
