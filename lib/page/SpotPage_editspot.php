<?php
class SpotPage_editspot extends SpotPage_Abs {
	private $_spotForm;
	private $_messageId;

		function __construct(Dao_Factory $daoFactory, Services_Settings_Base $settings, $currentSession, $params) {
				parent::__construct($daoFactory, $settings, $currentSession);
		$this->_spotForm = $params['editspotform'];
		$this->_messageId = $params['messageid'];
	} # ctor

	function render() {
		$result = new Dto_FormResult('notsubmitted');

		# check the users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_spotdetail, '');

		# and actually retrieve the spot
		$fullSpot = '';
		try {
			$svcActn_GetSpot = new Services_Actions_GetSpot($this->_settings, $this->_daoFactory, $this->_spotSec);
			$fullSpot = $svcActn_GetSpot->getFullSpot($this->_currentSession, $this->_messageId, true);
			$fullSpot = str_replace("[br]", "\n", $fullSpot);
		} catch (Exception $ex) {
			$result->addError($ex->getMessage());
		} # catch

		# and create a nice and shiny page title
		$this->_pageTitle = "spot: edit spot";

		/*
		 * bring the forms' action into the local scope for
		 * easier access
		 */
		$formAction = $this->_spotForm['action'];

		# Only perform certain validations when the form is actually submitted
		if (!empty($formAction)) {
			switch($formAction) {
				case 'delete' : {
					# check permissions
					$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_delete_spot, '');
					# assume success
					$result->setResult('success');
					# remove the spot from the database
					$svcSpotRecord = new Services_Spot_Record($this->_daoFactory, $this->_currentSession);
					$svcSpotRecord->deleteSpot($this->_messageId);
					break;
				} # case 'delete'

				case 'edit' : {
					# create a fullspot xml from the data entered by the user and the original fullspot
					$svcSpotRecord = new Services_Spot_Record($this->_daoFactory, $this->_currentSession);
					$result = $svcSpotRecord->updateSpotXml($fullSpot, $this->_spotForm);
					if ($result->isSuccess()) {
						# update the spot in the database
						$svcSpotRecord->updateSpot($this->_messageId, $result->getData('spotxml'));
					}
					break;
				} # case 'edit'
			} # switch
		} # if

		#- display stuff -#
		$this->template('editspot', array('editspotform' => $fullSpot,
						'result' => $result));
	} # render

}
