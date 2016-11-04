<?php
/**
 * Copyright [2011] [Mario Mueller]
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

/**
 * Example File
 * @author Mario Mueller <mario.mueller.work@gmail.com>
 * @version 1.0.0
 */
require_once dirname(__FILE__) . '/bootstrap.php';

// Use \Prowl\SecureConnector to make cUrl use SSL
$oProwl = new \Prowl\Connector();

$oMsg = new \Prowl\Message();

// If you have one:
// $oProwl->setProviderKey('MY_PROVIDER_KEY');

try {

	// You can choose to pass a callback
	$oProwl->setFilterCallback(function($sText) {
		return $sText;
	});

	// or set a filter instance:
	// $oFilter = new \Prowl\Security\PassthroughFilterImpl();
	// $oProwl->setFilter($oFilter);

	/*
	 * Both, the closure and the instance, can be passed to the connector
	 * or to each message. Setting it at the connector passes the closure or the instance down
	 * to each message on push() execution - but only if the message has neither of them set.
	 */

	$oProwl->setIsPostRequest(true);
	$oMsg->setPriority(0);

	// You can ADD up to 5 api keys
	// This is a Test Key, please use your own.
	$oMsg->addApiKey('e0bf09a4cc20ae0bcd63b30b19031ef59a458634');
	$oMsg->setEvent('My Event!');

	// These are optional:
	$oMsg->setDescription('My Event description.');
	$oMsg->setApplication('My Custom App Name.');

	$oResponse = $oProwl->push($oMsg);

	if ($oResponse->isError()) {
		print $oResponse->getErrorAsString();
	} else {
		print "Message sent." . PHP_EOL;
		print "You have " . $oResponse->getRemaining() . " Messages left." . PHP_EOL;
		print "Your counter will be resetted on " . date('Y-m-d H:i:s', $oResponse->getResetDate()) . PHP_EOL;
	}
} catch (\InvalidArgumentException $oIAE) {
	print $oIAE->getMessage();
} catch (\OutOfRangeException $oOORE) {
	print $oOORE->getMessage();
}
