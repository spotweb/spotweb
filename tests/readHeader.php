<?php

require 'vendor/autoload.php';

$server = array('host' => $argv[1],
				'enc' => false,
				'port' => 119,
				'user' => $argv[2],
				'pass' => $argv[3]);

$nntp = new SpotNntp($server);
$nntp->selectGroup('free.pt');
try {
	$r = $nntp->getHeader('<s7HqJl4Gi2MgyyITwozre@spot.net>');
	$r = $nntp->getHeader('<t8Dr5H2vZ4czjmITwAK6D@spot.net>');
} catch(Exception $x) {
		var_dump($x);
}
var_dump($r);

