<?php
abstract class NzbHandler_abs
{
	private $_name = "Abstract";
	private $_nameShort = "Abstract";
	
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

	abstract public function processNzb($fullspot, $filename, $category, $nzb, $mimetype);

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
	protected function makeNzbLocalPath($fullspot, $category, $path)
	{
		# als de path niet eindigt met een backslash of forwardslash, voeg die zelf toe
		$path = $this->addTrailingSlash($path);
		# add category to path
		$path .= $this->cleanForFileSystem($category);
		$path = $this->addTrailingSlash($path);
		
		$title = $this->cleanForFileSystem($fullspot['title']);
		
		return $path . $title . '.nzb';
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
	}
	
	/* 
	 * Zet een Spot category om naar een sabnzbd category
	 */
	public function convertCatToSabnzbdCat($spot, $settings) {
		# fix de category
		$spot['category'] = (int) $spot['category'];
		
		# vind een geschikte category
		$sabnzbd = $settings->get('sabnzbd');
		$category = $sabnzbd['categories'][$spot['category']]['default'];

		foreach($spot['subcatlist'] as $cat) {
			if (isset($sabnzbd['categories'][$spot['category']][$cat])) {
				$category = $sabnzbd['categories'][$spot['category']][$cat];				
			} # if
		} # foreach
		
		return $category;
	} # convertCatToSabnzbdCat	
}

