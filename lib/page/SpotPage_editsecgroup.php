<?php
class SpotPage_editsecgroup extends SpotPage_Abs {
	private $_editSecGroupForm;
	private $_groupId;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editSecGroupForm = $params['editsecgroupform'];
		$this->_groupId = $params['groupid'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');
		
		# editsecgroup resultaat is standaard niet geprobeerd
		$editResult = array();

		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit security groups";
		
		# haal de te editten securitygroup op 
		$secGroup = $spotUserSystem->getSecGroup($this->_groupId);
		
		# als de te wijzigen security group niet gevonden kan worden,
		# geef dan een error
		if ((empty($secGroup)) && (!isset($this->_editSecGroupForm['submitaddgroup']))) {
			$editResult = array('result' => 'failure');
			$formMessages['errors'][] = array('validatesecgroup_groupdoesnotexist', array($this->_groupId));
		} # if
		
		# Bepaal welke actie er gekozen was (welke knop ingedrukt was)
		$formAction = '';
		if (isset($this->_editSecGroupForm['submitaddperm'])) {
			$formAction = 'addperm';
			unset($this->_editSecGroupForm['submitaddperm']);
		} elseif (isset($this->_editSecGroupForm['submitremoveperm'])) {
			$formAction = 'removeperm';
			unset($this->_editSecGroupForm['submitremoveperm']);
		} elseif (isset($this->_editSecGroupForm['submitchangename'])) {
			$formAction = 'changename';
			unset($this->_editSecGroupForm['submitchangename']);
		} elseif (isset($this->_editSecGroupForm['submitaddgroup'])) {
			$formAction = 'addgroup';
			unset($this->_editSecGroupForm['submitaddgroup']);
		} elseif (isset($this->_editSecGroupForm['submitremovegroup'])) {
			$formAction = 'removegroup';
			unset($this->_editSecGroupForm['submitremovegroup']);
		} # if

		# Als er een van de ingebouwde groepen geprobeerd bewerkt te worden, 
		# geef dan ook een error.
		if ((!empty($formAction)) && ($secGroup['id'] < 4)) { 
			$editResult = array('result' => 'failure');
			$formMessages['errors'][] = array('validatesecgroup_cannoteditbuiltin', array($this->_groupId));
		} # if

		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'removegroup' : {
					$spotUserSystem->removeSecGroup($secGroup);
					$editResult = array('result' => 'success');
					
					break;
				} # case 'removegroup'
				
				case 'addperm'	: {
					$formMessages['errors'] = $spotUserSystem->addPermToSecGroup($this->_groupId, $this->_editSecGroupForm);
					
					if (!empty($formMessages['errors'])) {
						$editResult = array('result' => 'failure');
					} else {
						$editResult = array('result' => 'success');
					} # else
					
					break;
				} # case 'addperm' 
				
				case 'removeperm'	: {
					$spotUserSystem->removePermFromSecGroup($this->_groupId,
															$this->_editSecGroupForm);
					$editResult = array('result' => 'success');

					break;
				} # case 'removeparm' 
				
				case 'addgroup' : 
				case 'changename'	: {
					# update het security group record
					$secGroup['name'] = $this->_editSecGroupForm['name'];

					# controleer en repareer alle preferences 
					list ($formMessages['errors'], $secGroup) = $spotUserSystem->validateSecGroup($secGroup);

					if (empty($formMessages['errors'])) {
						# en update de database
						switch($formAction) {
							case 'changename'	: $spotUserSystem->setSecGroup($secGroup); break;
							case 'addgroup'		: $spotUserSystem->addSecGroup($secGroup); break;
						} # switch
						
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # if

					break;
				} # case 'changename' 
				
			} # switch
		} # if

		#- display stuff -#
		$this->template('editsecgroup', array('securitygroup' => $secGroup,
										    'formmessages' => $formMessages,
											'http_referer' => $this->_editSecGroupForm['http_referer'],
											'editresult' => $editResult));
	} # render
	
} # class SpotPage_editsecgroup
