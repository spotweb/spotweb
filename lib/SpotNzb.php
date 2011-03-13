<?php
require_once "lib/exceptions/InvalidLocalDirException.php";

# NZB Utility functies
class SpotNzb {
	private $_settings;
	private $_db;
	
	function __construct($db, $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor


	/*
	 * Sla de NZB file op het lokale filesysteem op
	 */
	function saveNzbFile($fullSpot, $nzb) {
		$fname = $this->makeNzbLocalPath($fullSpot);
		if (file_put_contents($fname, $nzb) === false) {
			throw new InvalidLocalDirException("Unable to write NZB file to: " . $fname);
		} # if
	} # saveNzbFile

	/*
	 * Voer een commando uit, geeft een exception als commando mislukt
	 */	 
	function runCommand($fullSpot) {
		$cmdToRun = $this->_settings['nzbhandler']['command'];
		$cmdToRun = str_replace('$SPOTTITLE', $this->cleanForFileSystem($fullSpot['title']), $cmdToRun);
		$cmdToRun = str_replace('$NZBPATH', $this->makeNzbLocalPath($fullSpot['title']), $cmdToRun);
		
		# als het commando leeg is, gooi een exception anders geeft php een warning
		if (empty($cmdToRun)) {
			throw new Exception("command in handler is leeg maar 'runcommand' gekozen!");
		} # if
		
		# en voer het commando ut
		exec($cmdToRun, $saveOutput, $status);
				
		if ($status != 0) {
			throw new Exception("Unable to execute program: " . $cmdToRun);
		} # if
	} # runCommand
	
	/*
	 * Roept sabnzbd aan en parseert de output
	 */
	function runHttp($fullSpot, $nzb, $action) {
		@define('MULTIPART_BOUNDARY', '--------------------------'.microtime(true));
		# equivalent to <input type="file" name="nzbfile"/>
		@define('FORM_FIELD', 'nzbfile'); 

		# URL to run
		$url = $this->generateSabnzbdUrl($fullSpot, $action);
		
		# dit is gecopieerd van:
		#	http://stackoverflow.com/questions/4003989/upload-a-file-using-file-get-contents

		# creeer de header
		$header = 'Content-Type: multipart/form-data; boundary='.MULTIPART_BOUNDARY;

		# bouw nu de content
		$content = "--" . MULTIPART_BOUNDARY . "\r\n";
		$content .= 
            "Content-Disposition: form-data; name=\"" . FORM_FIELD . "\"; filename=\"" . $this->cleanForFileSystem($fullSpot['title']) . ".nzb\"\r\n" .
			"Content-Type: application/x-nzb\r\n\r\n" . 
			$nzb."\r\n";
			
		# signal end of request (note the trailing "--")
		$content .= "--".MULTIPART_BOUNDARY."--\r\n";

		# create an stream context to be able to pass certain parameters
		$ctx = stream_context_create(array('http' => 
					array('timeout' => 15,
						  'method' => 'POST',
						  'header' => $header,
						  'content' => $content)));

		$output = @file_get_contents($url, 0, $ctx);
		if ($output	=== false) {
			throw new Exception("Unable to open sabnzbd url: " . $url);
		} # if
		
		if (strtolower(trim($output)) != 'ok') {
			throw new Exception("sabnzbd returned: " . $output);
		} # if
	} # runHttp

	/*
	 * Behandel de gekozen actie voor de NZB file
	 */
	function handleNzbAction($messageid, $action, $hdr_spotnntp, $nzb_spotnntp) {
		# Haal de volledige spot op en gebruik de informatie daarin om de NZB file op te halen
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($messageid, $hdr_spotnntp);
		$nzb = $spotsOverview->getNzb($fullSpot['nzb'], $nzb_spotnntp);
		
		# handel dit alles af naar gelang de actie die gekozen is
		switch ($action) { 
			case 'disable'			: break;
			
			# gewoon nzb file output geven
			case 'display'			: {
				Header("Content-Type: application/x-nzb");
				Header("Content-Disposition: attachment; filename=\"" . urlencode($fullSpot['title']) . ".nzb\"");
				echo $nzb;
				break;
			} # display
			
			# Voor deze acties moeten we de NZB file op het FS wegschrijven, dus dan doen we dat
			case 'save'				: $this->saveNzbFile($fullSpot, $nzb); break;
			case 'runcommand'		: {
				$this->saveNzbFile($fullSpot, $nzb); 
				$this->runCommand($fullSpot); 
				break;
			} # runcommand
			
			case 'push-sabnzbd'		: {
				$this->runHttp($fullSpot, $nzb, $action);
				break;
			} # push-sabnzbd
			
			default					: throw new Exception("Invalid action: " . $action);
		} # switch
		
		# en voeg hem toe aan de lijst met downloads
		if ($this->_settings['keep_downloadlist']) {
			$this->_db->addDownload($fullSpot['messageid']);
		} # if
	} # handleNzbAction
	 
	/*
	 * Genereert een schone filename voor nzb files
	 */
	function cleanForFileSystem($title) {
		$allowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!,@#^()-=+ _{}';
		$newTitle = '';
		
		for($i = 0; $i < strlen($title); $i++) {
			if (stripos($allowedChars, $title[$i]) === false) {
				$newTitle .= '_';
			} else {
				$newTitle .= $title[$i];
			} 
		} # for
		
		return $newTitle;
	} # cleanForFileSystem
	
	/* 
	 * Genereert het volledige path naar de NZB locatie waar files opgeslagen moeten worden
	 */
	function makeNzbLocalPath($spot) {
		if (empty($this->_settings['nzbhandling']['local_dir'])) {
			throw new InvalidLocalDirException("Unable to save NZB file, local dir is empty");
		} # if
		
		$path = $this->_settings['nzbhandling']['local_dir'];
		$fname = $this->cleanForFileSystem($spot['title']);
		
		# als de path niet eindigt met een backslash of forwardslash, voeg die zelf toe
		if (strpos('\/', $path[strlen($path) - 1]) === false) {
			$path .= '/';
		} # if
		
		return $path . $fname . '.nzb';
	} # makeNzbLocalPath
	
	
	/* 
	 * Genereert de URL voor sabnzbd om de spot te adden - dit is niet
	 * de functie die direct voor een template geschikt is
	 */
	function generateSabnzbdUrl($spot, $action) {
		# en creeer die sabnzbd url
		$sabnzbd = $this->_settings['nzbhandling']['sabnzbd'];
		$tmp = $sabnzbd['url'];
		
		# vervang een aantal variables		
		$tmp = str_replace('$SABNZBDHOST', $sabnzbd['host'], $tmp);
		$tmp = str_replace('$SPOTTITLE', urlencode($this->cleanForFileSystem($spot['title'])), $tmp);
		$tmp = str_replace('$SANZBDCAT', $this->convertCatToSabnzbdCat($spot), $tmp);
		$tmp = str_replace('$APIKEY', $sabnzbd['apikey'], $tmp);

		# afhankelijk van de keuze van opslaan, moeten we een andere NZB url meegeven
		if ($action == 'client-sabnzbd') {
			# Client roept sabnzbd aan
			$tmp = htmlentities($tmp);
			$tmp = str_replace('$SABNZBDMODE', 'addurl', $tmp);
			$tmp = str_replace('$NZBURL', urlencode($sabnzbd['spotweburl'] . '?page=getnzb&action=display&messageid=' . $spot['messageid']), $tmp);
		} elseif ($action == 'push-sabnzbd') {
			# server roept sabnzbd aan
			$tmp = str_replace('$SABNZBDMODE', 'addfile', $tmp);
			$tmp = str_replace('$NZBURL', '', $tmp);
		} # else
		
		return $tmp;
	} # generateSabnzbdUrl
	
	/* 
	 * Zet een Spot category om naar een sabnzbd category
	 */
	function convertCatToSabnzbdCat($spot) {
		# fix de category
		$spot['category'] = (int) $spot['category'];
		
		# vind een geschikte category
		$category = $this->_settings['sabnzbd']['categories'][$spot['category']]['default'];

		foreach($spot['subcatlist'] as $cat) {
			if (isset($this->_settings['sabnzbd']['categories'][$spot['category']][$cat])) {
				$category = $this->_settings['sabnzbd']['categories'][$spot['category']][$cat];
			} # if
		} # foreach
		
		return $category;
	} # convertCatToSabnzbdCat
	
} # SpotNzb
