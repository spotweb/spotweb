<?php
require_once('lib/nzbhandler/NzbHandler.php');

class DisplayNzbHandler extends NzbHandler
{
	function __construct($settings)
	{
		$this->setName("Display");
		$this->setNameShort("Show");
		
	} # __construct

	public function processNzb($fullspot, $filename, $category, $nzb, $mimetype)
	{
		# $fullspot, $category not used
		
		Header("Content-Type: " . $mimetype);
		Header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
		echo $nzb;
		
	} # processNzb
	
}
?>