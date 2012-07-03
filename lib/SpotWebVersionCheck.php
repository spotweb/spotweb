<?php
define('SPOTWEB_FEATURE_VERSION', '0.06');

/*
 * Spotweb version check needs to have as few dependencies on the
 * rest of the Spotweb infrastructure as possible because that would
 * create an interdependency
 */
class SpotWebVersionCheck {
	const rss_url = 'https://raw.github.com/spotweb/spotweb/master/notifications.xml';
	#const rss_url = './notifications.xml';

	private $_xml = null;
	
	function __construct() {
		$this->retrieveRss();
	} 
	
	/*
	 * Retrieves the RSS feed from Github
	 */
	private function retrieveRss() {
		$rssFile = file_get_contents(SpotWebVersionCheck::rss_url);
		
		# Supress the namespace warning
		$this->_xml = @simplexml_load_string($rssFile);
	} # retrieveRss
	
	/*
	 * Returns the news items
	 */
	function getItems() {
		$itemList = array();
		
		/*
		 * Returning the array directly from simplexml doesn't work
		 */
		foreach($this->_xml->channel->item as $item) {
			$itemArray = array(
				'title' => $item->title,
				'description' => $item->description,
				'link' => $item->link,
				'guid' => $item->guid,
				'pubDate' => $item->pubDate,
				'author' => $item->author,
				'schema_version' => $item->schema_version,
				'settings_version' => $item->settings_version,
				'security_version' => $item->security_version,
				'feature_version' => $item->feature_version);
			$itemArray['is_newer_than_installed'] = !$this->isLatestVersion($itemArray);
			
			$itemList[] = $itemArray;
		} # foreach
		
		return $itemList;
	} # getItems
	
	/*
	 * Returns whether the current system is up-to-date
	 */
	function isLatestVersion($item) {
		return ($item['schema_version'] <= SPOTDB_SCHEMA_VERSION) &&
			   ($item['settings_version'] <= SPOTWEB_SETTINGS_VERSION) &&
			   ($item['security_version'] <= SPOTWEB_SECURITY_VERSION) &&
			   ($item['feature_version'] <= SPOTWEB_FEATURE_VERSION);
	} # isLatestVersion
	
} # class SpotWebVersionCheck.php
