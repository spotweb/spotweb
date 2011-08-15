<?php
abstract class NzbHandler_abs
{
	protected $_name = "Abstract";
	protected $_nameShort = "Abstract";

	protected $_nzbHandling = null;
	protected $_settings = null;
	
	function __construct($settings, $name, $nameShort, array $nzbHandling)
	{
		$this->_settings = $settings;
		$this->_nzbHandling = $nzbHandling;
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

	public function generateNzbHandlerUrl($spot, $spotwebApiParam)
	{
		$spotwebUrl = $this->_settings->get('spotweburl');
		$action = $this->_nzbHandling['action'];
		$url = $spotwebUrl . '?page=getnzb&amp;action=' . $action . '&amp;messageid=' . $spot['messageid'] . $spotwebApiParam;
		
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
		$result = array();
		switch($this->_nzbHandling['prepare_action'])
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

	# NzbHandler API functions
	public function hasApiSupport()
	{
		return false;
	} # hasApiSupport
	
	public function getStatus()
	{
		# do nothing
		return false;
	} # getStatus

	public function pauseQueue()
	{
		# do nothing
		return false;
	} #pauseQueue
	
	public function resumeQueue()
	{
		# do nothing
		return false;
	} # resumeQueue

	public function setSpeedLimit(int $limit)
	{
		# do nothing
		return false;
	} # setSpeedLimit

	public function moveDown($id)
	{
		# do nothing
		return false;
	} # moveDown
	
	public function moveUp($id)
	{
		# do nothing
		return false;
	} # moveUp
	
	public function moveTop($id)
	{
		# do nothing
		return false;
	} # moveTop

	public function moveBottom($id)
	{
		# do nothing
		return false;
	} # moveBottom
	
	public function setCategory($id, $category)
	{
		# do nothing
		return false;
	} # setCategory
	
	public function setPriority($id, $priority)
	{
		# do nothing
		return false;
	} # setPriority

	public function setPassword($id, $password)
	{
		# do nothing
		return false;
	} # setPassword	
	
	public function delete($id)
	{
		# do nothing
		return false;
	} # delete
	
	public function rename($id, $name)
	{
		# do nothing
		return false;
	} # rename
	
	public function pause($id)
	{
		# do nothing
		return false;
	} # pause
	
	public function resume($id)
	{
		# do nothing
		return false;
	} # resume

	public function getCategories()
	{
		# For NzbHandlers that do not use configurable categories, but simply create
		# category directories on demand (e.g. NZBGet) we'll just use the categories
		# that are configured in SpotWeb.
		
		$sabnzbd = $this->_settings->get('sabnzbd');

		$allcategories = array();
		foreach($sabnzbd['categories'] as $categories)
		{
			$allcategories = array_merge($allcategories, array_values($categories));
		}
		
		$allcategories = array_unique($allcategories);
		
		$result = array();
		$result['readonly'] = true;	// inform the GUI to not allow adding of adhoc categories
		$result['categories'] = $allcategories;
		
		return $result;
	} # getCategories
	
	
	public function getVersion()
	{
		# do nothing
		return false;
	} # getVersion
	
} # class NzbHandler_abs

