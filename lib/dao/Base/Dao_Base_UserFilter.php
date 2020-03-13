<?php

class Dao_Base_UserFilter implements Dao_UserFilter
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_UserFilterCount object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Removes a filter and its children recursively
     */
    public function deleteFilter($userId, $filterId, $filterType)
    {
        $filterList = $this->getFilterList($userId, $filterType);
        foreach ($filterList as $filter) {
            if ($filter['id'] == $filterId) {
                foreach ($filter['children'] as $child) {
                    $this->deleteFilter($userId, $child['id'], $filterType);
                } // foreach
            } // if

            $this->_conn->modify(
                'DELETE FROM filters WHERE userid = :userid AND id = :filterid',
                [
                    ':userid'   => [$userId, PDO::PARAM_INT],
                    ':filterid' => [$filterId, PDO::PARAM_INT],
                ]
            );
        } // foreach
    }

    // deleteFilter

    /*
     * Add a filter and its children recursively
     */
    public function addFilter($userId, $filter)
    {
        $this->_conn->modify(
            'INSERT INTO filters(userid, filtertype, title, icon, torder, tparent, tree, valuelist, sorton, sortorder, enablenotify)
								VALUES(:userid, :filtertype, :title, :icon, :torder, :tparent, :tree, :valuelist, :sorton, :sortorder, :isenablenotify)',
            [
                ':userid'         => [$userId, PDO::PARAM_INT],
                ':filtertype'     => [$filter['filtertype'], PDO::PARAM_STR],
                ':title'          => [$filter['title'], PDO::PARAM_STR],
                ':icon'           => [$filter['icon'], PDO::PARAM_STR],
                ':torder'         => [$filter['torder'], PDO::PARAM_INT],
                ':tparent'        => [$filter['tparent'], PDO::PARAM_INT],
                ':tree'           => [$filter['tree'], PDO::PARAM_STR],
                ':valuelist'      => [implode('&', $filter['valuelist']), PDO::PARAM_STR],
                ':sorton'         => [$filter['sorton'], PDO::PARAM_STR],
                ':sortorder'      => [$filter['sortorder'], PDO::PARAM_STR],
                ':isenablenotify' => [$filter['enablenotify'], PDO::PARAM_BOOL],
            ]
        );
        $parentId = $this->_conn->lastInsertId('filters');

        foreach ($filter['children'] as $tmpFilter) {
            $tmpFilter['tparent'] = $parentId;
            $this->addFilter($userId, $tmpFilter);
        } // foreach
    }

    // addFilter

    /*
     * Copies the list of filters from one user to another user
     */
    public function copyFilterList($srcId, $dstId)
    {
        $filterList = $this->getFilterList($srcId, '');

        foreach ($filterList as $filterItems) {
            $this->addFilter($dstId, $filterItems);
        } // foreach
    }

    // copyFilterList

    /*
     * Removes all filters for a user
     */
    public function removeAllFilters($userId)
    {
        $this->_conn->modify(
            'DELETE FROM filters WHERE userid = :userid',
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );
    }

    // removeAllfilters

    /*
     * Get a specific filter
     */
    public function getFilter($userId, $filterId)
    {
        /* Retrieve this specific filter */
        $tmpResult = $this->_conn->arrayQuery(
            'SELECT id,
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
												WHERE userid = :userid AND id = :filterid',
            [
                ':userid'   => [$userId, PDO::PARAM_INT],
                ':filterid' => [$filterId, PDO::PARAM_INT],
            ]
        );
        if (!empty($tmpResult)) {
            return $tmpResult[0];
        } else {
            return false;
        } // else
    }

    // getFilter

    /*
     * Get a specific index filter
     */
    public function getUserIndexFilter($userId)
    {
        $tmpResult = $this->_conn->arrayQuery(
            "SELECT id,
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
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );
        if (!empty($tmpResult)) {
            return $tmpResult[0];
        } else {
            return false;
        } // else
    }

    // getUserIndexFilter

    /*
     * Updates some values of an existing filter
     */
    public function updateFilter($userId, $filter)
    {
        $tmpResult = $this->_conn->modify(
            'UPDATE filters 
												SET title = :title,
												    icon = :icon,
													torder = :torder,
													tparent = :tparent,
													enablenotify = :isenablenotify
												WHERE userid = :userid AND id = :filterid',
            [
                ':title'          => [$filter['title'], PDO::PARAM_STR],
                ':icon'           => [$filter['icon'], PDO::PARAM_STR],
                ':torder'         => [$filter['torder'], PDO::PARAM_INT],
                ':tparent'        => [$filter['tparent'], PDO::PARAM_INT],
                ':isenablenotify' => [$filter['enablenotify'], PDO::PARAM_BOOL],
                ':userid'         => [$userId, PDO::PARAM_STR],
                ':filterid'       => [$filter['id'], PDO::PARAM_STR],
            ]
        );
    }

    // updateFilter

    /*
     * Retrieves the filterlist as a flat list (no hierarchy is created)
     */
    public function getPlainFilterList($userId, $filterType)
    {
        if (empty($filterType)) {
            $filterTypeFilter = '';
        } else {
            $filterTypeFilter = " AND filtertype = 'filter'";
        } // else

        return $this->_conn->arrayQuery('SELECT id,
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
										WHERE userid = :userid '.$filterTypeFilter.'
										ORDER BY tparent,torder', /* was: id, tparent, torder */
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]);
    }

    // getPlainFilterList

    /*
     * Retrieves the filter list but formats it in a treelike structure
     */
    public function getFilterList($userId, $filterType)
    {
        $tmpResult = $this->getPlainFilterList($userId, $filterType);
        $idMapping = [];
        foreach ($tmpResult as &$tmp) {
            $idMapping[$tmp['id']] = &$tmp;
        } // foreach

        /* And actually convert the list of filters to an tree */
        $tree = [];
        foreach ($tmpResult as &$filter) {
            if (!isset($filter['children'])) {
                $filter['children'] = [];
            } // if

            /*
             * The filter values are stored URL encoded, we use
             * the &-sign to seperate individual filter values
             */
            $filter['valuelist'] = explode('&', $filter['valuelist']);

            if ($filter['tparent'] == 0) {
                $tree[$filter['id']] = &$filter;
            } else {
                $idMapping[$filter['tparent']]['children'][] = &$filter;
            } // else
        } // foreach

        return $tree;
    }

    // getFilterList

    /*
     * Returns a list of all unique filter combinations
     */
    public function getUniqueFilterCombinations()
    {
        return $this->_conn->arrayQuery('SELECT tree,valuelist FROM filters GROUP BY tree,valuelist ORDER BY tree,valuelist');
    }

    // getUniqueFilterCombinations

    /*
     * Returns the user ids for this filter combination
     */
    public function getUsersForFilter($tree, $valuelist)
    {
        return $this->_conn->arrayQuery(
            'SELECT title,
		                                        userid,
		                                        enablenotify
		                                   FROM filters
		                                   INNER JOIN users ON (filters.userid = users.id)
		                                   WHERE (NOT users.deleted) AND tree = :tree AND valuelist = :valuelist',
            [
                ':tree'      => [$tree, PDO::PARAM_STR],
                ':valuelist' => [$valuelist, PDO::PARAM_STR],
            ]
        );
    }

    // getUsersForFilter
} // Dao_UserFilter
