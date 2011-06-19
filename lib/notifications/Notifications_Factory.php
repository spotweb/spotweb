<?php
class Notifications_Factory {

	public static function build($appName, $provider, array $dataArray) {
		# Nieuwe handlers voegen we expliciet toe omdat we anders
		# niet weten wat we includen in combinate met __autoload()
		switch ($provider) {
			case 'growl'			: $handler = new Notifications_Growl($appName, $dataArray); break;
			case 'notifo'	  		: $handler = new Notifications_Notifo($appName, $dataArray); break;
			case 'prowl'			: $handler = new Notifications_Prowl($appName, $dataArray); break;
			default					: $handler = false; break;
		} # switch

		return $handler;
	} # build()
	
	public static function getActiveServices() {
		return array('growl',
					 'notifo',
					 'prowl'
					);
	}

	# Deze functie hebben we tijdelijk nodig totdat we alles actief hebben, daarna
	# wordt deze niet meer aangesproken en kan verwijderd worden
	public static function getFutureServices() {
		return array('email',
					 'growl',
					 'libnotify',
					 'notifo',
					 'prowl'
					);
	}

} # class Notifications_Factory
