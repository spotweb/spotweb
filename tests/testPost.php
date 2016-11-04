<?php
require_once "lib/SpotClassAutoload.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotPosting.php";
require_once "lib/SpotCategories.php";
require_once "lib/SpotSigning.php";
require_once "lib/SpotSeclibToOpenSsl.php";
require_once "lib/SpotNntp.php";
require_once "NNTP/Protocol/Client.php";
require_once "NNTP/Client.php";
require_once "lib/exceptions/CustomException.php";
require_once "lib/exceptions/NntpException.php";
require_once "lib/SpotSecurity.php";
require_once "lib/SpotTiming.php";
require_once "settings.php";
require_once "lib/SpotDb.php";

define('USERID', 30);

/* -------------------------------------------------------------------- */
echo "Included PHP classes... " . PHP_EOL;

$db = new SpotDb($settings['db']);
$db->connect();

echo "Connected to the database.." . PHP_EOL;

$spotSettings = SpotSettings::singleton($db, $settings);
$spotSigning = new SpotSigning();
$spotPosting = new SpotPosting($db, $spotSettings);
$spotUserSystem = new SpotUserSystem($db, $spotSettings);

echo "Initialized classes.." . PHP_EOL;

$spot['category'] = 0;
$spot['website'] = 'http://www.moviemeter.nl/film/69912';
$spot['body'] = 'Hierbij een cover van de film Colombiana.

Met dank aan de originele poster van deze cover';
$spot['poster'] = 'Spotweb Test User';
$spot['tag'] = '';
$spot['key'] = 7;
$spot['title'] = 'Colombiana cover (SWtest2)';
echo "Spot Title will be: " . $spot['title'] . PHP_EOL;
$spot['category'] = 0;
$spot["subcata"] = "a5|";
$spot['subcatb'] = '';
$spot['subcatc'] = '';
$spot['subcatd'] = 'd30|';
$spot['subcatz'] = 'z2|';
echo "Generating hash.." . PHP_EOL;
$spot['newmessageid'] = substr($spotSigning->makeExpensiveHash('<' . $spotSigning->makeRandomStr(15), '@spot.net>'), 1, -1);
echo "Hash generated.." . PHP_EOL;

$user = $spotUserSystem->getUser(USERID);
$user['privatekey'] = $db->getUserPrivateRsaKey($user['userid']);
echo "Posting spot... " . PHP_EOL;
var_dump($spotPosting->postSpot($user, $spot, 'tests/test.jpg', 'tests/test.nzb'));


