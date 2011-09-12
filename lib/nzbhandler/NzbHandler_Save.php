<?php
class NzbHandler_Save extends NzbHandler_abs
{
	private $_localDir = null;
	
	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'Save', 'Save', $nzbHandling);
		
		$this->_localDir = $nzbHandling['local_dir'];
		if (empty($this->_localDir))
		{
			throw new InvalidLocalDirException("Unable to save NZB file, local dir in config is empty");
		} # if
		
	} # __construct
	
	
	public function processNzb($fullspot, $nzblist)
	{
		$nzb = $this->prepareNzb($fullspot, $nzblist);
		
		$path = $this->makeNzbLocalPath($fullspot, $this->_localDir);
		$filename = $path . $nzb['filename'];
		
		# Sla de NZB file op het lokale filesysteem op
		if (file_put_contents($filename, $nzb['nzb']) === false)
		{
			throw new InvalidLocalDirException("Unable to write NZB file to: " . $filename);
		} # if
		
	} # processNzb

} # class NzbHandler_Save
