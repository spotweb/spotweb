<?php

class Dao_Base_UserFilter implements Dao_UserFilter {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_UserFilterCount object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn) {
		$this->_conn = $conn;
	} # ctor


	/*
	 * Removes a filter and its children recursively
	 */
	function deleteFilter($userId, $filterId, $filterType) {
		$filterList = $this->getFilterList($userId, $filterType);
		foreach($filterList as $filter) {
		
			if ($filter['id'] == $filterId) {
				foreach($filter['children'] as $child) {
					$this->deleteFilter($userId, $child['id'], $filterType);
				} # foreach
			} # if
			
			$this->_conn->modify("DELETE FROM filters WHERE userid = :userid AND id = :filterid",
                array(
                    ':userid' => array($userId, PDO::PARAM_INT),
                    ':filterid' => array($filterId, PDO::PARAM_INT)
                ));
		} # foreach
	} # deleteFilter
	
	/*
	 * Add a filter and its children recursively
	 */
	function addFilter($userId, $filter) {
		$this->_conn->modify("INSERT INTO filters(userid, filtertype, title, icon, torder, tparent, tree, valuelist, sorton, sortorder, enablenotify)
								VALUES(:userid, :filtertype, :title, :icon, :torder, :tparent, :tree, :valuelist, :sorton, :sortorder, :isenablenotify)",
            array(
                ':userid' => array($userId, PDO::PARAM_INT),
                ':filtertype' => array($filter['filtertype'], PDO::PARAM_STR),
                ':title' => array($filter['title'], PDO::PARAM_STR),
                ':icon' => array($filter['icon'], PDO::PARAM_STR),
                ':torder' => array($filter['torder'], PDO::PARAM_INT),
                ':tparent' => array($filter['tparent'], PDO::PARAM_INT),
                ':tree' => array($filter['tree'], PDO::PARAM_STR),
                ':valuelist' => array(implode('&', $filter['valuelist']), PDO::PARAM_STR),
                ':sorton' => array($filter['sorton'], PDO::PARAM_STR),
                ':sortorder' => array($filter['sortorder'], PDO::PARAM_STR),
                ':isenablenotify' => array($filter['enablenotify'], PDO::PARAM_BOOL),
            ));
		$parentId = $this->_conn->lastInsertId('filters');

		foreach($filter['children'] as $tmpFilter) {
			$tmpFilter['tparent'] = $parentId;
			$this->addFilter($userId, $tmpFilter);
		} # foreach
	} # addFilter
	
	/*
	 * Copies the list of filters from one user to another user
	 */
	function copyFilterList($srcId, $dstId) {
		$filterList = $this->getFilterList($srcId, '');
		
		foreach($filterList as $filterItems) {
			$this->addFilter($dstId, $filterItems);
		} # foreach
	} # copyFilterList
	
	/*
	 * Removes all filters for a user
	 */
	function removeAllFilters($userId) {
		$this->_conn->modify("DELETE FROM filters WHERE userid = :userid",
            array(
                ':userid' => array($userId, PDO::PARAM_INT)
            ));
	} # removeAllfilters

	/*
	 * Get a specific filter
	 */
	function getFilter($userId, $filterId) {
		/* Retrieve this specific filter */
		$tmpResult = $this->_conn->arrayQuery("SELECT id,
													  userid,
													  filtertype,
													  title,
													  icon,
													  torder,
													  tparent,
													  tree,
													  valuelist,
													  sorton,
													  sortorder,
													  enablenotify 
												FROM filters 
												WHERE userid = :userid AND id = :filterid",
            array(
                ':userid' => array($userId, PDO::PARAM_INT),
                ':filterid' => array($filterId, PDO::PARAM_INT)
            ));
		if (!empty($tmpResult)) {
			return $tmpResult[0];
		} else {
			return false;
		} # else
	} # getFilter

	/*
	 * Get a specific index filter 
	 */
	function getUserIndexFilter($userId) {
		$tmpResult = $this->_conn->arrayQuery("SELECT id,
													  userid,
													  filtertype,
													  title,
													  icon,
													  torder,
													  tparent,
													  tree,
													  valuelist,
													  sorton,
													  sortorder,
													  enablenotify 
												FROM filters 
												WHERE userid = :userid AND filtertype = 'index_filter'",
            array(
                ':userid' => array($userId, PDO::PARAM_INT)
            ));
		if (!empty($tmpResult)) {
			return $tmpResult[0];
		} else {
			return false;
		} # else
	} # getUserIndexFilter
	
	
	/*
	 * Updates some values of an existing filter
	 */
	function updateFilter($userId, $filter) {
		$tmpResult = $this->_conn->modify("UPDATE filters 
												SET title = :title,
												    icon = :icon,
													torder = :torder,
													tparent = :tparent,
													enablenotify = :isenablenotify
												WHERE userid = :userid AND id = :filterid",
            array(
                ':title' => array($filter['title'], PDO::PARAM_STR),
                ':icon' => array($filter['icon'], PDO::PARAM_STR),
                ':torder' => array($filter['torder'], PDO::PARAM_INT),
                ':tparent' => array($filter['tparent'], PDO::PARAM_INT),
                ':isenablenotify' => array($filter['enablenotify'], PDO::PARAM_BOOL),
                ':userid' => array($userId, PDO::PARAM_STR),
                ':filterid' => array($filter['id'], PDO::PARAM_STR),
            ));
	} # updateFilter

	/* 
	 * Retrieves the filterlist as a flat list (no hierarchy is created)
	 */
	function getPlainFilterList($userId, $filterType) {
		if (empty($filterType)) {
			$filterTypeFilter = '';
		} else {
			$filterTypeFilter = " AND filtertype = 'filter'"; 
		} # else
		
		return $this->_conn->arrayQuery("SELECT id,
											  userid,
											  filtertype,
											  title,
											  icon,
											  torder,
											  tparent,
											  tree,
											  valuelist,
											  sorton,
											  sortorder,
											  enablenotify 
										FROM filters 
										WHERE userid = :userid " . $filterTypeFilter . "
										ORDER BY tparent,torder", /* was: id, tparent, torder */
            array(
                ':userid' => array($userId,PDO::PARAM_INT)
            ));
	} # getPlainFilterList
	
	/*
	 * Retrieves the filter list but formats it in a treelike structure
	 */
	function getFilterList($userId, $filterType) {
		$tmpResult = $this->getPlainFilterList($userId, $filterType);
		$idMapping = array();
		foreach($tmpResult as &$tmp) {
			$idMapping[$tmp['id']] =& $tmp;
		} # foreach

		/* And actually convert the list of filters to an tree */		
		$tree = array();
		foreach($tmpResult as &$filter) {
			if (!isset($filter['children'])) {
				$filter['children'] = array();
			} # if

			/*
			 * The filter values are stored URL encoded, we use
			 * the &-sign to seperate individual filter values
			 */
			$filter['valuelist'] = explode('&', $filter['valuelist']);
			
			if ($filter['tparent'] == 0) {
				$tree[$filter['id']] =& $filter;
			} else {
				$idMapping[$filter['tparent']]['children'][] =& $filter;
			} # else
		} # foreach

		return $tree;
	} # getFilterList
	
	/*
	 * Returns a list of all unique filter combinations
	 */
	function getUniqueFilterCombinations() {
		return $this->_conn->arrayQuery("SELECT tree,valuelist FROM filters GROUP BY tree,valuelist ORDER BY tree,valuelist");
	} # getUniqueFilterCombinations

	/*
	 * Returns the user ids for this filter combination
	 */
	function getUsersForFilter($tree, $valuelist) {
		return $this->_conn->arrayQuery("SELECT title,
		                                        userid,
		                                        enablenotify
		                                   FROM filters
		                                   INNER JOIN users ON (filters.userid = users.id)
		                                   WHERE (NOT users.deleted) AND tree = :tree AND valuelist = :valuelist",
            array(
                ':tree' => array($tree, PDO::PARAM_STR),
                ':valuelist' => array($valuelist, PDO::PARAM_STR)
            ));
	} # getUsersForFilter

} # Dao_UserFilter
