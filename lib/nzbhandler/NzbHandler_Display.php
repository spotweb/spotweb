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
		
		/* Een NZB file hoeft niet per se als attachment binnen te komen */
		switch($this->_nzbHandling['prepare_action']) {
			case 'zip'	: Header("Content-Disposition: attachment; filename=\"" . $nzb['filename'] . "\""); break;
			default		: Header("Content-Disposition: inline; filename=\"" . $nzb['filename'] . "\"");
		} # switch
		echo $nzb['nzb'];

	} # processNzb

} # class NzbHandler_Display
