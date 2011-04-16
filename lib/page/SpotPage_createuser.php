<?php
class SpotPage_createuser extends SpotPage_Abs {
	private $_spotForm;
	
	function __construct($db, $settings, $currentUser, $params) {
		parent::__construct($db, $settings, $currentUser);
		$this->_spotForm = $params['spotform'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# creeer een default spotuser zodat het form altijd
		# de waardes van het form kan renderen
		$spotUser = array('username' => '',
						  'firstname' => '',
						  'lastname' => '',
						  'mail' => '');
		
		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: create user";
		
		# Is dit een submit van een form, of nog maar de aanroep?
		if (isset($this->_spotForm['submit'])) {
			# submit unsetten we altijd
			unset($this->_spotForm['submit']);
			
			# valideer de user
			$spotUser = $this->_spotForm;
			$formMessages['errors'] = $spotUserSystem->validateUserRecord($spotUser);
			
			if (empty($formMessages['errors'])) {
				# Creer een private en public key paar voor deze user
				$spotSigning = new SpotSigning();
				$userKey = $spotSigning->createPrivateKey();
				$spotUser['publickey'] = $userKey['public'];
				$spotUser['privatekey'] = $userKey['private'];
				
				# creeer een random password voor deze user
				$spotUser['password'] = substr($spotUserSystem->generateUniqueId(), 1, 9);
				
				# voeg de user toe
				$spotUserSystem->addUser($spotUser);
				
				$formMessages['info'] = array("Added user '" . htmlspecialchars($spotUser['username']) . "' with password: '" . $spotUser['password'] . "'");
			} # else
			
		} # if
		
		#- display stuff -#
		$this->template('createuser', array('createuserform' => $spotUser,
										    'formmessages' => $formMessages));
	} # render
	
} # class SpotPage_createuser
