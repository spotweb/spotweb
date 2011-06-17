<?php
abstract class Notifications_abs {

	public function __construct($host, $username, $secret) {
    }

	/* registreert een service bij een host
	 * Gezocht: betere omschrijving :) */
	abstract function register();

	/* verstuurt het bericht */
	abstract function sendMessage($appName, $type, $title, $body, $sourceUrl);
} # SpotNotifyService_abs