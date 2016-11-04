<?php
/**
 * Sample file for the boxcar php provider client
 * 
 * @author Russell Smith <russell.smith@ukd1.co.uk>
 * @copyright UKD1 Limited 2010
 * @license licence.txt ISC license
 * @see https://github.com/ukd1/Boxcar
 */

define('API_KEY', 'key');
define('API_SEC', 'secret');
define('YOUR_EMAIL', 'your email');

if (!function_exists('curl_init')) {
	trigger_error('CURL must be enabled for boxcar_api to function', E_USER_ERROR);
}

// load the api class
require_once 'boxcar_api.php';

// this is needed to stop warnings when using the date functions
date_default_timezone_set('Europe/London');

// instantiate a new instance of the boxcar api
$b = new boxcar_api(API_KEY, API_SEC, 'http://store.ukd1.co.uk.s3.amazonaws.com/ukd1_small.png');

// send a broadcast (to all your subscribers)
$b->broadcast('Test Name', 'Test Broadcast, this was sent at ' . date('r'));

// send a message to a specific user, with an ID of 999
$b->notify(YOUR_EMAIL, 'Test name', 'Hey ' . YOUR_EMAIL . ' this was sent at ' . date('r'), 999);


// you can also do with a default icon
boxcar_api::factory(API_KEY, API_SEC, 'http://store.ukd1.co.uk.s3.amazonaws.com/ukd1_small.png')
	->broadcast('Test broadcast', 'Another test broadcast with icon');
