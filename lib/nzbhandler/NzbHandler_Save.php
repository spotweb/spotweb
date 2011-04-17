<?php
require_once('lib/exceptions/InvalidLocalDirException.php');

class NzbHandler_Save extends NzbHandler_abs
{
	private $_localDir = null;
	
	function __construct($settings)
	{
		$this->setName("Save");
		$this->setNameShort("Save");
		
		$nzbhandling = $settings->get('nzbhandling');
		$this->_localDir = $nzbhandling['local_dir'];
		if (empty($this->_localDir))
		{
			throw new InvalidLocalDirException("Unable to save NZB file, local dir in config is empty");
		} # if
		
	} # __construct
	
	
	public function processNzb($fullspot, $filename, $category, $nzb, $mimetype)
	{
		# $filename, $mimetype not used
		$filename = $this->makeNzbLocalPath($fullspot, $category, $this->_localDir);
	
		# Sla de NZB file op het lokale filesysteem op
		if (file_put_contents($filename, $nzb) === false)
		{
			throw new InvalidLocalDirException("Unable to write NZB file to: " . $filename);
		} # if
		
	} # processNzb
	
}
