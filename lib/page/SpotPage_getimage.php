<?php

class SpotPage_getimage extends SpotPage_Abs
{
    private $_messageid;
    private $_image;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_messageid = $params['messageid'];
        $this->_image = $params['image'];
    }

    // ctor

    public function render()
    {
        // Check users' permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');

        $settings_nntp_hdr = $this->_settings->get('nntp_hdr');

        // Did the user request an SpeedDial image?
        if (isset($this->_image['type']) && $this->_image['type'] == 'speeddial') {
            $svcActn_SpeedDial = new Services_Actions_SpeedDial($this->_daoFactory, $this->_spotSec, $this->_tplHelper);
            $data = $svcActn_SpeedDial->createSpeedDialImage($this->_currentSession['user']['userid'], $settings_nntp_hdr['host']);
        } elseif (isset($this->_image['type']) && $this->_image['type'] == 'statistics') {
            /* Check whether the user has view statistics permissions */
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statistics, '');

            $graph = (isset($this->_image['graph'])) ? $this->_image['graph'] : false;
            $limit = (isset($this->_image['limit'])) ? $this->_image['limit'] : false;

            // init
            $svcPrv_Stats = new Services_Providers_Statistics(
                $this->_daoFactory->getSpotDao(),
                $this->_daoFactory->getCacheDao(),
                $this->_daoFactory->getUsenetStateDao()->getLastUpdate(Dao_UsenetState::State_Spots)
            );
            $data = $svcPrv_Stats->renderStatImage($graph, $limit);
        } elseif (isset($this->_image['type']) && $this->_image['type'] == 'avatar') {
            // Check users' permissions
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, 'avatar');

            $providerSpotImage = new Services_Providers_CommentImage(new Services_Providers_Http($this->_daoFactory->getCacheDao()));
            $data = $providerSpotImage->fetchGravatarImage($this->_image);
        } else {
            $svc_nntpnzb_engine = Services_Nntp_EnginePool::pool($this->_settings, 'bin');

            /*
             * Retrieve the full spot, we need it to be able to retrieve the image
             */
            $svcActn_GetSpot = new Services_Actions_GetSpot($this->_settings, $this->_daoFactory, $this->_spotSec);
            $fullSpot = $svcActn_GetSpot->getFullSpot($this->_currentSession, $this->_messageid, false);

            /*
             * Actually retrieve the image
             */
            $providerSpotImage = new Services_Providers_SpotImage(
                new Services_Providers_Http($this->_daoFactory->getCacheDao()),
                new Services_Nntp_SpotReading($svc_nntpnzb_engine),
                $this->_daoFactory->getCacheDao()
            );
            $data = $providerSpotImage->fetchSpotImage($fullSpot);
        } // else

        // Images are allowed to be cached on the client unless the provider explicitly told us not to
        if (isset($data['ttl']) && ($data['ttl'] > 0)) {
            $this->sendExpireHeaders(true);
        } else {
            $this->sendExpireHeaders(false);
        } // else

        header('Content-Type: '.image_type_to_mime_type($data['metadata']['imagetype']));
        header('Content-Length: '.strlen($data['content']));
        echo $data['content'];
    }

    // render
} // SpotPage_getimage
