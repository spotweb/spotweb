<?php

class SpotPage_blacklistspotter extends SpotPage_Abs
{
    private $_blForm;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_blForm = $params['blform'];
    }

    // ctor

    public function render()
    {
        // Check users' permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_blacklist_spotter, '');

        // Make sure the editresult is set to 'not comitted' per default
        $result = new Dto_FormResult('notsubmitted');

        // Create the default blacklist information
        $blackList = ['spotterid' => '',
            'origin'              => '', ];

        // set the page title
        $this->_pageTitle = 'report: blacklist spotter';

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        if (isset($this->_blForm['action'])) {
            $formAction = $this->_blForm['action'];
        } else {
            $formAction = '';
        } // else

        // Instantiate the user system which does the actually heavy lifting
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        if ((!empty($formAction)) && (!$result->isError())) {
            $result->setResult('success');

            // Make sure we have a complete blacklist information
            $blackList = array_merge($blackList, $this->_blForm);

            switch ($formAction) {
                case 'addspotterid':
                    $result->mergeResult($svcUserRecord->addSpotterToList(
                        $this->_currentSession['user'],
                        $blackList['spotterid'],
                        $blackList['origin'],
                        $blackList['idtype']
                    ));

                    break;
                 // case addspotterid

                case 'removespotterid':
                    $result->mergeResult($svcUserRecord->removeSpotterFromList(
                        $this->_currentSession['user'],
                        $blackList['spotterid']
                    ));

                    break;
                 // case removespotterid
            } // switch
        } // if

        //- display stuff -#
        $this->template('jsonresult', ['result' => $result]);
    }

    // render
} // class SpotPage_blacklistspotter
