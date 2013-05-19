<?php
class Services_NzbHandler_Factory
{
	public static function build(Services_Settings_Base $settings, $action, array $nzbHandling)
	{
		# Nieuwe handlers voegen we expliciet toe omdat we anders
		# niet weten wat we includen in combinate met __autoload()
		switch ($action)
		{
			case 'disable'			: $handler = new Services_NzbHandler_Disable($settings, $nzbHandling); break;
			case 'save'	  			: $handler = new Services_NzbHandler_Save($settings, $nzbHandling); break;
			case 'runcommand'		: $handler = new Services_NzbHandler_Runcommand($settings, $nzbHandling); break;
			case 'push-sabnzbd' 	: $handler = new Services_NzbHandler_Pushsabnzbd($settings, $nzbHandling); break;
			case 'client-sabnzbd' 	: $handler = new Services_NzbHandler_Clientsabnzbd($settings, $nzbHandling); break;
			case 'nzbget'			: $handler = new Services_NzbHandler_Nzbget($settings, $nzbHandling); break;
			default					: $handler = new Services_NzbHandler_Display($settings, $nzbHandling); break;
		} # switch

		if (!$handler instanceof Services_NzbHandler_Disable && $handler->isAvailable()!==true) {
			$handler = new Services_NzbHandler_Disable($settings, $nzbHandling);
		}

		return $handler;
	} # build()

} # class Services_NzbHandler_Factory
