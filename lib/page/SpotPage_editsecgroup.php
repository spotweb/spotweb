<?php
class SpotPage_editsecgroup extends SpotPage_Abs {
	private $_editSecGroupForm;
	private $_groupId;
	
	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_editSecGroupForm = $params['editsecgroupform'];
		$this->_groupId = $params['groupid'];
	} # ctor

	function render() {
		$result = new Dto_FormResult('notsubmitted');

		# Make sure the user has the appropriate rights
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');

		# Instantiate the SpoUser system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit security groups";
		
		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editSecGroupForm['action'];
		
		# Did the user submit already or are we just rendering the form?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'removegroup' : {
					$result = $spotUserSystem->removeSecGroup($this->_groupId);
					break;
				} # case 'removegroup'
				
				case 'addperm'	: {
					$result = $spotUserSystem->addPermToSecGroup($this->_groupId, $this->_editSecGroupForm);
					break;
				} # case 'addperm' 
				
				case 'removeperm'	: {
					$result = $spotUserSystem->removePermFromSecGroup($this->_groupId,
															$this->_editSecGroupForm);
					break;
				} # case 'removeparm' 
				
				case 'setallow' 	:
				case 'setdeny'		:  {
					$this->_editSecGroupForm['deny'] = (bool) ($formAction == 'setdeny');
				
					$result = $spotUserSystem->setDenyForPermFromSecGroup($this->_groupId,
																$this->_editSecGroupForm);
					break;
				} # case 'setallow' / 'setdeny'
				
				case 'addgroup' 	: {
					$result = $spotUserSystem->addSecGroup($this->_editSecGroupForm['name']); 
					break;
				} # 'addgroup'

				case 'changename'	: {
					$result = $spotUserSystem->setSecGroup($this->_groupId, $this->_editSecGroupForm['name']); 
					break;
				} # case 'changename' 
			} # switch
		} # if

		#- display stuff -#
		$this->template('editsecgroup', array('securitygroup' => $secGroup,
										    'formmessages' => $result,
											'http_referer' => $this->_editSecGroupForm['http_referer']));
	} # render
	
} # class SpotPage_editsecgroup
