<?php
class NzbHandler_Disable extends NzbHandler_abs
{
	function __construct($settings)
	{
		$this->setName("Disable");
		$this->setNameShort("Disable");
		
	} # __construct
	
	public function processNzb($fullspot, $nzblist)
	{
		# do nothing
	} # processNzb
	
	public function generateNzbHandlerUrl($spot)
	{
		return '';
	}
}
