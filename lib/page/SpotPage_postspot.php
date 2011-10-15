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
							  
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_post_spot, '');
							  
		# Sportparser is nodig voor het escapen van de random string
		$spotParser = new SpotParser();
		
		# spot signing is nodig voor het RSA signen van de spot en dergelijke
		$spotSigning = new SpotSigning();
		
		# creeer een default spot zodat het form altijd
		# de waardes van het form kan renderen
		$spot = array('title' => '',
					  'body' => '',
					  'category' => 0,
					  'subcatz' => '',
					  'subcatlist' => '',
					  'tag' => '',
					  'website' => '',
					  'newmessageid' => '',
					  'randomstr' => '');
		
		# postspot verzoek was standaard niet geprobeerd
		$postResult = array();
		
		# zet de page title
		$this->_pageTitle = "spot: post spot";

		# Als de user niet ingelogged is, dan heeft dit geen zin
		if ($this->_currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) {
			$postResult = array('result' => 'notloggedin');
			unset($this->_spotForm['submit']);
		} # if

		# Zorg er voor dat reserved usernames geen spots kunnen posten
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		if (!$spotUser->validUsername($this->_currentSession['user']['username'])) {
			$postResult = array('result' => 'notloggedin');
			unset($this->_spotForm['submit']);
		} # if

		if (isset($this->_spotForm['submit'])) {
			# submit unsetten we altijd
			unset($this->_spotForm['submit']);

			# Make sure an NZB file was provided
			if ((isset($_FILES['newspotform'])) && ($_FILES['newspotform']['error']['nzbfile'] != UPLOAD_ERR_OK)) {
				$formMessages['errors'][] = array('postspot_invalidnzb', '(none given)');
				// $xml = file_get_contents($_FILES['filterimport']['tmp_name']);
				unset($this->_spotForm['submit']);
			} # if

			# Make sure an imgae file was provided
			if ((isset($_FILES['newspotform'])) && ($_FILES['newspotform']['error']['imagefile'] != UPLOAD_ERR_OK)) {
				$formMessages['errors'][] = array('postspot_imageinvalid', '(none given)');
				// $xml = file_get_contents($_FILES['filterimport']['tmp_name']);
				unset($this->_spotForm['submit']);
			} # if
			
			# zorg er voor dat alle variables ingevuld zijn
			$spot = array_merge($spot, $this->_spotForm);

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
									'userid' => $spotSigning->calculateUserid($this->_currentSession['user']['publickey']),
									'body' => $spot['body']);
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