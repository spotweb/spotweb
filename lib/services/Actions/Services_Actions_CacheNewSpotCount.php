<?php

class Services_Actions_CacheNewSpotCount
{
    private $_userFilterCountDao;
    private $_userFilterDao;
    private $_spotDao;
    private $_queryParser;
    protected $_cachedSpotCount = null;

    /*
     * constructor
     */
    public function __construct(
        Dao_UserFilterCount $userFilterCountDao,
        Dao_UserFilter $userFilterDao,
        Dao_Spot $spotDao,
        Services_Search_QueryParser $queryParser
    ) {
        $this->_userFilterCountDao = $userFilterCountDao;
        $this->_userFilterDao = $userFilterDao;
        $this->_spotDao = $spotDao;
        $this->_queryParser = $queryParser;
    }

    // ctor

    /*
     * Get the new spotcount for a specific filter
     */
    public function getNewCountForFilter($userId, $filterStr)
    {
        /*
         * If necessary, fill the cache
         */
        if ($this->_cachedSpotCount === null) {
            $this->_cachedSpotCount = $this->_userFilterCountDao->getNewCountForFilters($userId);
        } // if

        // Now parse it to an array as we would get when called from a webpage
        parse_str(html_entity_decode($filterStr), $query_params);

        /*
         * We need several items to exist
         */
        $query_tpl = ['search' => ['valuelist' => [], 'value' => []]];
        $query_params = array_merge($query_tpl, $query_params);

        $query_params['search']['valuelist'] = implode('&', $query_params['search']['value']);

        // Make sure we have a tree variable, even if it is an empty one
        if (!isset($query_params['search']['tree'])) {
            $query_params['search']['tree'] = '';
        } // if

        $filterHash = sha1($query_params['search']['tree'].'|'.urldecode($query_params['search']['valuelist']));

        if (isset($this->_cachedSpotCount[$filterHash])) {
            return $this->_cachedSpotCount[$filterHash]['newspotcount'];
        } else {
            return -1;
        } // if
    }

    // getNewCountForFilter

    /*
     * Pre-calculates the amount of new spots
     */
    public function cacheNewSpotCount()
    {
        $statisticsUpdate = [];

        /*
         * Update the filter counts for the users.
         *
         * Basically it compares the lasthit of the session with the lastupdate
         * of the filters. If lasthit>lastupdate, it will store the lastupdate as
         * last counters read, hence we need to do it here and not at the end.
         */
        $this->_userFilterCountDao->updateCurrentFilterCounts();

        /*
         * First we want a unique list of all currently
         * created filter combinations so we can determine
         * its' spotcount
         */
        $filterList = $this->_userFilterDao->getUniqueFilterCombinations();

        /* We add a dummy entry for 'all new spots' */
        $filterList[] = ['id' => 9999, 'userid' => -1, 'filtertype' => 'dummyfilter',
            'title'           => 'NewSpots', 'icon' => '', 'torder' => 0, 'tparent' => 0,
            'tree'            => '', 'valuelist' => 'New:0', 'sorton' => '', 'sortorder' => '', ];

        /*
         * Now get the current number of spotcounts for all
         * filters. This allows us to add to the current number
         * which is a lot faster than just asking for the complete
         * count
         */
        $cachedList = $this->_userFilterCountDao->getCachedFilterCount(-1);

        /*
         * Loop throug each unique filter and try to calculate the
         * total amount of spots
         */
        foreach ($filterList as $filter) {
            // Reset the PHP timeout timer
            set_time_limit(960);

            // Calculate the filter hash
            $filter['filterhash'] = sha1($filter['tree'].'|'.urldecode($filter['valuelist']));
            $filter['userid'] = -1;

            //echo 'Calculating hash for: "' . $filter['tree'] . '|' . $filter['valuelist'] . '"' . PHP_EOL;
            //echo '         ==> ' . $filter['filterhash'] . PHP_EOL;

            // Check to see if this hash is already in the database
            if (isset($cachedList[$filter['filterhash']])) {
                $filter['lastupdate'] = $cachedList[$filter['filterhash']]['lastupdate'];
                $filter['lastvisitspotcount'] = $cachedList[$filter['filterhash']]['currentspotcount'];
                $filter['currentspotcount'] = $cachedList[$filter['filterhash']]['currentspotcount'];
            } else {
                // Apparently a totally new filter
                $filter['lastupdate'] = 0;
                $filter['lastvisitspotcount'] = 0;
                $filter['currentspotcount'] = 0;
            } // else

            /*
             * Now we have to simulate a search. Because we want to
             * utilize existing infrastructure, we convert the filter to
             * a format which can be used in this system
             */
            $strFilter = '&amp;search[tree]='.$filter['tree'];

            $valueArray = explode('&', $filter['valuelist']);
            if (!empty($valueArray)) {
                foreach ($valueArray as $value) {
                    $strFilter .= '&amp;search[value][]='.$value;
                } // foreach
            } // if

            /*
             * Now we will artifficially add the 'stamp' column to the
             * list of parameters. Basically this tells the query
             * system to only query for spots newer than the last
             * update of the filter
             */
            $strFilter .= '&amp;search[value][]=stamp:>:'.$filter['lastupdate'];

            // Now parse it to an array as we would get when called from a webpage
            parse_str(html_entity_decode($strFilter), $query_params);

            /*
             * Create a fake session
             */
            $userSession = [];
            $userSession['user'] = ['lastread' => $filter['lastupdate']];
            $userSession['user']['prefs'] = ['auto_markasread' => false];

            /*
             * And convert the parsed system to an SQL statement and actually run it
             */
            $parsedSearch = $this->_queryParser->filterToQuery($query_params['search'], [], $userSession, []);
            $spotCount = $this->_spotDao->getSpotCount($parsedSearch['filter']);

            /*
             * Because we only ask for new spots, just increase the current
             * amount of spots. This has a slight chance of sometimes missing
             * a spot but it's sufficiently accurate for this kind of importance
             */
            $filter['currentspotcount'] += $spotCount;

            $this->_userFilterCountDao->setCachedFilterCount(-1, [$filter['filterhash'] => $filter]);

            /*
             * Now determine the users wich actually have this filter
             */
            $usersWithThisFilter = $this->_userFilterDao->getUsersForFilter($filter['tree'], $filter['valuelist']);
            foreach ($usersWithThisFilter as $thisFilter) {
                $statisticsUpdate[$thisFilter['userid']][] = ['title' => $thisFilter['title'],
                    'newcount'                                        => $spotCount,
                    'enablenotify'                                    => $thisFilter['enablenotify'], ];
            } // foreach
        } // foreach

        /*
         * We want to make sure all filtercounts are available for all
         * users, hence we make sure all these records do exist
         */
        $this->_userFilterCountDao->createFilterCountsForEveryone();

        return $statisticsUpdate;
    }

    // cacheNewSpotCount

    /*
     * Returns the amount of spots for a specific version
     */
    public function getSpotCount($sqlFilter)
    {
        return $this->_spotDao->getSpotCount($sqlFilter);
    }

    // getSpotCount
} // Services_Actions_CacheNewSpotCount
