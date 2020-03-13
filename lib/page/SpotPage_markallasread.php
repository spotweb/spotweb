<?php

class SpotPage_markallasread extends SpotPage_Abs
{
    public function render()
    {
        $result = new Dto_FormResult('success');

        // Check the appropriate permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_mark_spots_asread, '');

        // instantiate an user system
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        // if this is allowed, mark all individual spots as read
        if ($this->_spotSec->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) {
            $svcUserRecord->markAllAsRead($this->_currentSession['user']['userid']);
        } // if

        // never cache this action
        $this->sendExpireHeaders(true);

        // our results are always in json
        $this->sendContentTypeHeader('json');

        // reset the lastvisit and lastread timestamp
        $svcUserRecord->resetReadStamp($this->_currentSession['user']);

        $this->template('jsonresult', ['result' => $result]);
    }

    // render()
} // SpotPage_markallasread
