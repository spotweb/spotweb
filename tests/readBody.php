<?php

require "lib/SpotNntp.php";
require "lib/SpotParser.php";
require "NNTP/Protocol/Responsecode.php";
require "NNTP/Protocol/Client.php";
require "NNTP/Client.php";
require "lib/SpotSigning.php";	
require "lib/exceptions/CustomException.php";
require "lib/exceptions/NntpException.php";
require "lib/SpotSeclibToOpenSsl.php";	

$server = array('host' => $argv[1],
				'enc' => false,
				'port' => 119,
				'user' => $argv[2],
				'pass' => $argv[3]);

$nntp = new SpotNntp($server);
$nntp->selectGroup('free.pt');
try {
	$r1 = array();
	$r2 = array();

	$r1 = $nntp->getBody('<s7HqJl4Gi2MgyyITwozre@spot.net>');
	$r2 = $nntp->getBody('<OrjrHBeG9BQRrhPTwAhnJ@spot.net>');
} catch(Exception $x) {
		var_dump($x);
}
var_dump($r1);
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=" . PHP_EOL;
var_dump($r2);


