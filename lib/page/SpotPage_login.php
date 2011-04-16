<?php
class SpotPage_login extends SpotPage_Abs {
	private $_loginForm;
	
	function __construct($db, $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_loginForm = $params['loginform'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# creeer een default spotuser zodat het form altijd
		# de waardes van het form kan renderen
		$spotUser = array('username' => '',
						  'password' => '');
		
		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: login";
		
		# Is dit een submit van een form, of nog maar de aanroep?
		if (isset($this->_loginForm['submit'])) {
			# submit unsetten we altijd
			unset($this->_loginForm['submit']);
			
			# valideer de user
			$credentials = $this->_loginForm;
			
			if (!$spotUserSystem->login($credentials['username'], $credentials['password'])) {
				$formMessages['errors'] = array('Logon failed');
			} else {
				$formMessages['info'] = array('Logon succesful');
			} # else
		} # if
		
		#- display stuff -#
		$this->template('login', array('loginform' => $this->_loginForm,
									   'formmessages' => $formMessages));
	} # render
	
} # class SpotPage_login
