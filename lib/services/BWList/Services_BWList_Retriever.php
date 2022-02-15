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
        }
        $xml = simplexml_load_string($items);
        if ($xml == false) {
            echo 'Failed loading XML: ';
            foreach (libxml_get_errors() as $error) {
                echo $error->message.PHP_EOL;
            }

            throw new CorruptBWListException();
        }

        return $items;
    }

    // retrieveExternalList

    /*
     * Retrieve the blacklist
     */
    public function retrieveBlackList($listUrl)
    {
        $result = $this->retrieveExternalList($listUrl);
        $a = [];
        if ($result !== false) {
            // Transform spotnet 1.9.x.x blacklist to list of spotterid's
            $list = new SimpleXMLElement($result);
            foreach ($list as $key) {
                $a[] = (string) $key[0];
            }
            $result = $this->_blackWhiteListDao->updateExternalList($a, 'black');
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
        $a = [];
        if ($result !== false) {
            $list = new SimpleXMLElement($result);
            foreach ($list as $key) {
                $a[] = (string) $key[0];
            }
            $result = $this->_blackWhiteListDao->updateExternalList($a, 'white');
        } // if

        return $result;
    }

    // retrieveWhiteList
} // Services_BWList_Retriever
