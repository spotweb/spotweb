<?php
define('SPOTWEB_FEATURE_VERSION', '0.08');

/*
 * Spotweb version check needs to have as few dependencies on the
 * rest of the Spotweb infrastructure as possible because that would
 * create an interdependency
 */
class SpotWebVersionCheck {
    const rss_url = 'https://raw.github.com/spotweb/spotweb/master/notifications.xml';
    //const rss_url = 'http://localhost:81/spotweb/notifications.xml';
    const https_not_available = <<<EOF
<rss version="2.0">
	<channel>
		<title>Spotweb update notifications</title>
		<description>HTTPS not available</description>
		<link>https://github.com/spotweb/spotweb/</link>
		<lastBuildDate>Thu, 12 Feb 2016 00:00:00 +0100</lastBuildDate>
		<pubDate>Thu, 12 Feb 2016 00:00:00 +0100</pubDate>
		<ttl>1800</ttl>

		<item>
			<title>HTTPS wrapper not available</title>
			<description><![CDATA[To be able to view notifications of changes and new features for Spotweb, you need to
			have the OpenSSL extension installed.<br /><br />
			Your PHP does not have this extension enabled, so we cannot show you the list of changes.
			]]></description>
			<link>https://github.com/spotweb/spotweb/</link>
			<guid>1</guid>
			<pubDate>Thu, 12 Feb 2016 00:00:00 +0100</pubDate>
			<author>spotweb</author>
			<!-- Please update these according to the current version, it allows for Spotweb to notify the administrator of the changes required -->
			<spotweb:schema_version>0.58</spotweb:schema_version>
			<spotweb:settings_version>0.25</spotweb:settings_version>
			<spotweb:security_version>0.29</spotweb:security_version>
			<spotweb:feature_version>0.08</spotweb:feature_version>
		</item>
	</channel>
</rss>
EOF;

	#const rss_url = './notifications.xml';

	private $_xml = null;
	
	function __construct() {
		$this->retrieveRss();
	} 
	
	/*
	 * Retrieves the RSS feed from Github
	 */
	private function retrieveRss() {
        /*
         * Check for the existence of the HTTPS stream wrapper
         */
        if (in_array('https', stream_get_wrappers())) {
            $rssFile = file_get_contents(SpotWebVersionCheck::rss_url);
        } else {
            $rssFile = SpotWebVersionCheck::https_not_available;
        } # else

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
