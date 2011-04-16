<?php
class NzbHandler_Factory
{
	public static function build($settings)
	{
		# automatically determine classname based on configured action
		$nzbhandling = $settings->get('nzbhandling');
		
		$action = strtolower($nzbhandling['action']);

		# for backward compatibility we introduce a mapping
		# Nieuwe handlers voegen we alsnog expliciet toe omdat we anders
		# niet weten wat we includen in combinate met __autoload()
		switch ($action)
		{
			case 'disable'			: $handler = new NzbHandler_Disable($settings); break;
			case 'save	'	  		: $handler = new NzbHandler_Save($settings); break;
			case 'runcommand'		: $handler = new NzbHandler_Runcommand($settings); break;
			case 'push-sabnzbd' 	: $handler = new NzbHandler_Pushsabnzbd($settings); break;
			case 'client-sabnzbd' 	: $handler = new NzbHandler_Pushsabnzbd($settings); break;
			case 'nzbget'			: $handler = new NzbHandler_Nzbget($settings); break;
			default					: $handler = new NzbHandler_Display($settings); break;
		} # switch

		return $handler;
	} # build()
} # class NzbHandler_Factory
