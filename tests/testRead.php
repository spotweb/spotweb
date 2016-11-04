<?php
require_once 'vendor/autoload.php';
require_once "settings.php";

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
