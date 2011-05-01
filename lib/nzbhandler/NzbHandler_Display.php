<?php
class NzbHandler_Display extends NzbHandler_abs
{
	function __construct($settings)
	{
		$this->setName("Display");
		$this->setNameShort("Show");
		$this->setSettings($settings);
		
	} # __construct

	public function processNzb($fullspot, $nzblist)
	{
		$nzb = $this->prepareNzb($fullspot, $nzblist);
		
		Header("Content-Type: " . $nzb['mimetype']);
		Header("Content-Disposition: attachment; filename=\"" . $nzb['filename'] . "\"");
		echo $nzb['nzb'];

	} # processNzb
	
}
