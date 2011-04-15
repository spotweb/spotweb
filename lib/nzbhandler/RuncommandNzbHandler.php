<?php
require_once('lib/nzbhandler/NzbHandler.php');
require_once('lib/nzbhandler/SaveNzbHandler.php');

class RuncommandNzbHandler extends SaveNzbHandler
{
	private $_localDir = null;
	private $_cmdToRun = null;
	
	function __construct($settings)
	{
		parent::__construct($settings);

		$this->setName("Run");
		$this->setNameShort("Run");

		# als het commando leeg is, gooi een exception anders geeft php een warning
		$nzbhandling = $settings->get('nzbhandling');
		$this->_cmdToRun = $nzbhandling['command'];
		if (empty($this->_cmdToRun))
		{
			throw new Exception("command in handler is leeg maar 'runcommand' gekozen!");
		} # if

		$this->_localDir = $nzbhandling['local_dir'];
		if (empty($this->_localDir))
		{
			throw new InvalidLocalDirException("Unable to save NZB file, local dir in config is empty");
		} # if
		
	} # __construct

	public function processNzb($fullspot, $filename, $category, $nzb, $mimetype)
	{
		# $filename, $mimetype not used

		# save the nzb
		parent::processNzb($fullspot, $filename, $category, $nzb, $mimetype);
		
		# where was the nzb stored
		$filename = $this->makeNzbLocalPath($fullspot, $category, $this->_localDir);
		
		$cmdToRun = str_replace('$SPOTTITLE', $fullspot['title'], $this->_cmdToRun);
		$cmdToRun = str_replace('$NZBPATH', $filename, $cmdToRun);
		
		# execute the command
		exec($cmdToRun, $saveOutput, $status);
				
		if ($status != 0)
		{
			throw new Exception("Unable to execute program: " . $cmdToRun);
		} # if

	} # processNzb
}
?>