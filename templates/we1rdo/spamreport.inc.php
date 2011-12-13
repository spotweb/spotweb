<?php
if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	$this->sendContentTypeHeader('xml');
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
} 

if (empty($postresult)) {
	if (isset($formmessages)) {
		include "includes/form-messages.inc.php"; 
	} # if
}