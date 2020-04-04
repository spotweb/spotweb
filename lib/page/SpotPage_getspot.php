<?php

class SpotPage_getspot extends SpotPage_Abs
{
    private $_messageid;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_messageid = $params['messageid'];
    }

    // ctor

    public function render()
    {
        // Make sure user has access to the spot
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');

        // and actually retrieve the spot
        $svcActn_GetSpot = new Services_Actions_GetSpot($this->_settings, $this->_daoFactory, $this->_spotSec);
        $fullSpot = $svcActn_GetSpot->getFullSpot($this->_currentSession, $this->_messageid, true);

        // set page title
        $this->_pageTitle = 'spot: '.$fullSpot['title'];

        //- display stuff -#
        $this->template('spotinfo', ['spot' => $fullSpot]);
    }

    // render
} // class SpotPage_getspot
