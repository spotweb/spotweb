<?php
class NzbHandler_Clientsabnzbd extends NzbHandler_abs
{
	private $_url = null;

	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'SABnzbd', 'SAB', $nzbHandling);

		$sabnzbd = $nzbHandling['sabnzbd'];

		# prepare sabnzbd url
		$this->_url = $sabnzbd['url'] . 'sabnzbd/api?mode=addurl&apikey=' . $sabnzbd['apikey'] . '&output=text';
	} # __construct

	public function processNzb($fullspot, $nzblist)
	{
		// do nothing
	} # processNzb

	public function generateNzbHandlerUrl($spot)
	{
		$title = urlencode($this->cleanForFileSystem($spot['title']));
		$category = urlencode($this->convertCatToSabnzbdCat($spot));

		# yes, using a local variable instead of the member variable is intentional
		$url = htmlspecialchars($this->_url . '&nzbname=' . $title . '&cat=' . $category);
		$url .= '&name=' . urlencode($this->_settings->get('spotweburl') . '?page=getnzb&action=display&messageid=' . $spot['messageid']);

		return $url;
	} # generateNzbHandlerUrl

} # class NzbHandler_Clientsabnzbd