<?php

require "lib/SpotNntp.php";
require "lib/SpotParser.php";
require "NNTP/Protocol/Responsecode.php";
require "NNTP/Protocol/Client.php";
require "NNTP/Client.php";
require "lib/services/Signing/Services_Signing_Base.php";
require "lib/services/Signing/Services_Signing_Php.php";
require "lib/services/Signing/Services_Signing_Openssl.php";
require "lib/exceptions/CustomException.php";
require "lib/exceptions/NntpException.php";

$server = array('host' => $argv[1],
				'enc' => false,
				'port' => 119,
				'user' => $argv[2],
				'pass' => $argv[3]);

$nntp = new SpotNntp($server);
$nntp->selectGroup('alt.test');
try {
	$r = $nntp->getHeader('<ZTZLM3pnNW5pE5RsDlWL8347ntp@spot.net>');
} catch(Exception $x) {
		var_dump($x);
}
var_dump($r);

