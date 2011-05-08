<?php
class NzbHandler_Factory
{
	public static function build(SpotSettings $settings, $action, $currentSession)
	{
		# Nieuwe handlers voegen we expliciet toe omdat we anders
		# niet weten wat we includen in combinate met __autoload()
		switch ($action)
		{
			case 'disable'			: $handler = new NzbHandler_Disable($settings); break;
			case 'save'	  			: $handler = new NzbHandler_Save($settings); break;
			case 'runcommand'		: $handler = new NzbHandler_Runcommand($settings); break;
			case 'push-sabnzbd' 	: $handler = new NzbHandler_Pushsabnzbd($settings); break;
			case 'client-sabnzbd' 	: $handler = new NzbHandler_Clientsabnzbd($settings, $currentSession); break;
			case 'nzbget'			: $handler = new NzbHandler_Nzbget($settings); break;
			default					: $handler = new NzbHandler_Display($settings); break;
		} # switch

		return $handler;
	} # build()

} # class NzbHandler_Factory
