<?php
require_once('lib/nzbhandler/NzbHandler.php');

class DisableNzbHandler extends NzbHandler
{
	function __construct($settings)
	{
		$this->setName("Disable");
		$this->setNameShort("Disable");
		
	} # __construct
	
	public function processNzb($fullspot, $filename, $category, $nzb, $mimetype)
	{
		# do nothing
	} # processNzb
}
?>