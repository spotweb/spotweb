<?php

interface Dao_UserFilter {

	function deleteFilter($userId, $filterId, $filterType);
	function addFilter($userId, $filter);
	function copyFilterList($srcId, $dstId);
	function removeAllFilters($userId);
	function getFilter($userId, $filterId);
	function getUserIndexFilter($userId);
	function updateFilter($userId, $filter);
	function getPlainFilterList($userId, $filterType);
	function getFilterList($userId, $filterType);
	function getUniqueFilterCombinations();
	function getUsersForFilter($tree, $valuelist);
	
} # Dao_UserFilter
