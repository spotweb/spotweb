<?php
require_once "lib/exceptions/InvalidLocalDirException.php";

# NZB Utility functies
class SpotNzb {
	private $_settings;
	private $_db;
	
	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Voeg een lijst van NZB XML files samen tot 1 XML file
	 */
	function mergeNzbList($nzbList) {
		$nzbXml = simplexml_load_string('<?xml version="1.0" encoding="iso-8859-1" ?>
											<!DOCTYPE nzb PUBLIC "-//newzBin//DTD NZB 1.0//EN" "http://www.newzbin.com/DTD/nzb/nzb-1.0.dtd">
											<nzb xmlns="http://www.newzbin.com/DTD/2003/nzb"></nzb>');
		$domNzbXml = dom_import_simplexml($nzbXml);
		foreach($nzbList as $nzb) {
			$oneNzbFile = simplexml_load_string($nzb['nzb']);
			
			# add each file section to the larger XML object
			foreach($oneNzbFile->file as $file) {
				# Import the file into the larger NZB object
				$domFile = $domNzbXml->ownerDocument->importNode(dom_import_simplexml($file), TRUE);
				$domNzbXml->appendChild($domFile);
			} # foreach
		} # foreach
		return $nzbXml->asXml();
	} # mergeNzbList

	/*
	 * Stop de lijst van NZB XML files in 1 zip file
	 */
	function zipNzbList($nzbList) {
		$tmpZip = tempnam(sys_get_temp_dir(), 'SpotWebZip');
		$zip = new ZipArchive;
		$res = $zip->open($tmpZip, ZipArchive::CREATE);
		if ($res !== TRUE) {
			throw new Exception("Unable to create temporary ZIP file: " . $res);
		} # if
		
		foreach($nzbList as $nzb) {
			$zip->addFromString($this->cleanForFileSystem($nzb['spot']['title']) . '.nzb', $nzb['nzb']);
		} # foreach
		$zip->close();
		
		# lees de tempfile uit 
		$zipFile = file_get_contents($tmpZip);
		
		# en wis de tijdelijke file
		unlink($tmpZip);
		
		return $zipFile;
	} # zipNzbList
		
	/*
	 * Behandel de gekozen actie voor de NZB file
	 */
	function handleNzbAction($messageids, $ourUserId, $action, $hdr_spotnntp, $nzb_spotnntp) {
		if (!is_array($messageids)) {
			$messageids = array($messageids);
		} # if
		
		# Haal de volledige spot op en gebruik de informatie daarin om de NZB file op te halen
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		
		$nzbList = array();
		foreach($messageids as $thisMsgId) {
			$fullSpot = $spotsOverview->getFullSpot($thisMsgId, $ourUserId, $hdr_spotnntp);
			
			if (!empty($fullSpot['nzb'])) {
				$nzbList[] = array('spot' => $fullSpot, 
								   'nzb' => $spotsOverview->getNzb($fullSpot['nzb'], $nzb_spotnntp));
			} # if
		} # foreach

		# nu we alle nzb files hebben, trekken we de 'file' secties eruit, 
		# en plakken die in onze overkoepelende nzb
		$nzbHandling = $this->_settings->get('nzbhandling');
		switch($nzbHandling['prepare_action']) {
			case 'zip'	: {
				$nzb = $this->zipNzbList($nzbList); 
				$mimeType = 'application/x-zip-compressed';
				$fileName = 'SpotWeb_' . microtime(true) . '.zip';
				break;
			} # zip
			
			default 		: {
				$nzb = $this->mergeNzbList($nzbList); 
				$mimeType = 'application/x-nzb';
				$fileName = $fullSpot['title'] . '.nzb';
				break;
			} # merge
		} # switch

		# send nzb to NzbHandler plugin
		$nzbHandlerFactory = new NzbHandler_Factory();
		$nzbHandler = $nzbHandlerFactory->build($this->_settings, $action);

		$category = $nzbHandler->convertCatToSabnzbdCat($fullSpot, $this->_settings);
		$nzbHandler->processNzb($fullSpot, $fileName, $category, $nzb, $mimeType);

		# en voeg hem toe aan de lijst met downloads
		if ($this->_settings->get('keep_downloadlist')) {
			foreach($messageids as $thisMsgId) {
				$this->_db->addDownload($thisMsgId, $ourUserId);
			} # foreach
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
	 * Genereert de URL voor sabnzbd om de spot te adden - dit is niet
	 * de functie die direct voor een template geschikt is
	 */
	function generateSabnzbdUrl($spot, $action) {
		# en creeer die sabnzbd url
		$nzbHandling = $this->_settings->get('nzbhandling');
		$sabnzbd = $nzbHandling['sabnzbd'];
		$tmp = $sabnzbd['url'];

		# vervang een aantal variables		
		$tmp = str_replace('$SABNZBDHOST', $sabnzbd['host'], $tmp);
		$tmp = str_replace('$SPOTTITLE', urlencode($this->cleanForFileSystem($spot['title'])), $tmp);
		$tmp = str_replace('$SANZBDCAT', $this->convertCatToSabnzbdCat($spot), $tmp);
		$tmp = str_replace('$APIKEY', $sabnzbd['apikey'], $tmp);

		# afhankelijk van de keuze van opslaan, moeten we een andere NZB url meegeven
		if ($action == 'client-sabnzbd') {
			# Client roept sabnzbd aan
			$tmp = htmlspecialchars($tmp);
			$tmp = str_replace('$SABNZBDMODE', 'addurl', $tmp);
			$tmp = str_replace('$NZBURL', urlencode($this->_settings->get('spotweburl') . '?page=getnzb&action=display&messageid=' . $spot['messageid']), $tmp);
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
		$sabnzbd = $this->_settings->get('sabnzbd');
		$category = $sabnzbd['categories'][$spot['category']]['default'];

		foreach($spot['subcatlist'] as $cat) {
			if (isset($sabnzbd['categories'][$spot['category']][$cat])) {
				$category = $sabnzbd['categories'][$spot['category']][$cat];
			} # if
		} # foreach
		
		return urlencode($category);
	} # convertCatToSabnzbdCat
	
} # SpotNzb
