<?php

class Services_BWList_Retriever
{
    protected $_blackWhiteListDao;
    protected $_cacheDao;
    protected $_svcPrvHttp;

    /*
     * Constructor
     */
    public function __construct(Dao_BlackWhiteList $blackWhiteListDao, Dao_Cache $cacheDao)
    {
        $this->_blackWhiteListDao = $blackWhiteListDao;
        $this->_cacheDao = $cacheDao;
        $this->_svcPrvHttp = new Services_Providers_Http($cacheDao);
    }

    // ctor

    /*
     * Retrieve a black or whitelist from the ewb
     */
    private function retrieveExternalList($listUrl)
    {
        /*
         * Actually retrieve the list
         */
        list($http_code, $items) = $this->_svcPrvHttp->performCachedGet($listUrl, false, 30 * 60);

        /*
         * If the list didn't modify, that's enough to know
         */
        if ($http_code == 304) {
            return false;
        } elseif (strpos($items, '>')) {
            throw new CorruptBWListException();
        } // else

        /*
         * We've come so far, the list might be valid
         */
        $expItems = explode(chr(10), $items);

        // Perform a very small sanity check on the black/whitelist
        if ((count($expItems) > 5) && (strlen($expItems[0]) < 200)) {
            return $expItems;
        } else {
            throw new CorruptBWListException();
        } // else
    }

    // retrieveExternalList

    /*
     * Retrieve the blacklist
     */
    public function retrieveBlackList($listUrl)
    {
        $result = $this->retrieveExternalList($listUrl);

        if ($result !== false) {
            $result = $this->_blackWhiteListDao->updateExternalList($result, 'black');
        } // if

        return $result;
    }

    // retrieveBlackList

    /*
     * Retrieve the whitelist
     */
    public function retrieveWhiteList($listUrl)
    {
        $result = $this->retrieveExternalList($listUrl);

        if ($result !== false) {
            $result = $this->_blackWhiteListDao->updateExternalList($result, 'white');
        } // if

        return $result;
    }

    // retrieveWhiteList
} // Services_BWList_Retriever
