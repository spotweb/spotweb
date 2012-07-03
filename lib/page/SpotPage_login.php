<?php
class SpotPage_login extends SpotPage_Abs {
	private $_loginForm;
	private $_params;
	
	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_loginForm = $params['loginform'];
		$this->_params = $params;
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_login, '');
							  
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

		# bring the form action into the local scope
		$formAction = $this->_loginForm['action'];

		# Is dit een submit van een form, of nog maar de aanroep?
		if (!empty($formAction)) {
			# valideer de user
			$credentials = array_merge($credentials, $this->_loginForm);
			
			$tryLogin = $spotUserSystem->login($credentials['username'], $credentials['password']);
			if (!$tryLogin) {
				/* Create an audit event */
				if ($this->_settings->get('auditlevel') != SpotSecurity::spot_secaudit_none) {
					$spotAudit = new SpotAudit($this->_db, $this->_settings, $this->_currentSession['user'], $this->_currentSession['session']['ipaddr']);
					$spotAudit->audit(SpotSecurity::spotsec_perform_login, 'incorrect user or pass', false);
				} # if
				
				$loginResult = array('result' => 'failure');
			    $formMessages['errors'][] = _('Invalid username or password');
			} else {
				$loginResult = array('result' => 'success');
				$this->_currentSession = $tryLogin;
			} # else
		} else {
			# Als de user al een sessie heeft, voeg een waarschuwing toe
			if ($this->_currentSession['user']['userid'] != $this->_settings->get('nonauthenticated_userid')) {
				$loginResult = array('result' => 'alreadyloggedin');
			} # if
		} # else
		
		#- display stuff -#
		$this->template('login', array('loginform' => $credentials,
									   'formmessages' => $formMessages,
									   'loginresult' => $loginResult,
									   'http_referer' => $this->_loginForm['http_referer'],
									   'data' => $this->_params['data']));
	} # render
	
} # class SpotPage_login
