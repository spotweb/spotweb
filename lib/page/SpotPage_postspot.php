<?php
class SpotPage_postspot extends SpotPage_Abs {
	private $_spotForm;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_spotForm = $params['spotform'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Validate proper permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_post_spot, '');
							  
		# Sportparser is nodig voor het escapen van de random string
		$spotParser = new SpotParser();
		
		# spot signing is nodig voor het RSA signen van de spot en dergelijke
		$spotSigning = Services_Signing_Base::newServiceSigning();
		
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
		
		# postspot verzoek was standaard niet geprobeerd
		$postResult = array();
		
		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_spotForm['action'];

		# zet de page title
		$this->_pageTitle = "spot: post";

		# Make sure the anonymous user and reserved usernames cannot post content
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		if (!$spotUser->allowedToPost($this->_currentSession['user'])) {
			$postResult = array('result' => 'notloggedin');

			$formAction = '';
		} # if

		# zorg er voor dat alle variables ingevuld zijn
		$spot = array_merge($spot, $this->_spotForm);

		# If user tried to submit, validate the file uploads
		if ($formAction == 'post') {
			# Make sure an NZB file was provided
			if ((!isset($_FILES['newspotform'])) || ($_FILES['newspotform']['error']['nzbfile'] != UPLOAD_ERR_OK)) {
				$formMessages['errors'][] = _('Please select NZB file');
				$postResult = array('result' => 'failure');

				$formAction = '';
			} # if

			# Make sure an imgae file was provided
			if ((!isset($_FILES['newspotform'])) || ($_FILES['newspotform']['error']['imagefile'] != UPLOAD_ERR_OK)) {
				$formMessages['errors'][] = _('Please select a picture');
				$postResult = array('result' => 'failure');

				$formAction = '';
			} # if
		
			# Make sure the subcategorie are in the proper format
			if ((is_array($spot['subcata'])) || (is_array($spot['subcatz'])) || (!is_array($spot['subcatb'])) || (!is_array($spot['subcatc'])) || (!is_array($spot['subcatd']))) { 
				$formMessages['errors'][] = _('Invalid subcategories given ');
				$postResult = array('result' => 'failure');

				$formAction = '';
			} # if				
		} # if

		if ($formAction == 'post') {
			# Notificatiesysteem initialiseren
			$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);

			# en creer een grote lijst met spots
			$spot['subcatlist'] = array_merge(
										array($spot['subcata']), 
										$spot['subcatb'], 
										$spot['subcatc'], 
										$spot['subcatd']
									);

			# vraag de users' privatekey op
			$this->_currentSession['user']['privatekey'] = 
				$spotUser->getUserPrivateRsaKey($this->_currentSession['user']['userid']);
				
			# het messageid krijgen we met <>'s, maar we werken 
			# in spotweb altijd zonder, dus die strippen we
			$spot['newmessageid'] = substr($spot['newmessageid'], 1, -1);
			
			# valideer of we deze spot kunnen posten, en zo ja, doe dat dan
			$spotPosting = new SpotPosting($this->_db, $this->_settings);
			$formMessages['errors'] = 
				$spotPosting->postSpot($this->_currentSession['user'], 
									   $spot,
									   $_FILES['newspotform']['tmp_name']['imagefile'],
									   $_FILES['newspotform']['tmp_name']['nzbfile']);
			
			if (empty($formMessages['errors'])) {
				$postResult = array('result' => 'success',
									'user' => $this->_currentSession['user']['username'],
									'spotterid' => $spotSigning->calculateSpotterId($this->_currentSession['user']['publickey']),
									'body' => $spot['body']);
				$formMessages['info'][] = _('Spot has been successfully uploaded. It can take some time before it is shown');

				# en verstuur een notificatie
				$spotsNotifications->sendSpotPosted($spot);
			} else {
				$postResult = array('result' => 'failure');
			} # else
		} # if
		
		#- display stuff -#
		$this->template('newspot', array('postspotform' => $spot,
								         'formmessages' => $formMessages,
										 'postresult' => $postResult));
	} # render
	
} # class SpotPage_postspot