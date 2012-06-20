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
		$spotSigning = Services_Signing_Base::newServiceSigning();
		
		# creeer een default report
		$report = array('body' => 'This is SPAM!',
						 'inreplyto' => $this->_inReplyTo,
						 'newmessageid' => '',
						 'randomstr' => '');
		
		# reportpost verzoek was standaard niet geprobeerd
		$postResult = array();
		
		# zet de page title
		$this->_pageTitle = "report: report spot";

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_reportForm['action'];

		# Make sure the anonymous user and reserved usernames cannot post content
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		if (!$spotUser->allowedToPost($this->_currentSession['user'])) {
			$postResult = array('result' => 'notloggedin');

			$formAction = '';
		} # if
		
		if ($formAction == 'post') {
			# Notificatiesysteem initialiseren
			$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);

			# zorg er voor dat alle variables ingevuld zijn
			$report = array_merge($report, $this->_reportForm);

			# vraag de users' privatekey op
			$this->_currentSession['user']['privatekey'] = 
				$this->_db->getUserPrivateRsaKey($this->_currentSession['user']['userid']);
			
			# het messageid krijgen we met <>'s, maar we werken 
			# in spotweb altijd zonder, dus die strippen we
			$report['newmessageid'] = substr($report['newmessageid'], 1, -1);
			
			# valideer of we dit report kunnen posten, en zo ja, doe dat dan
			$spotPosting = new SpotPosting($this->_db, $this->_settings);
			$formMessages['errors'] = $spotPosting->reportSpotAsSpam($this->_currentSession['user'], $report);
			
			if (empty($formMessages['errors'])) {
				$postResult = array('result' => 'success');

				# en verstuur een notificatie
				$spotsNotifications->sendReportPosted($report['inreplyto']);
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