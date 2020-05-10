<?php
/*
 * TODO
 * XXX
 * FIXME
 *
 * * Editten van spots is nog helemaal niet getest
 */

class SpotPage_editspot extends SpotPage_Abs
{
    private $_spotForm;
    private $_messageId;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_spotForm = $params['editspotform'];
        $this->_messageId = $params['messageid'];
    }

    // ctor

    public function render()
    {
        $result = new Dto_FormResult('notsubmitted');

        // check the users' permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_spotdetail, '');

        // and actually retrieve the spot
        $fullSpot = '';

        try {
            $svcActn_GetSpot = new Services_Actions_GetSpot($this->_settings, $this->_daoFactory, $this->_spotSec);
            $fullSpot = $svcActn_GetSpot->getFullSpot($this->_currentSession, $this->_messageId, true);
            $fullSpot = str_replace('[br]', "\n", $fullSpot);
        } catch (Exception $ex) {
            $result->addError($ex->getMessage());
        } // catch

        // and create a nice and shiny page title
        $this->_pageTitle = 'spot: edit spot';

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        $formAction = $this->_spotForm['action'];

        // Only perform certain validations when the form is actually submitted
        if (!empty($formAction)) {
            switch ($formAction) {
                case 'delete':
                    // check permissions
                    $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_delete_spot, '');

                    // assume success
                    $result->setResult('success');

                    // remove the spot from the database
                    $svcSpotEditor = new Services_Posting_Editor($this->_daoFactory, $this->_currentSession);
                    $svcSpotEditor->deleteSpot($this->_messageId);

                    break;
                 // case 'delete'

                case 'edit':
                    // create a fullspot xml from the data entered by the user and the original fullspot
                    $svcSpotEditor = new Services_Posting_Editor($this->_daoFactory, $this->_currentSession);
                    $result = $svcSpotEditor->updateSpotXml($fullSpot, $this->_spotForm);

                    if ($result->isSuccess()) {
                        // update the spot in the database
                        $svcSpotEditor->updateSpot($this->_messageId, $result->getData('spotxml'));
                    } // if

                    break;
                 // case 'edit'
            } // switch
        } // if

        //- display stuff -#
        $this->template('editspot', ['editspotform' => $fullSpot,
            'result'                                => $result, ]);
    }

    // render
}
