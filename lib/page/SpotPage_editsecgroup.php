<?php

class SpotPage_editsecgroup extends SpotPage_Abs
{
    private $_editSecGroupForm;
    private $_groupId;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_editSecGroupForm = $params['editsecgroupform'];
        $this->_groupId = $params['groupid'];
    }

    // ctor

    public function render()
    {
        $result = new Dto_FormResult('notsubmitted');

        // Make sure the user has the appropriate rights
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');

        // Instantiate the user record system
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        // set the page title
        $this->_pageTitle = 'spot: edit security groups';

        /*
         * Retrieve the requested group and merge results
         */
        if ($this->_groupId != 9999) {
            $this->_editSecGroupForm = array_merge($svcUserRecord->getSecGroup($this->_groupId), $this->_editSecGroupForm);
        } // if

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        $formAction = $this->_editSecGroupForm['action'];

        // Did the user submit already or are we just rendering the form?
        if (!empty($formAction)) {
            switch ($formAction) {
                case 'removegroup':
                    $result = $svcUserRecord->removeSecGroup($this->_groupId);
                    break;
                 // case 'removegroup'

                case 'addperm':
                    $result = $svcUserRecord->addPermToSecGroup($this->_groupId, $this->_editSecGroupForm);
                    break;
                 // case 'addperm'

                case 'removeperm':
                    $result = $svcUserRecord->removePermFromSecGroup(
                        $this->_groupId,
                        $this->_editSecGroupForm
                    );
                    break;
                 // case 'removeparm'

                case 'setallow':
                case 'setdeny':
                    $this->_editSecGroupForm['deny'] = (bool) ($formAction == 'setdeny');

                    $result = $svcUserRecord->setDenyForPermFromSecGroup(
                        $this->_groupId,
                        $this->_editSecGroupForm
                    );
                    break;
                 // case 'setallow' / 'setdeny'

                case 'addgroup':
                    $result = $svcUserRecord->addSecGroup($this->_editSecGroupForm['name']);
                    break;
                 // 'addgroup'

                case 'changename':
                    $result = $svcUserRecord->setSecGroup($this->_groupId, $this->_editSecGroupForm['name']);
                    break;
                 // case 'changename'
            } // switch
        } // if

        //- display stuff -#
        $this->template('editsecgroup', ['securitygroup' => $this->_editSecGroupForm,
            'result'                                     => $result,
            'http_referer'                               => $this->_editSecGroupForm['http_referer'], ]);
    }

    // render
} // class SpotPage_editsecgroup
