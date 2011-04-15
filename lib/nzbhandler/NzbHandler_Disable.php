<?php
class NzbHandler_Disabble extends NzbHandler_abs
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
