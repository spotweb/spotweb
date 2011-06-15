<?php
require_once "lib/notifications/prowl/Connector.php";

function prowlNotify($apiKey, $title, $message) {
	$oProwl = new \Prowl\Connector();
	$oMsg = new \Prowl\Message();
	$oMsg->addApiKey($apiKey);
	$oMsg->setApplication('Spotweb');
	$oMsg->setEvent($title);
	$oMsg->setDescription($message);

	$oFilter = new \Prowl\Security\PassthroughFilterImpl();
	$oProwl->setFilter($oFilter);
	$oProwl->setIsPostRequest(true);
	$oResponse = $oProwl->push($oMsg);
} prowlNotify
