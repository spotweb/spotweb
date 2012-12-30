<?php
class SpotPage_postspot extends SpotPage_Abs {
	private $_spotForm;
	
	function __construct(Dao_Factory $daoFactory, Services_Settings_Base $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_spotForm = $params['spotform'];
	} # ctor

	function render() {
		# Make sure the result is set to 'not comited' per default
		$result = new Dto_FormResult('notsubmitted');
							  
		# Validate proper permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_post_spot, '');
							  
		# Sportparser is nodig voor het escapen van de random string
		$spotParseUtil = new Services_Format_Util();

		# we need the spotuser system
		$svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);
		
		# creeer een default spot zodat het form altijd
		# de waardes van het form kan renderen
		$spot = array('title' => '',
					  'body' => '',
					  'category' => 0,
					  'subcata' => '',
					  'subcatb' => array(),
					  'subcatc' => array(),
					  'subcatd' => array(),
					  'subcatz' => '',
					  'tag' => '',
					  'website' => '',
					  'newmessageid' => '',
					  'randomstr' => '');
		
		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_spotForm['action'];

		# set the page title
		$this->_pageTitle = "spot: post";

		# zorg er voor dat alle variables ingevuld zijn
		$spot = array_merge($spot, $this->_spotForm);

		# If user tried to submit, validate the file uploads
		if ($formAction == 'post') {
			$result->setResult('success');

			# Make sure an NZB file was provided
			if ((!isset($_FILES['newspotform'])) || ($_FILES['newspotform']['error']['nzbfile'] != UPLOAD_ERR_OK)) {
				$result->addError(_('Please select NZB file'));
			} # if

			# Make sure an imgae file was provided
			if ((!isset($_FILES['newspotform'])) || ($_FILES['newspotform']['error']['imagefile'] != UPLOAD_ERR_OK)) {
				$result->addError(_('Please select a picture'));
			} # if
		} # if

		if (($formAction == 'post') && ($result->isSuccess())) {
			# Initialize notificatiesystem
			$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);

			# valideer of we deze spot kunnen posten, en zo ja, doe dat dan
			$svcPostSpot = new Services_Posting_Spot($this->_daoFactory, $this->_settings);
			$result = $svcPostSpot->postSpot($svcUserRecord,
									   $this->_currentSession['user'], 
									   $spot,
									   $_FILES['newspotform']['tmp_name']['imagefile'],
									   $_FILES['newspotform']['tmp_name']['nzbfile']);
			
			if ($result->isSuccess()) { 
				$result->addData('user', $this->_currentSession['user']['username']);
				$result->addData('spotterid', $spotParseUtil->calculateSpotterId($this->_currentSession['user']['publickey']));
				$result->addData('body', $spot['body']);

				# en send a notification
				$spotsNotifications->sendSpotPosted($spot);
			} # if
		} # if
		
		#- display stuff -#
		$this->template('newspot', array('postspotform' => $spot,
										 'result' => $result));
	} # render
	
} # class SpotPage_postspot
