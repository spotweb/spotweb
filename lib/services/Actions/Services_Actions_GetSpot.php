<?php

class Services_Actions_GetSpot
{
    private $_settings;
    private $_daoFactory;
    private $_spotSec;

    public function __construct(Services_Settings_Container $settings, Dao_Factory $daoFactory, SpotSecurity $spotSec)
    {
        $this->_settings = $settings;
        $this->_daoFactory = $daoFactory;
        $this->_spotSec = $spotSec;
    }

    // ctor

    /*
     * Returns a spot in full including all the information we have available
     */
    public function getFullSpot(array $currentSession, $msgId, $markAsRead)
    {
        // Make sure user has access to the spot
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');

        $svcNntpSpotReading = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($this->_settings, 'hdr'));
        $svcProvFullSpot = new Services_Providers_FullSpot($this->_daoFactory->getSpotDao(), $svcNntpSpotReading);
        $fullSpot = $svcProvFullSpot->fetchFullSpot($msgId, $currentSession['user']['userid']);

        // seen list
        if ($markAsRead) {
            if ($this->_spotSec->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) {
                if ($currentSession['user']['prefs']['keep_seenlist']) {
                    /*
                     * Always update the seen stamp, this is used for viewing new comments
                     * and the likes
                     */
                    $this->_daoFactory->getSpotStateListDao()->addtoSeenList($msgId, $currentSession['user']['userid']);
                } // if
            } // if allowed
        } // if

        return $fullSpot;
    }

    // getFullSpot
} // Services_Actions_GetSpot
