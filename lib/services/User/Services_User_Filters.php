<?php

class Services_User_Filters
{
    private $_userDao;
    private $_daoFactory;
    private $_settings;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;

        $this->_userDao = $daoFactory->getUserDao();
    }

    // ctor

    /*
     * Retrieves an unformatted filterlist
     */
    public function getPlainFilterList($userId, $filterType)
    {
        return $this->_daoFactory->getUserFilterDao()->getPlainFilterList($userId, $filterType);
    }

    // get PlainFilterList

    /*
     * Retrieves a list of filters (in an hierarchical list)
     */
    public function getFilterList($userId, $filterType)
    {
        return $this->_daoFactory->getUserFilterDao()->getFilterList($userId, $filterType);
    }

    // getFilterList

    /*
     * Retrieves one specific filter
     */
    public function getFilter($userId, $filterId)
    {
        return $this->_daoFactory->getUserFilterDao()->getFilter($userId, $filterId);
    }

    // getFilter

    /*
     * Changes the filter values.
     *
     * For now only the following values might be changed:
     *
     *   * Title
     *   * Order
     *   * Parent
     */
    public function changeFilter($userId, $filterForm)
    {
        $filter = array_merge($this->getFilter($userId, $filterForm['id']), $filterForm);
        list($filter, $result) = $this->validateFilter($filter);

        // No errors found? add it to the datbase
        if ($result->isSuccess()) {
            $this->_daoFactory->getUserFilterDao()->updateFilter($userId, $filter);
        } // if

        return $result;
    }

    // changeFilter

    /*
     * Validates a filter
     */
    public function validateFilter($filter)
    {
        $result = new Dto_FormResult();

        // Remove any spaces
        $filter['title'] = trim(utf8_decode($filter['title']), " \t\n\r\0\x0B");
        $filter['title'] = trim(utf8_decode($filter['title']), " \t\n\r\0\x0B");

        // Make sure a filter name is valid
        if (strlen($filter['title']) < 2) {
            $result->addError(_('Invalid filter name'));
        } // if

        return [$filter, $result];
    }

    // validateFilter

    /*
     * Adds a filter to a user
     */
    public function addFilter($userId, $filter)
    {
        list($filter, $result) = $this->validateFilter($filter);

        // No errors found? add it to the datbase
        if ($result->isSuccess()) {
            $this->_daoFactory->getUserFilterDao()->addFilter($userId, $filter);
        } // if

        return $result;
    }

    // addFilter

    /*
     * Reorder filters
     */
    public function reorderFilters($userId, $filterOrder)
    {
        $orderCounter = 0;
        foreach ($filterOrder as $id => $parent) {
            $spotFilter = $this->getFilter($userId, $id);

            /*
             * If either the order or the filterhierarchy is changed, we
             * need to update the filter
             */
            if (($spotFilter['torder'] != $orderCounter) || ($spotFilter['tparent'] != $parent)) {
                $spotFilter['torder'] = (int) $orderCounter;
                $spotFilter['tparent'] = (int) $parent;
                $this->changeFilter($userId, $spotFilter);
            } // if

            $orderCounter++;
        } // foreach

        return new Dto_FormResult('success');
    }

    // reorderFilters

    /*
     * Retrieves the users' index filter
     */
    public function getIndexFilter($userId)
    {
        /*
         * The users' index filter is usually retrieved two or
         * thee times for the index page, make sure we don't approach
         * the database that many times
         */
        $userIndexFilter = $this->_daoFactory->getUserFilterDao()->getUserIndexFilter($userId);

        if ($userIndexFilter === false) {
            return ['tree' => ''];
        } else {
            return $userIndexFilter;
        } // else
    }

    // getIndexFilter

    /*
     * Flter out all erotic spots on the indx page
     */
    public function setEroticIndexFilter($userId)
    {
        $this->setIndexFilter(
            $userId,
            ['valuelist'       => [],
                'title'        => 'Index filter',
                'torder'       => 999,
                'tparent'      => 0,
                'children'     => [],
                'filtertype'   => 'index_filter',
                'sorton'       => '',
                'sortorder'    => '',
                'enablenotify' => false,
                'icon'         => 'spotweb.png',
                'tree'         => '~cat0_z3', ]
        );
    }

    // setEroticIndexFilter

    /*
     * Add user's index filter
     */
    private function setIndexFilter($userId, $filter)
    {
        // There can only be one
        $this->removeIndexFilter($userId);

        // and actually add the index filter
        $filter['filtertype'] = 'index_filter';
        $this->_daoFactory->getUserFilterDao()->addFilter($userId, $filter);
    }

    // addIndexFilter

    /*
     * Remove an index filter
     */
    public function removeIndexFilter($userId)
    {
        $tmpFilter = $this->_daoFactory->getUserFilterDao()->getUserIndexFilter($userId);

        if (!empty($tmpFilter)) {
            $this->_daoFactory->getUserFilterDao()->deleteFilter($userId, $tmpFilter['id'], 'index_filter');
        } // if
    }

    // removeIndexFilter

    /*
     * Removes a userfilter
     */
    public function removeFilter($userId, $filterId)
    {
        $this->_daoFactory->getUserFilterDao()->deleteFilter($userId, $filterId, 'filter');

        return new Dto_FormResult('success');
    }

    // removeFilter

    /*
     * Removes all existing filters for a user, and reset its
     * filerlist to the one for the system defined anonymous account
     */
    public function resetFilterList($userId)
    {
        // Remove all filters
        $this->_daoFactory->getUserFilterDao()->removeAllFilters($userId);

        // and copy them back from the userlist
        $this->_daoFactory->getUserFilterDao()->copyFilterList($this->_settings->get('nonauthenticated_userid'), $userId);

        return new Dto_FormResult('success');
    }

    // resetFilterList

    /*
     * Set the filterlist as specified
     */
    public function setFilterList($userId, $filterList)
    {
        // remove all existing filters
        $this->_daoFactory->getUserFilterDao()->removeAllFilters($userId);

        // and add the filters from the list
        foreach ($filterList as $filter) {
            $this->_daoFactory->getUserFilterDao()->addFilter($userId, $filter);
        } // foreach
    }

    // setFilterList

    /*
     * Copy the filters from a specific user to be the
     * default filters
     */
    public function setFiltersAsDefault($userId)
    {
        // Remove all filters for the Anonymous user
        $this->_daoFactory->getUserFilterDao()->removeAllFilters($this->_settings->get('nonauthenticated_userid'));

        // and copy them from the specified user to anonymous
        $this->_daoFactory->getUserFilterDao()->copyFilterList($userId, $this->_settings->get('nonauthenticated_userid'));

        return new Dto_FormResult('success');
    }

    // setFiltersAsDefault

    /*
     * Converts a list of filters to an XML record which should
     * be interchangeable
     */
    public function filtersToXml($filterList)
    {
        $svcSearchQP = new Services_Search_QueryParser($this->_daoFactory->getConnection());

        // create the XML document
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;

        $mainElm = $doc->createElement('spotwebfilter');
        $mainElm->appendChild($doc->createElement('version', '1.0'));
        $mainElm->appendChild($doc->createElement('generator', 'SpotWeb v'.SPOTWEB_VERSION));
        $doc->appendChild($mainElm);

        $filterListElm = $doc->createElement('filters');

        foreach ($filterList as $filter) {
            $filterElm = $doc->createElement('filter');

            $filterElm->appendChild($doc->createElement('id', $filter['id']));
            $filterElm->appendChild($doc->createElement('title', $filter['title']));
            $filterElm->appendChild($doc->createElement('icon', $filter['icon']));
            $filterElm->appendChild($doc->createElement('parent', $filter['tparent']));
            $filterElm->appendChild($doc->createElement('order', $filter['torder']));
            $filterElm->appendChild($doc->createElement('enablenotify', $filter['enablenotify']));

            /*
             * Now add the tree. We get the list of filters as a tree, but we
             * want to keep the XML as clean as possible so we try to compress it.
             *
             * First we have to extract the tree to a list of selections, strongnots
             * and excludes
             */
            $dynaList = explode(',', $filter['tree']);
            list($categoryList, $strongNotList) = $svcSearchQP->prepareCategorySelection($dynaList);
            $treeList = explode(',', $svcSearchQP->compressCategorySelection($categoryList, $strongNotList));
            $tree = $doc->createElement('tree');
            foreach ($treeList as $treeItem) {
                if (!empty($treeItem)) {
                    // determine what type of element this is
                    $treeType = 'include';
                    if ($treeItem[0] == '~') {
                        $treeType = 'strongnot';
                        $treeItem = substr($treeItem, 1);
                    } elseif ($treeItem[1] == '!') {
                        $treeType = 'exclude';
                        $treeItem = substr($treeItem, 1);
                    } // else

                    // and create the XML item
                    $treeElm = $doc->createElement('item', $treeItem);
                    $treeElm->setAttribute('type', $treeType);

                    if (!empty($treeItem)) {
                        $tree->appendChild($treeElm);
                    } // if
                } // if
            } // treeItems
            $filterElm->appendChild($tree);

            /*
             * Prepare the filtervalue list to make it usable for the XML
             */
            $tmpFilterValues = explode('&', $filter['valuelist']);
            $filterValueList = [];
            foreach ($tmpFilterValues as $filterValue) {
                $tmpFilter = explode(':', urldecode($filterValue));

                // and create the actual filter
                if (count($tmpFilter) >= 4) {
                    $filterValueList[] = ['fieldname' => $tmpFilter[0],
                        'operator'                    => $tmpFilter[1],
                        'booloper'                    => $tmpFilter[2],
                        'value'                       => implode(':', array_slice($tmpFilter, 3)), ];
                } // if
            } // foreach

            /*
             * Now add the filter items (text searches etc)
             */
            if (!empty($filterValueList)) {
                $valuesElm = $doc->createElement('values');
                foreach ($filterValueList as $filterValue) {
                    // Create the value XML item
                    $itemElm = $doc->createElement('item');
                    $itemElm->appendChild($doc->createElement('fieldname', $filterValue['fieldname']));
                    $itemElm->appendChild($doc->createElement('operator', $filterValue['operator']));
                    $itemElm->appendChild($doc->createElement('booloper', $filterValue['booloper']));
                    $itemElm->appendChild($doc->createElement('value', $filterValue['value']));

                    $valuesElm->appendChild($itemElm);
                } // foreach
                $filterElm->appendChild($valuesElm);
            } // if

            /*
             * Add the sorting items
             */
            if (!empty($filter['sorton'])) {
                $sortElm = $doc->createElement('sort');

                $itemElm = $doc->createElement('item');
                $itemElm->appendChild($doc->createElement('fieldname', $filter['sorton']));
                $itemElm->appendChild($doc->createElement('direction', $filter['sortorder']));

                $sortElm->appendChild($itemElm);
                $filterElm->appendChild($sortElm);
            } // if

            $filterListElm->appendChild($filterElm);
        } // foreach

        $mainElm->appendChild($filterListElm);

        /*
         * Create a new result object
         */
        $result = new Dto_FormResult('success');
        $result->addData('filters', $doc->saveXML());

        return $result;
    }

    // filtersToXml

    /*
     * Translates an XML string back to a list of filters
     */
    public function xmlToFilters($xmlStr)
    {
        $filterList = [];

        /*
         * Parse the XML file
         */
        $xml = @(new SimpleXMLElement($xmlStr));

        // We can only parse version 1.0 of the filters
        if ((string) $xml->version != '1.0') {
            return $filterList;
        } // if

        // and try to process all of the filters
        foreach ($xml->xpath('/spotwebfilter/filters/filter') as $filterItem) {
            $filter['id'] = (string) $filterItem->id;
            $filter['title'] = (string) $filterItem->title;
            $filter['icon'] = (string) $filterItem->icon;
            $filter['tparent'] = (string) $filterItem->parent;
            $filter['torder'] = (string) $filterItem->order;
            $filter['filtertype'] = 'filter';
            $filter['sorton'] = '';
            $filter['sortorder'] = '';
            $filter['tree'] = '';
            $filter['enablenotify'] = (string) $filterItem->enablenotify;
            $filter['children'] = [];

            /*
             * start with the tree items
             */
            $treeStr = '';
            foreach ($filterItem->xpath('tree/item') as $treeItem) {
                $treeType = (string) $treeItem->attributes()->type;
                if ($treeType == 'exclude') {
                    $treeStr .= ',!'.$treeItem[0];
                } elseif ($treeType == 'strongnot') {
                    $treeStr .= ',~'.$treeItem[0];
                } elseif ($treeType == 'include') {
                    $treeStr .= ','.$treeItem[0];
                } // if
            } // foreach

            if (strlen($treeStr) > 1) {
                $treeStr = substr($treeStr, 1);
            } // if

            $filter['tree'] = $treeStr;

            /*
             * now parse the values (textsearches etc)
             */
            $filterValues = [];
            foreach ($filterItem->xpath('values/item') as $valueItem) {
                $filterValues[] = urlencode(
                    (string) $valueItem->fieldname.
                                    ':'.
                                   (string) $valueItem->operator.
                                    ':'.
                                   (string) $valueItem->booloper.
                                   ':'.
                                   (string) $valueItem->value
                );
            } // foreach
            $filter['valuelist'] = $filterValues;

            /*
             * Sorting elements are optional
             */
            if ($filterItem->sort) {
                $filter['sorton'] = (string) $filterItem->sort->item->fieldname;
                $filter['sortorder'] = (string) $filterItem->sort->item->direction;
            } // if

            $filterList[$filter['id']] = $filter;
        } // foreach

        /*
         * Now create a tree out of it. We cannot do this the same way
         * as in SpotDb because we cannot create references to the XPATH
         * function
         */
        foreach ($filterList as $idx => &$filter) {
            if (($filter['tparent'] != 0) && (isset($filterList[$filter['tparent']]))) {
                $filterList[$filter['tparent']]['children'][] = &$filter;
            } // if
        } // foreach

        /*
         * we have to run it in two passes because unsetting it
         * will result in an incorrect result on an nested-nested
         * list
         */
        foreach ($filterList as $idx => &$filter) {
            if (($filter['tparent'] != 0) && (isset($filterList[$filter['tparent']]))) {
                unset($filterList[$filter['id']]);
            } // if
        } // foreach

        /*
         * Create a new result object
         */
        $result = new Dto_FormResult('success');
        $result->addData('filters', $filterList);

        return $result;
    }

    // xmlToFilters
} // class Services_User_Filters
