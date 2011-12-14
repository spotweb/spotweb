<?php
	include 'includes/form-xmlresult.inc.php';

	if (!$tplHelper->allowed(SpotSecurity::spotsec_edit_settings, '')) {
		$postresult = array('result' => 'failure');
		$formmessages = array('error' => array(_('Access denied')));
	} else {
		/* 
		 * Create an artificial NNTP record
		 */
		$server = array('host' => '', 'enc' => false, 'port' => 119, 'user' => '', 'pass' => '');
		$server = array_merge($server, $data);

		$nntpResult = $tplHelper->validateNntpServer($server);
		if (!empty($nntpResult)) {
			$postresult = array('result' => 'failure');
			$formmessages = array('error' => array($nntpResult));
		} else {
			$postresult = array('result' => 'success');
			$formmessages = array();
		} # else
	} # else
		
	$this->sendContentTypeHeader('xml');
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
