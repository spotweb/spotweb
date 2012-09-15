<?php
class SpotPage_reportpost extends SpotPage_Abs {
	private $_inReplyTo;
	private $_reportForm;
	
	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_reportForm = $params['reportform'];
		$this->_inReplyTo = $params['inreplyto'];
	} # ctor

	function render() {
		$result = new Dto_FormResult('notsubmitted');

		# Check the users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_report_spam, '');
				
		# Create the default report a spot structure
		$report = array('body' => 'This is SPAM!',
						 'inreplyto' => $this->_inReplyTo,
						 'newmessageid' => '',
						 'randomstr' => '');
		
		# set the page title
		$this->_pageTitle = "report: report spot";

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_reportForm['action'];

		if ($formAction == 'post') {
			# Initialize the notification system
			$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);

			# Make sure we always have a fully valid form
			$report = array_merge($report, $this->_reportForm);

			# can we report this spot as spam?
			$spotPosting = new SpotPosting($this->_db, $this->_settings);
			$result = $spotPosting->reportSpotAsSpam($this->_currentSession['user'], $report);
			
			if ($result->isSuccess()) {
				# send a notification
				$spotsNotifications->sendReportPosted($report['inreplyto']);
			} # if
		} # if
		
		#- display stuff -#
		$this->template('spamreport', array('postreportform' => $report,
											'result' => $result));
	} # render	

} # class SpotPage_reportpost

