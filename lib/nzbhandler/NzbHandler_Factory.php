<?php
class NzbHandler_Factory
{
	public static function build(SpotSettings $settings, array $nzbHandling)
	{
		# Nieuwe handlers voegen we expliciet toe omdat we anders
		# niet weten wat we includen in combinate met __autoload()
		switch ($nzbHandling['action'])
		{
			case 'disable'			: $handler = new NzbHandler_Disable($settings, $nzbHandling); break;
			case 'save'	  			: $handler = new NzbHandler_Save($settings, $nzbHandling); break;
			case 'runcommand'		: $handler = new NzbHandler_Runcommand($settings, $nzbHandling); break;
			case 'push-sabnzbd' 	: $handler = new NzbHandler_Pushsabnzbd($settings, $nzbHandling); break;
			case 'client-sabnzbd' 	: $handler = new NzbHandler_Clientsabnzbd($settings, $nzbHandling); break;
			case 'nzbget'			: $handler = new NzbHandler_Nzbget($settings, $nzbHandling); break;
			default					: $handler = new NzbHandler_Display($settings, $nzbHandling); break;
		} # switch

		return $handler;
	} # build()

} # class NzbHandler_Factory
