<?php

    require __DIR__.'/includes/form-xmlresult.inc.php';

    if (!$tplHelper->allowed(SpotSecurity::spotsec_edit_settings, '')) {
        $postresult = ['result' => 'failure'];
        $formmessages = ['error' => [_('Access denied')]];
    } else {
        /*
         * Create an artificial NNTP record
         */
        $server = ['host' => '', 'enc' => false, 'port' => 119, 'user' => '', 'pass' => '', 'verifyname' => true];
        $server = array_merge($server, $data);

        $nntpResult = $tplHelper->validateNntpServer($server);
        if (!empty($nntpResult)) {
            $postresult = ['result' => 'failure'];
            $formmessages = ['error' => [$nntpResult]];
        } else {
            $postresult = ['result' => 'success'];
            $formmessages = [];
        } // else
    } // else

    $this->sendContentTypeHeader('xml');
    echo formResult2Xml($postresult, $formmessages, $tplHelper);
