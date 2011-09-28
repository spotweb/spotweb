<?php
class SpotPage_reportpost extends SpotPage_Abs {
	private $_inReplyTo;
	private $_reportForm;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_reportForm = $params['reportform'];
		$this->_inReplyTo = $params['inreplyto'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_report_spam, '');
				
		# Sportparser is nodig voor het escapen van de random string
		$spotParser = new SpotParser();
		
		# spot signing is nodig voor het RSA signen van de spot en dergelijke
		$spotSigning = new SpotSigning();
		
		# creeer een default report
		$report = array('body' => 'Dit is SPAM!',
						 'inreplyto' => $this->_inReplyTo,
						 'newmessageid' => '',
						 'randomstr' => '');
		
		# reportpost verzoek was standaard niet geprobeerd
		$postResult = array();
		
		# zet de page title
		$this->_pageTitle = "report: report spot";

		# Als de user niet ingelogged is, dan heeft dit geen zin
		if ($this->_currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) {
			$postResult = array('result' => 'notloggedin');
			unset($this->_reportForm['submit']);
		} # if

		# Zorg er voor dat reserved usernames geen reports kunnen posten
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		if (!$spotUser->validUsername($this->_currentSession['user']['username'])) {
			$postResult = array('result' => 'notloggedin');
			unset($this->_reportForm['submit']);
		} # if
		
		if (isset($this->_reportForm['submit'])) {
			# submit unsetten we altijd
			unset($this->_reportForm['submit']);
			
			# zorg er voor dat alle variables ingevuld zijn
			$report = array_merge($report, $this->_reportForm);

			# vraag de users' privatekey op
			$this->_currentSession['user']['privatekey'] = 
				$this->_db->getUserPrivateRsaKey($this->_currentSession['user']['userid']);
			
			# valideer of we dit report kunnen posten, en zo ja, doe dat dan
			$spotPosting = new SpotPosting($this->_db, $this->_settings);
			$formMessages['errors'] = $spotPosting->reportSpotAsSpam($this->_currentSession['user'], $report);
			
			if (empty($formMessages['errors'])) {
				$postResult = array('result' => 'success',
									'user' => $this->_currentSession['user']['username'],
									'userid' => $spotSigning->calculateUserid($this->_currentSession['user']['publickey']),
									'body' => $report['body']
									);
			} else {
				$postResult = array('result' => 'failure');
			} # else
		} # if
		
		#- display stuff -#
		$this->template('spamreport', array('postreportform' => $report,
											 'formmessages' => $formMessages,
											 'postresult' => $postResult));
	} # render	
} # class SpotPage_reportpost