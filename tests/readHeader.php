<?php
require_once "lib/SpotClassAutoload.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotPosting.php";
require_once "lib/SpotCategories.php";
require_once "lib/SpotNntp.php";
require_once "NNTP/Protocol/Client.php";
require_once "NNTP/Client.php";
require_once "lib/exceptions/CustomException.php";
require_once "lib/exceptions/NntpException.php";
require_once "lib/services/Signing/Services_Signing_Base.php";
require_once "lib/SpotSecurity.php";
require_once "lib/SpotTiming.php";
require_once "settings.php";
require_once "lib/SpotDb.php";



$db = new SpotDb($settings['db']);
$db->connect();

echo "Connected to the database.." . PHP_EOL;

$spotSettings = SpotSettings::singleton($db, $settings);

$server = array('host' => $argv[1],
				'enc' => false,
				'port' => 119,
				'user' => $argv[2],
				'pass' => $argv[3]);

$rsaKeys = $spotSettings->get('rsa_keys');

$nntp = new SpotNntp($server);
$nntp->selectGroup('alt.test');
try {
	$r = $nntp->getHeader('<ZTZLM3pnNW5pE5RsDlWL8347ntp@spot.net>');
       
	foreach($r as $hdr) {
		$y = explode(":", $hdr);
		$r[$y[0]] = trim($y[1]);
	} # foreac

	$spotParser = new SpotParser();
        $spot = $spotParser->parseXover($r['Subject'],
                                        $r['From'],
                                        $r['Date'],
                                        $r['Message-ID'],
                                        $rsaKeys);

//	var_dump($y);
	//var_dump($r);
        var_dump($spot);

} catch(Exception $x) {
		var_dump($x);
}

