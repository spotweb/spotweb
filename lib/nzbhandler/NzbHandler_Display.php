<?php
class NzbHandler_Display extends NzbHandler_abs
{
	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'Display', 'Show', $nzbHandling);
	} # __construct

	public function processNzb($fullspot, $nzblist)
	{
		$nzb = $this->prepareNzb($fullspot, $nzblist);
		
		Header("Content-Type: " . $nzb['mimetype']);
		Header("Content-Disposition: attachment; filename=\"" . $nzb['filename'] . "\"");
		echo $nzb['nzb'];

	} # processNzb

} # class NzbHandler_Display
