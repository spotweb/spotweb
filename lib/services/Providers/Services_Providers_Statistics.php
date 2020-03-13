<?php

class Services_Providers_Statistics
{
    private $_oldestSpotAge = null;
    private $_nntpUpdate;
    private $_spotDao;

    private $_svcImageChart;
    private $_svcImageError;

    /*
     * Constructor
     *
     * nntpUpdate is the last time an update of spots has occured
     */
    public function __construct(Dao_Spot $spotDao, Dao_Cache $cacheDao, $nntpUpdate)
    {
        $this->_spotDao = $spotDao;
        $this->_cacheDao = $cacheDao;
        $this->_nntpUpdate = $nntpUpdate;

        $this->_svcImageChart = new Services_Image_Chart();
        $this->_svcImageError = new Services_Image_Error();

        $this->getOldestSpotAge();
    }

    // ctor

    /*
     * Create all statistics available
     */
    public function createAllStatistics()
    {
        foreach ($this->getValidStatisticsLimits() as $limitValue => $limitName) {
            // Reset timelimit
            set_time_limit(120);

            foreach ($this->getValidStatisticsGraphs() as $graphValue => $graphName) {
                $this->renderStatImage($graphValue, $limitValue);
            } // foreach graph
        } // foreach limit
    }

    // createAllStatistics

    /*
     * Returns an chart depending on who asks
     */
    public function renderStatImage($statType, $dateLimit)
    {
        $graphs = $this->getValidStatisticsGraphs();
        $limits = $this->getValidStatisticsLimits();

        /*
         * Requested invalid graphic settings, create an error image
         */
        if ((!isset($graphs[$statType])) || (!isset($limits[$dateLimit]))) {
            return $this->_svcImageError->createErrorImage(400);
        } // if

        switch ($statType) {
            case 'spotsperhour': $data = $this->createStatsPerHour($dateLimit); break;
            case 'spotsperweekday': $data = $this->createStatsPerWeekday($dateLimit); break;
            case 'spotspermonth': $data = $this->createStatsPerMonth($dateLimit); break;
            case 'spotspercategory': $data = $this->createStatsPerCategory($dateLimit); break;
        } // switch

        $data['ttl'] = (24 * 60 * 60);

        return $data;
    }

    // renderStatImage

    /*
     * Create a graphic for Spots Per Hour statistics
     */
    private function createStatsPerHour($dateLimit)
    {
        $title = $this->makeTitle('spotsperhour', $dateLimit);
        $prepData = $this->getStatisticsData('spotsperhour', $dateLimit);
        $legend = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];

        return $this->_svcImageChart->renderChart('bar', $title, $prepData, $legend);
    }

    // createSpotsPerHour

    /*
     * Create a graphic for spots per weekday statistics
     */
    private function createStatsPerWeekday($dateLimit)
    {
        $title = $this->makeTitle('spotsperweekday', $dateLimit);
        $prepData = $this->getStatisticsData('spotsperweekday', $dateLimit);
        $legend = [_('Monday'), _('Tuesday'), _('Wednesday'), _('Thursday'), _('Friday'), _('Saturday'), _('Sunday')];

        return $this->_svcImageChart->renderChart('bar', $title, $prepData, $legend);
    }

    // createStatsPerWeekday

    /*
     * Create a graphic for spots per month statistics
     */
    private function createStatsPerMonth($dateLimit)
    {
        $title = $this->makeTitle('spotspermonth', $dateLimit);
        $prepData = $this->getStatisticsData('spotspermonth', $dateLimit);
        $legend = [_('January'), _('February'), _('March'), _('April'), _('May'), _('June'), _('July'), _('August'), _('September'), _('October'), _('November'), _('December')];

        return $this->_svcImageChart->renderChart('bar', $title, $prepData, $legend);
    }

    // createStatsPerMonth

    /*
     * Create a graphic for spots per category statistics
     */
    private function createStatsPerCategory($dateLimit)
    {
        $title = $this->makeTitle('spotspercategory', $dateLimit);
        $prepData = $this->getStatisticsData('spotspercategory', $dateLimit);
        $legend = [_(SpotCategories::HeadCat2Desc(0)), _(SpotCategories::HeadCat2Desc(1)), _(SpotCategories::HeadCat2Desc(2)), _(SpotCategories::HeadCat2Desc(3))];

        return $this->_svcImageChart->renderChart('3Dpie', $title, $prepData, $legend);
    }

    // createStatsPerCategory

    /*
     * Returns a title to be used in the graphs
     */
    private function makeTitle($statType, $dateLimit)
    {
        $graphs = $this->getValidStatisticsGraphs();
        $limits = $this->getValidStatisticsLimits();

        $title = $graphs[$statType];
        if (!empty($limit)) {
            $title .= ' ('.$limits[$dateLimit].')';
        } // if

        return $title;
    }

    // makeTitle

    /*
     * Returns a list of valid statistics
     */
    public function getValidStatisticsGraphs()
    {
        $graphs = [];
        $graphs['spotspercategory'] = _('Spots per category');
        $graphs['spotsperhour'] = _('Spots per hour');
        $graphs['spotsperweekday'] = _('Spots per weekday');

        if ($this->_oldestSpotAge > 31) {
            $graphs['spotspermonth'] = _('Spots per month');
        } // if

        return $graphs;
    }

    // getValidStatisticsGraphs

    /*
     * Returns a list of statistics
     */
    public function getValidStatisticsLimits()
    {
        $limits = [];
        if ($this->_oldestSpotAge > 365) {
            $limits[''] = _('Everything');
        } // if

        if ($this->_oldestSpotAge > 31) {
            $limits['year'] = _('last year');
        } // if

        if ($this->_oldestSpotAge > 7) {
            $limits['month'] = _('last month');
        } // if

        if ($this->_oldestSpotAge > 1) {
            $limits['week'] = _('last week');
        } // if

        $limits['day'] = _('last 24 hours');

        return $limits;
    }

    // getValidStatisticsLimits

    /*
     * Returns the current oldest spot in the database in number of days
     */
    private function getOldestSpotAge()
    {
        if ($this->_oldestSpotAge === null) {
            $this->_oldestSpotAge = round((time() - $this->_spotDao->getOldestSpotTimestamp()) / 60 / 60 / 24);
        } // if

        return $this->_oldestSpotAge;
    }

    // getOldestSpotAge

    /*
     * A function returning the statistics data we need
     */
    private function getStatisticsData($statType, $dateLimit)
    {
        /*
         * Check whether we can find the resource in the cache
         */
        $resourceid = $statType.'.'.$dateLimit;
        $rs = $this->_cacheDao->getCachedStats($resourceid);

        /*
         * Check to see if the resource is avilable in the cache
         * and whether its not expired
         */
        if (($rs === false) || ((int) $rs['stamp'] < $this->_nntpUpdate)) {
            switch ($statType) {
                case 'spotsperhour': $data = $this->_spotDao->getSpotCountPerHour($dateLimit); break;
                case 'spotsperweekday': $data = $this->_spotDao->getSpotCountPerWeekday($dateLimit); break;
                case 'spotspermonth': $data = $this->_spotDao->getSpotCountPerMonth($dateLimit); break;
                case 'spotspercategory': $data = $this->_spotDao->getSpotCountPerCategory($dateLimit); break;
            } // switch

            /*
             * Make sure we have all valid data
             */
            $preparedData = [];
            foreach ($data as $tmp) {
                $preparedData[(int) $tmp['data']] = (float) $tmp['amount'];
            } // foreach

            /*
             * and store the retrieved raw data into the cache.
             */
            $this->_cacheDao->saveStatsCache($resourceid, $preparedData);
        } else {
            $preparedData = unserialize($rs['content']);
        } // else

        return $preparedData;
    }

    // getStatisticsData
} // Services_Providers_Statistics
