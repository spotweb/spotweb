<?php

class Services_Actions_SpeedDial
{
    private $_daoFactory;
    private $_spotSec;
    private $_tplHelper;

    public function __construct(Dao_Factory $daoFactory, SpotSecurity $spotSec, SpotTemplateHelper $tplHelper)
    {
        $this->_daoFactory = $daoFactory;
        $this->_spotSec = $spotSec;
        $this->_tplHelper = $tplHelper;
    }

    // ctor

    /*
     * Actually create the SpeedDial image
     */
    public function createSpeedDialImage($userId, $headerServer)
    {
        /*
         * Because the speeddial image shows stuff like last update and amount of new spots,
         * we want to make sure this is not a totally closed system
         */
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');

        /*
         * Initialize the service to get the new spotcount for this user
         */
        $svcCacheNewSpotCount = new Services_Actions_CacheNewSpotCount(
            $this->_daoFactory->getUserFilterCountDao(),
            $this->_daoFactory->getUserFilterDao(),
            $this->_daoFactory->getSpotDao(),
            new Services_Search_QueryParser($this->_daoFactory->getConnection())
        );
        $newSpots = $svcCacheNewSpotCount->getNewCountForFilter($userId, '');

        /*
         * Get the total amount of spots
         */
        $totalSpots = $svcCacheNewSpotCount->getSpotCount('');
        $lastUpdate = $this->_tplHelper->formatDate($this->_daoFactory->getUsenetStateDao()->getLastUpdate(Dao_UsenetState::State_Spots), 'lastupdate');

        $svc_ImageSpeedDial = new Services_Image_SpeedDial();

        return $svc_ImageSpeedDial->createSpeedDial($totalSpots, $newSpots, $lastUpdate);
    }

    // createSpeedDialImage
} // Services_Actions_SpeedDial
