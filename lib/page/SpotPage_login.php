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
							  
		# creeer een default credentials zodat het form altijd
		# de waardes van het form kan renderen
		$credentials = array('username' => '',
						  'password' => '');

		# login verzoek was standaard niet geprobeerd
		$loginResult = array();
		
		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: login";
		
		# Is dit een submit van een form, of nog maar de aanroep?
		if (isset($this->_loginForm['submit'])) {
			# submit unsetten we altijd
			unset($this->_loginForm['submit']);
			
			# valideer de user
			$credentials = array_merge($credentials, $this->_loginForm);
			
			$tryLogin = $spotUserSystem->login($credentials['username'], $credentials['password']);
			if (!$tryLogin) {
				$loginResult = array('result' => 'failure');
			} else {
				$loginResult = array('result '=> 'success');
				$this->_currentSession = $tryLogin;
			} # else
		} else {
			# Als de user al een sessie heeft, voeg een waarschuwing toe
			if ($this->_currentSession['user']['userid'] != SPOTWEB_ANONYMOUS_USERID) {
				$loginResult = array('result' => 'alreadyloggedin');
			} # if
		} # else
		
		#- display stuff -#
		$this->template('login', array('loginform' => $credentials,
									   'formmessages' => $formMessages,
									   'loginresult' => $loginResult));
	} # render
	
} # class SpotPage_login
