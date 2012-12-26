<?php
class NzbHandler_Disable extends NzbHandler_abs
{
	function __construct(Services_Settings_Base $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'Disable', 'Disable', $nzbHandling);
		
	} # __construct
	
	public function processNzb($fullspot, $nzblist)
	{
		# do nothing
	} # processNzb
	
	public function generateNzbHandlerUrl($spot, $spotwebApiParam)
	{
		return '';
	} # generateNzbHandlerUrl

} # class NzbHandler_Disable
