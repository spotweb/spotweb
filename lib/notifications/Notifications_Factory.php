<?php
class Notifications_Factory {

	public static function build($appName, $provider, array $dataArray) {
		# Nieuwe handlers voegen we expliciet toe omdat we anders
		# niet weten wat we includen in combinate met __autoload()
		switch ($provider) {
			case 'email'			: $handler = new Notifications_Email($appName, $dataArray); break;
			case 'growl'			: $handler = new Notifications_Growl($appName, $dataArray); break;
			case 'notifo'	  		: $handler = new Notifications_Notifo($appName, $dataArray); break;
			case 'prowl'			: $handler = new Notifications_Prowl($appName, $dataArray); break;
			default					: $handler = false; break;
		} # switch

		return $handler;
	} # build()
	
	public static function getActiveServices() {
		return array('email',
					 'growl',
					 'notifo',
					 'prowl'
					);
	}

} # class Notifications_Factory
