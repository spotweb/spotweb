<?php

interface Dao_UserFilterCount
{
    public function setCachedFilterCount($userId, $filterHashes);

    public function getNewCountForFilters($userId);

    public function createFilterCountsForEveryone();

    public function getCachedFilterCount($userId);

    public function resetFilterCountForUser($userId);

    public function updateCurrentFilterCounts();

    public function markFilterCountAsSeen($userId);
} // Dao_UserFilterCount
