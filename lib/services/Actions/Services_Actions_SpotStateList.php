<?php

class Services_Actions_SpotStateList
{
    private $_spotStateListDao;

    /*
     * constructor
     */
    public function __construct(Dao_SpotStateList $spotStateListDao)
    {
        $this->_spotStateListDao = $spotStateListDao;
    }

    // ctor

    public function markAllAsRead($ourUserId)
    {
        return $this->_spotStateListDao->markAllAsRead($ourUserId);
    }

    public function clearDownloadList($ourUserId)
    {
        return $this->_spotStateListDao->clearDownloadList($ourUserId);
    }

    public function cleanSpotStateList()
    {
        return $this->_spotStateListDao->cleanSpotStateList();
    }

    public function removeFromWatchList($messageid, $ourUserId)
    {
        return $this->_spotStateListDao->removeFromWatchList($messageid, $ourUserId);
    }

    public function addToWatchList($messageid, $ourUserId)
    {
        return $this->_spotStateListDao->addToWatchList($messageid, $ourUserId);
    }

    public function addToSeenList($messageid, $ourUserId)
    {
        return $this->_spotStateListDao->addToSeenList($messageid, $ourUserId);
    }

    public function addToDownloadList($messageid, $ourUserId)
    {
        return $this->_spotStateListDao->addToDownloadList($messageid, $ourUserId);
    }
} // Services_Actions_SpotStateList
