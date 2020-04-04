<?php

class SpotPage_logout extends SpotPage_Abs
{
    public function render()
    {
        $result = new Dto_FormResult('notsubmitted');

        // Check users' permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_logout, '');

        // Instanatiate the spotweb user system
        $svcUserAuth = new Services_User_Authentication($this->_daoFactory, $this->_settings);

        // make sure the logout isn't cached
        $this->sendExpireHeaders(true);

        // send the appropriate content-type header
        $this->sendContentTypeHeader('json');

        // and remove the users' session if the user isn't the anonymous one
        if ($svcUserAuth->removeSession($this->_currentSession)) {
            $result->setResult('success');
        } else {
            $result->addError(_('Unable to remove session'));
        } // else

        $this->template('jsonresult', ['result' => $result]);
    }

    // render
} // class SpotPage_logout
