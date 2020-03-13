<?php

class SpotPage_index extends SpotPage_Abs
{
    private $_params;
    private $_action;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        SpotTiming::start('SpotPage_Index::ctor');
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_params = $params;

        /*
         * Make sure only valid actions (either add or remove) are allowd,
         * else perform like no action was given
         */
        if (array_search($this->_params['action'], ['add', 'remove']) === false) {
            $this->_action = '';
        } else {
            $this->_action = $this->_params['action'];
        } // else

        SpotTiming::stop('SpotPage_Index::ctor');
    }

    // ctor

    public function render()
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        // Give an page title
        $this->_pageTitle = _('overview');

        /*
         * Make sure the user has the appropriate permissions
         */
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');

        /*
         * When the user wants to perform a search, it needs specific search rights
         * as well
         */
        if (!empty($this->_params['search'])) {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_search, '');
        } // if

        /*
         * We get a bunch of query parameters, so now change this to the actual
         * search query the user requested including the required sorting
         */
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);

        $svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());
        $parsedSearch = $svcSearchQp->filterToQuery(
            $this->_params['search'],
            [
                'field'     => $this->_params['sortby'],
                'direction' => $this->_params['sortdir'],
            ],
            $this->_currentSession,
            $svcUserFilter->getIndexFilter($this->_currentSession['user']['userid'])
        );

        /*
         * If any specific action was chosen, we perform that as well
         */
        if (isset($parsedSearch['filterValueList'][0]['fieldname']) && $parsedSearch['filterValueList'][0]['fieldname'] == 'Watch') {
            // Make sure the appropriate permissions are set
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_watchlist, '');

            $svcSpotStateListDao = new Services_Actions_SpotStateList($this->_daoFactory->getSpotStateListDao());

            switch ($this->_action) {
                case 'remove': $svcSpotStateListDao->removeFromWatchList($this->_params['messageid'], $this->_currentSession['user']['userid']);

                                  $spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $this->_currentSession);
                                  $spotsNotifications->sendWatchlistHandled($this->_action, $this->_params['messageid']);

                                  break;

                case 'add': $svcSpotStateListDao->addToWatchList($this->_params['messageid'], $this->_currentSession['user']['userid']);

                                  $spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $this->_currentSession);
                                  $spotsNotifications->sendWatchlistHandled($this->_action, $this->_params['messageid']);

                                  break;
                default:;
            } // switch
        } // if

        /*
         * Get the offset from the URL, if none given, we default to zero
         */
        $pageNr = $this->_params['pagenr'];

        /*
         * Actually fetch the spots, we always perform
         * this action even when the watchlist is editted
         */
        $svcProvSpotList = new Services_Providers_SpotList($this->_daoFactory->getSpotDao());
        $spotsTmp = $svcProvSpotList->fetchSpotList(
            $this->_currentSession['user']['userid'],
            $pageNr,
            $this->_currentSession['user']['prefs']['perpage'],
            $parsedSearch
        );

        /*
         * If we are on the first page, we want to pass '-1' as the previous page,
         * so the templates can deduce we are on the first page.
         *
         * If there are no more spots, make sure we don't show
         * the nextpage link
         */
        if ($spotsTmp['hasmore']) {
            $nextPage = $pageNr + 1;
        } else {
            $nextPage = -1;
        } // else
        $prevPage = max($pageNr - 1, -1);

        //- display stuff -#
        $this->template('spots', [
            'spots'        => $spotsTmp['list'],
            'quicklinks'   => $this->_settings->get('quicklinks'),
            'filters'      => $svcUserFilter->getFilterList($this->_currentSession['user']['userid'], 'filter'),
            'nextPage'     => $nextPage,
            'prevPage'     => $prevPage,
            'parsedsearch' => $parsedSearch,
            'data'         => $this->_params['data'], ]);
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__);
    }

    // render()
} // class SpotPage_index
