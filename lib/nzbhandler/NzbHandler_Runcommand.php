<?php

class NzbHandler_Runcommand extends NzbHandler_abs
{
	private $_localDir = null;
	private $_cmdToRun = null;
	
	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'Runcommand', 'Run', $nzbHandling);

		# als het commando leeg is, gooi een exception anders geeft php een warning
		$this->_cmdToRun = $nzbHandling['command'];
		if (empty($this->_cmdToRun))
		{
			throw new Exception("command in handler is leeg maar 'runcommand' gekozen!");
		} # if

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
		
		$cmdToRun = str_replace('$SPOTTITLE', $fullspot['title'], $this->_cmdToRun);
		$cmdToRun = str_replace('$NZBPATH', $filename, $cmdToRun);
		
		# execute the command
		exec($cmdToRun, $saveOutput, $status);
				
		if ($status != 0)
		{
			throw new Exception("Unable to execute program: " . $cmdToRun);
		} # if

	} # processNzb

} # class NzbHandler_Runcommand
