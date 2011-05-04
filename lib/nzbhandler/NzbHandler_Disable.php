<?php
class NzbHandler_Disable extends NzbHandler_abs
{
	function __construct(SpotSettings $settings)
	{
		parent::__construct($settings, 'Disable', 'Disable');
		
	} # __construct
	
	public function processNzb($fullspot, $nzblist)
	{
		# do nothing
	} # processNzb
	
	public function generateNzbHandlerUrl($spot)
	{
		return '';
	} # generateNzbHandlerUrl

} # class NzbHandler_Disable
