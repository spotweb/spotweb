<?php

class Services_NzbHandler_Clientsabnzbd extends Services_NzbHandler_abs
{
    private $_url = null;

    public function __construct(Services_Settings_Container $settings, array $nzbHandling)
    {
        parent::__construct($settings, 'SABnzbd', 'SAB', $nzbHandling);

        $sabnzbd = $nzbHandling['sabnzbd'];

        // prepare sabnzbd url
        $this->_url = $sabnzbd['url'].'api?mode=addurl&apikey='.$sabnzbd['apikey'].'&output=jsonp';
    }

    // __construct

    public function processNzb($fullspot, $nzblist)
    {
        // do nothing
    }

    // processNzb

    public function generateNzbHandlerUrl($spot, $spotwebApiParam)
    {
        $title = urlencode($this->cleanForFileSystem($spot['title']));
        $category = urlencode($this->convertCatToSabnzbdCat($spot));

        // yes, using a local variable instead of the member variable is intentional
        $url = htmlspecialchars($this->_url.'&nzbname='.$title.'&cat='.$category);
        $url .= '&name='.urlencode($this->_settings->get('spotweburl').'?page=getnzb&action=display&messageid='.$spot['messageid'].html_entity_decode($spotwebApiParam));

        return $url;
    }

    // generateNzbHandlerUrl
} // class Services_NzbHandler_Clientsabnzbd
