<?php

error_reporting(0);
class SpotPage_postspot extends SpotPage_Abs
{
    private $_spotForm;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_spotForm = $params['spotform'];
    }

    // ctor

    public function render()
    {
        // Make sure the result is set to 'not comited' per default
        $result = new Dto_FormResult('notsubmitted');

        // Validate proper permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_post_spot, '');

        // Sportparser is nodig voor het escapen van de random string
        $spotParseUtil = new Services_Format_Util();

        // we need the spotuser system
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        /*
         * Create a default form so we can be sure to always be able
         * to render the form without notices or whatever
         */
        $spot = ['title'   => '',
            'body'         => '',
            'category'     => 0,
            'subcata'      => '',
            'subcatb'      => [],
            'subcatc'      => [],
            'subcatd'      => [],
            'subcatz'      => '',
            'tag'          => '',
            'website'      => '',
            'newmessageid' => '',
            'randomstr'    => '', ];

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        $formAction = $this->_spotForm['action'];

        // set the page title
        $this->_pageTitle = 'spot: post';

        // Make sure all variables are merged with the default form
        $spot = array_merge($spot, $this->_spotForm);

        // If user tried to submit, validate the file uploads
        $nzbFilename = '';
        $imgFilename = '';
        if ($formAction == 'post') {
            $result->setResult('success');

            // Make sure an NZB file was provided
            $uploadHandler = new Services_Providers_FileUpload('newspotform', 'nzbfile');
            if (!$uploadHandler->isUploaded()) {
                $result->addError(_('Please select NZB file'));
            } elseif (!$uploadHandler->success()) {
                $result->addError(_('Invalid NZB file').' ('.$uploadHandler->errorText().')');
            } else {
                $nzbFilename = $uploadHandler->getTempName();
            } // if

            // Make sure an picture was provided
            $uploadHandler = new Services_Providers_FileUpload('newspotform', 'imagefile');
            if (!$uploadHandler->isUploaded()) {
                $result->addError(_('Please select a picture'));
            } elseif (!$uploadHandler->success()) {
                $result->addError(_('Invalid picture').' ('.$uploadHandler->errorText().')');
            } else {
                $imgFilename = $uploadHandler->getTempName();
            } // if
        } // if

        if (($formAction == 'post') && ($result->isSuccess())) {
            // Initialize notificatiesystem
            $spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $this->_currentSession);

            // Make sure we can post this spot, if so, make it happen
            $svcPostSpot = new Services_Posting_Spot($this->_daoFactory, $this->_settings);
            $result = $svcPostSpot->postSpot(
                $svcUserRecord,
                $this->_currentSession['user'],
                $spot,
                $imgFilename,
                $nzbFilename
            );

            if ($result->isSuccess()) {
                $result->addData('user', $this->_currentSession['user']['username']);
                $result->addData('spotterid', $spotParseUtil->calculateSpotterId($this->_currentSession['user']['publickey']['modulo']));

                // en send a notification
                $spotsNotifications->sendSpotPosted($spot);
            } // if
        } // if

        //- display stuff -#
        $this->template('newspot', ['postspotform' => $spot,
            'result'                               => $result, ]);
    }

    // render
} // class SpotPage_postspot
