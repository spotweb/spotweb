<?php
abstract class NzbHandler_abs
{
	protected $_name = "Abstract";
	protected $_nameShort = "Abstract";
	
	protected $_settings = null;
	
	function __construct($settings, $name, $nameShort)
	{
		$this->_settings = $settings;
		$this->_name = $name;
		$this->_nameShort = $nameShort;
	} # __construct
	
	/**
	 * Get the name of the application handling the nzb, e.g. "SabNZBd".
	 */
	public function getName()
	{
		return $this->_name;
	} # getName

	/**
	 * Set the name of the application handling the nzb. This allows template
	 * designers to adapt the application name if necessary
	 */	
	public function setName($name)
	{
		$this->_name = $name;
	} # setName

	/**
	 * Get the name of the application handling the nzb, e.g. "SAB".
	 */	
	public function getNameShort()
	{
		return $this->_nameShort;
	} # getNameShort

	/**
	 * Set the short name of the application handling the nzb. This allows template
	 * designers to adapt the application name if necessary
	 */
	
	public function setNameShort($name)
	{
		$this->_nameShort = $name;
	} # setNameShort
	
	abstract public function processNzb($fullspot, $nzblist);

	public function generateNzbHandlerUrl($spot)
	{
		$spotwebUrl = $this->_settings->get('spotweburl');
		$nzbHandling = $this->_settings->get('nzbhandling');
		$action = $nzbHandling['action'];
		$url = $spotwebUrl . '?page=getnzb&amp;action=' . $action . '&amp;messageid=' . $spot['messageid'];
		
		return $url;
	} # generateNzbHandlerUrl
	
	/*
	 * Genereert een schone filename voor nzb files
	 */
	protected function cleanForFileSystem($title)
	{
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
	protected function makeNzbLocalPath($fullspot, $path)
	{
		$category = $this->convertCatToSabnzbdCat($fullspot);
		
		# add category to path als dat gevraagd is
		$path = str_replace('$SANZBDCAT', $this->cleanForFileSystem($category), $path);
		$path = str_replace('$SABNZBDCAT', $this->cleanForFileSystem($category), $path);

		# als de path niet eindigt met een backslash of forwardslash, voeg die zelf toe
		$path = $this->addTrailingSlash($path);
		
		return $path;
	} # makeNzbLocalPath
	
	
	/*
	 * Voegt, indien nodig, een trailing slash toe
	 */
	protected function addTrailingSlash($path)
	{
		# als de path niet eindigt met een backslash of forwardslash, voeg die zelf toe
		if (strpos('\/', $path[strlen($path) - 1]) === false) {
			$path .= DIRECTORY_SEPARATOR;
		} # if
		
		return $path;
	} # addTrailingSlash
	
	protected function sendHttpRequest($method, $url, $header, $content, $timeout = 15, $userAgent = 'Spotweb')
	{
		$stream_options = array('http' =>
			array('timeout' => $timeout,
					'method' => $method,
					'user_agent' => 'Spotweb',
					'header' => $header,
					'content' => $content));

		$ctx = stream_context_create($stream_options);

		return @file_get_contents($url, false, $ctx);
	} # sendHttpRequest
	
	protected function prepareNzb($fullspot, $nzblist)
	{
		# nu we alle nzb files hebben, trekken we de 'file' secties eruit, 
		# en plakken die in onze overkoepelende nzb
		$nzbHandling = $this->_settings->get('nzbhandling');
		$result = array();
		switch($nzbHandling['prepare_action'])
		{
			case 'zip'	: {
				$result['nzb'] = $this->zipNzbList($nzblist); 
				$result['mimetype'] = 'application/x-zip-compressed';
				$result['filename'] = 'SpotWeb_' . microtime(true) . '.zip';
				break;
			} # zip
			
			default 		: {
				$result['nzb'] = $this->mergeNzbList($nzblist); 
				$result['mimetype'] = 'application/x-nzb';
				$result['filename'] = $this->cleanForFileSystem($fullspot['title']) . '.nzb';
				break;
			} # merge
		} # switch

		return $result;
	} # prepareNzb
	
	/* 
	 * Zet een Spot category om naar een sabnzbd category
	 */
	protected function convertCatToSabnzbdCat($spot) {
		# fix de category
		$spot['category'] = (int) $spot['category'];
		
		# vind een geschikte category
		$sabnzbd = $this->_settings->get('sabnzbd');
		
		if (isset($sabnzbd['categories'][$spot['category']]['default'])) {
			$category = $sabnzbd['categories'][$spot['category']]['default'];
		} else {
			$category = '';
		} # else

		foreach($spot['subcatlist'] as $cat) {
			if (isset($sabnzbd['categories'][$spot['category']][$cat])) {
				$category = $sabnzbd['categories'][$spot['category']][$cat];				
			} # if
		} # foreach
		
		return $category;
	} # convertCatToSabnzbdCat

	/*
	 * Voeg een lijst van NZB XML files samen tot 1 XML file
	 */
	protected function mergeNzbList($nzbList) {
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
	protected function zipNzbList($nzbList) {
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

} # class NzbHandler_abs

