<?php

interface Dao_UserFilter
{
    public function deleteFilter($userId, $filterId, $filterType);

    public function addFilter($userId, $filter);

    public function copyFilterList($srcId, $dstId);

    public function removeAllFilters($userId);

    public function getFilter($userId, $filterId);

    public function getUserIndexFilter($userId);

    public function updateFilter($userId, $filter);

    public function getPlainFilterList($userId, $filterType);

    public function getFilterList($userId, $filterType);

    public function getUniqueFilterCombinations();

    public function getUsersForFilter($tree, $valuelist);
} // Dao_UserFilter
