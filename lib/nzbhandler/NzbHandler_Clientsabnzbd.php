<?php
class NzbHandler_Clientsabnzbd extends NzbHandler_abs
{
	private $_url = null;

	function __construct($settings)
	{
		$this->setName("SabNZBd");
		$this->setNameShort("SAB");
		$this->setSettings($settings);
		
		$nzbhandling = $settings->get('nzbhandling');
		$sabnzbd = $nzbhandling['sabnzbd'];
		
		# prepare sabnzbd url
		# substitute variables that are not download specific
		$this->_url = $sabnzbd['url'];		
		$this->_url = str_replace('$SABNZBDHOST', $sabnzbd['host'], $this->_url);
		$this->_url = str_replace('$APIKEY', $sabnzbd['apikey'], $this->_url);
		$this->_url = str_replace('$SABNZBDMODE', 'addurl', $this->_url);

	} # __construct

	public function processNzb($fullspot, $nzblist)
	{
		// do nothing
	} # processNzb
	
	public function generateNzbHandlerUrl($spot)
	{
		$title = urlencode($this->cleanForFileSystem($spot['title']));
		$category = urlencode($this->convertCatToSabnzbdCat($spot, $this->getSettings()));
		
		# yes, using a local variable instead of the member variable is intentional		
		$url = str_replace('$SPOTTITLE', $title, $this->_url);
		$url = str_replace('$SANZBDCAT', $category, $url);
		$url = str_replace('$SABNZBDCAT', $category, $url);
	
		$url = htmlspecialchars($url);
		$url = str_replace('$NZBURL', urlencode($this->getSettings()->get('spotweburl') . '?page=getnzb&action=display&messageid=' . $spot['messageid']), $url);
		
		return $url;
	} # generateNzbHandlerUrl
	
}
