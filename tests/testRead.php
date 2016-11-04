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

$rsaKeys = $spotSettings->get('rsa_keys');
$retriever = new SpotRetriever_Spots($spotSettings->get('nntp_hdr'),
				 $db, 
				 $spotSettings,										 
				 $rsaKeys, 
				 '',
				 $spotSettings->get('retrieve_full'));
$msgdata = $retriever->connect($spotSettings->get('hdr_group'));
var_dump($msgdata);

# Connct thru our own NNTP session to the server so we have an XOVER list
$headerServer = $spotSettings->get('nntp_hdr');
$spotnntp = new SpotNntp($spotSettings->get('nntp_hdr'));
$spotnntp->selectGroup($spotSettings->get('hdr_group'));
$hdrList = $spotnntp->getOverview(3244937, 3244938);


foreach($hdrList as $msgid => $msgheader) {
	$spotParser = new SpotParser();
	$spot = $spotParser->parseXover($msgheader['Subject'], 
					$msgheader['From'], 
					$msgheader['Date'],
					$msgheader['Message-ID'],
					$rsaKeys);

	var_dump($spot);
}


# and signal quit
$retriever->quit();
