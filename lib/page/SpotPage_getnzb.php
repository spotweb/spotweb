<?php

class SpotPage_getnzb extends SpotPage_Abs
{
    private $_messageid;
    private $_action;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_messageid = $params['messageid'];
        $this->_action = $params['action'];
    }

    // ctor

    public function render()
    {
        // Check the users' basic rights
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');

        /*
         * If the user has configured download integration, make sure the user has
         * permission for this specific download integration
         */
        if ($this->_action != 'display') {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_download_integration, $this->_action);
        } // if

        /*
         * Create the different NNTP components
         */
        $svcBinSpotReading = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($this->_settings, 'bin'));
        $svcTextSpotReading = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($this->_settings, 'hdr'));
        $svcProvNzb = new Services_Providers_Nzb($this->_daoFactory->getCacheDao(), $svcBinSpotReading);
        $svcProvSpot = new Services_Providers_FullSpot($this->_daoFactory->getSpotDao(), $svcTextSpotReading);

        // We do not want NZB files to be cached on the client
        $this->sendExpireHeaders(true);

        try {
            if ($this->_action == 'display') {
                $this->sendContentTypeHeader('nzb');
            } // if

            $svcActnNzb = new Services_Actions_DownloadNzb($this->_settings, $this->_daoFactory);
            $svcActnNzb->handleNzbAction(
                $this->_messageid,
                $this->_currentSession,
                $this->_action,
                $svcProvSpot,
                $svcProvNzb
            );

            if ($this->_action != 'display') {
                $this->sendContentTypeHeader('json');

                $result = new Dto_FormResult('success');
                $this->template('jsonresult', ['result' => $result]);
            } // if
        } catch (Exception $x) {
            $this->sendContentTypeHeader('json');

            $result = new Dto_FormResult('notsubmitted');
            $result->addError($x->getMessage());
            $this->template('jsonresult', ['result' => $result]);
        } // catch
    }

    // render
} // SpotPage_getnzb
