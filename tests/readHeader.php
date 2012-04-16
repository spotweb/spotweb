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
$r = $nntp->getHeader('<s7HqJl4Gi2MgyyITwozre@spot.net>');

var_dump($r);


