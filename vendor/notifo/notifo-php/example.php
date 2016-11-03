<?php

/* include the Notifo_API PHP library */
include("Notifo_API.php");

/* create a new "notifo" object */
$notifo = new Notifo_API("username", "apisecret");

/* set the notification parameters */
$params = array("to"=>"username", /* "to" only used with Service accounts */
		"label"=>"Dictionary", /* "label" only used with User accounts */
		"title"=>"Word of the Day",
		"msg"=>"chuffed: delighted; pleased; satisfied.",
		"uri"=>"http://dictionary.com");

/* send the notification! */
$response = $notifo->send_notification($params);

/* handle response below */

/* ... */

?>
