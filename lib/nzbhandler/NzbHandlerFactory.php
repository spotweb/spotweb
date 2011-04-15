<?php
class NzbHandlerFactory
{
	public static function build($settings)
	{
		# automatically determine classname based on configured action
		
		$nzbhandling = $settings->get('nzbhandling');
		
		$action = strtolower($nzbhandling['action']);

		# TODO: remove this (used for allowing to test new nzbhandling next to old
		$action = str_replace("new-", "", $action);
		
		# for backward compatibility we introduce a mapping
		# new handlers do not need to be added here since they will automatically
		# be handled by the default case
		switch ($action)
		{
			case 'disable':
				$action = 'Disable';
				break;
			case 'display':
				$action = 'Display';
				break;
			case 'save':
				$action = 'Save';
				break;
			case 'runcommand':
				$action = 'Runcommand';
				break;
			case 'push-sabnzbd':
				$action = 'Pushsabnzbd';
				break;
			case 'client-sabnzbd':
				$action = 'Clientsabnzbd';
				break;
			default:
				# We'll use the configured action to construct the classname
				# so that we don't need to update this factory class everytime a
				# new handler is added. It's good to be lazy...
				
				$action = ucfirst($action);
				break;
		}

		$handler = $action . 'NzbHandler';
		
		if (is_readable("lib/nzbhandler/".$handler.".php"))
		{
			require_once("lib/nzbhandler/".$handler.".php");
		}
		if (!class_exists($handler))
		{
			$error = 'No handler found for configured nzbhandler action: ' . $action
				. '. Expected class file lib/nzbhandler/' . $handler .'.php does not exist.';
			error_log($error);
			throw new Exception($error);
		}
		return new $handler($settings);
	}
}
?>