<?php

class Services_Providers_SpotList
{
    private $_spotDao;

    /*
     * constructor
     */
    public function __construct(Dao_Spot $spotDao)
    {
        $this->_spotDao = $spotDao;
    }

    // ctor

    /*
     * Returns a list of spots
     */
    public function fetchSpotList($ourUserId, $start, $limit, $parsedSearch)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        /*
         * Actually fetch the spots from the database
         */
        $spotResults = $this->_spotDao->getSpots($ourUserId, $start, $limit, $parsedSearch);

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, []);

        return $spotResults;
    }

    // fetchSpotList()
} // Services_Providers_SpotList
