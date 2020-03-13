<?php

class SpotPage_rss extends SpotPage_Abs
{
    private $_params;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_params = $params;
    }

    // ctor

    public function render()
    {
        // Make sure the proper permissions are met
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_rssfeed, '');

        $nzbhandling = $this->_currentSession['user']['prefs']['nzbhandling'];

        // Don't allow the RSS feed to be cached
        $this->sendExpireHeaders(true);

        /*
         * Transform the query parameters to a list of filters, fields,
         * sortings, etc.
         */
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);
        $svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());
        $parsedSearch = $svcSearchQp->filterToQuery(
            $this->_params['search'],
            [
                'field'     => $this->_params['sortby'],
                'direction' => $this->_params['sortdir'],
            ],
            $this->_currentSession,
            $svcUserFilter->getIndexFilter($this->_currentSession['user']['userid'])
        );

        /*
         * Actually fetch the spots
         */
        $pageNr = $this->_params['page'];
        $svcProvSpotList = new Services_Providers_SpotList($this->_daoFactory->getSpotDao());
        $spotsTmp = $svcProvSpotList->fetchSpotList(
            $this->_currentSession['user']['userid'],
            $pageNr,
            $this->_currentSession['user']['prefs']['perpage'],
            $parsedSearch
        );

        // Create an XML document for RSS
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;

        $rss = $doc->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $doc->appendChild($rss);

        $atomSelfLink = $doc->createElementNS('http://www.w3.org/2005/Atom', 'atom10:link');
        $atomSelfLink->setAttribute('href', html_entity_decode($this->_tplHelper->makeSelfUrl('full')));
        $atomSelfLink->setAttribute('rel', 'self');
        $atomSelfLink->setAttribute('type', 'application/rss+xml');

        $channel = $doc->createElement('channel');
        $channel->appendChild($doc->createElement('generator', 'Spotweb v'.SPOTWEB_VERSION));
        $channel->appendChild($doc->createElement('language', 'nl'));
        $channel->appendChild($doc->createElement('title', 'Spotweb'));
        $channel->appendChild($doc->createElement('description', 'Spotweb RSS Feed'));
        $channel->appendChild($doc->createElement('link', $this->_tplHelper->makeBaseUrl('full')));
        $channel->appendChild($atomSelfLink);
        $channel->appendChild($doc->createElement('webMaster', $this->_currentSession['user']['mail'].' ('.$this->_currentSession['user']['firstname'].' '.$this->_currentSession['user']['lastname'].')'));
        $channel->appendChild($doc->createElement('pubDate', date('r')));
        $rss->appendChild($channel);

        // Retrieve full spots so we can show images for spots etc.
        foreach ($spotsTmp['list'] as $spotHeaders) {
            try {
                $spot = $this->_tplHelper->getFullSpot($spotHeaders['messageid'], false);

                /*
                 * We supress the error by using this ugly operator simply because the library
                 * sometimes gives an notice and we cannot be bothered to fix it, but it does
                 * give an incorrect and unusable RSS feed
                 */
                $spot = @$this->_tplHelper->formatSpot($spot);

                $title = str_replace(['<', '>', '&'], ['&#x3C;', '&#x3E;', '&amp;'], $spot['title']);
                $poster = (empty($spot['spotterid'])) ? $spot['poster'] : $spot['poster'].' ('.$spot['spotterid'].')';

                $guid = $doc->createElement('guid', $spot['messageid']);
                $guid->setAttribute('isPermaLink', 'false');

                $description = $doc->createElement('description');
                $descriptionCdata = $doc->createCDATASection($spot['description'].'<br /><font color="#ca0000">Door: '.$poster.'</font>');
                $description->appendChild($descriptionCdata);

                $item = $doc->createElement('item');
                $item->appendChild($doc->createElement('title', $title));
                $item->appendChild($guid);
                $item->appendChild($doc->createElement('link', $this->_tplHelper->makeBaseUrl('full').'?page=getspot&amp;messageid='.urlencode($spot['messageid']).$this->_tplHelper->makeApiRequestString()));
                $item->appendChild($description);
                $item->appendChild($doc->createElement('author', $spot['messageid'].' ('.$poster.')'));
                $item->appendChild($doc->createElement('pubDate', date('r', $spot['stamp'])));
                $item->appendChild($doc->createElement('category', SpotCategories::HeadCat2Desc($spot['category']).': '.SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata'])));

                $enclosure = $doc->createElement('enclosure');
                $enclosure->setAttribute('url', html_entity_decode($this->_tplHelper->makeNzbUrl($spot)));
                $enclosure->setAttribute('length', $spot['filesize']);
                switch ($nzbhandling['prepare_action']) {
                    case 'zip': $enclosure->setAttribute('type', 'application/zip'); break;
                    default: $enclosure->setAttribute('type', 'application/x-nzb');
                } // switch
                $item->appendChild($enclosure);

                $channel->appendChild($item);
            } // try
            catch (Exception $x) {
                // Article not found. ignore.
            } // catch
        } // foreach

        // Output XML
        $this->sendContentTypeHeader('rss');
        echo $doc->saveXML();
    }

    // render()
} // class SpotPage_rss
