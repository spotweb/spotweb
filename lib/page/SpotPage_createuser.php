<?php
class SpotPage_createuser extends SpotPage_Abs {
	private $_createUserForm;
	
	function __construct($db, $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_createUserForm = $params['createuserform'];
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
		if (isset($this->_createUserForm['submit'])) {
			# submit unsetten we altijd
			unset($this->_createUserForm['submit']);
			
			# valideer de user
			$spotUser = $this->_createUserForm;
			$formMessages['errors'] = $spotUserSystem->validateUserRecord($spotUser);
			
			if (empty($formMessages['errors'])) {
				# Creer een private en public key paar voor deze user
				$spotSigning = new SpotSigning();
				$userKey = $spotSigning->createPrivateKey($this->_settings->get('openssl_cnf_path'));
				$spotUser['publickey'] = $userKey['public'];
				$spotUser['privatekey'] = $userKey['private'];
				
				# creeer een random password voor deze user
				$spotUser['password'] = substr($spotUserSystem->generateUniqueId(), 1, 9);
				
				# voeg de user toe
				$spotUserSystem->addUser($spotUser);
				
				$formMessages['info'] = array("Added user '" . htmlspecialchars($spotUser['username']) . "' with password: '" . $spotUser['password'] . "'");
			} # if
			
		} # if
		
		#- display stuff -#
		$this->template('createuser', array('createuserform' => $spotUser,
										    'formmessages' => $formMessages));
	} # render
	
} # class SpotPage_createuser
