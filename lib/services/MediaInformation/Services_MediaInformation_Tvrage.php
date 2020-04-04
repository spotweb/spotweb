<?php

class Services_MediaInformation_Tvrage extends Services_MediaInformation_Abs
{
    /**
     * @return Dto_MediaInformation|void
     */
    public function retrieveInfo()
    {
        $mediaInfo = new Dto_MediaInformation();
        $mediaInfo->setValid(false);

        /*
         * Create URL to retrive info from, for this provider
         * we only support direct id lookups for now
         */
        $url = 'http://services.tvrage.com/feeds/showinfo.php?sid='.$this->getSearchid().'/';
        list($http_code, $tvrage) = $this->_httpProvider->performCachedGet($url, false, 31 * 24 * 60 * 60);
        if (empty($tvrage)) {
            return $mediaInfo;
        } // if

        // fetch remote content
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($tvrage);
        $showTitle = $dom->getElementsByTagName('showname');

        /*
         * TV rage doesn't returns an 404 or something alike when the content is
         * not found, so we try to mimic this ourselves.
         */
        // TVRage geeft geen 404 indien niet gevonden, dus vangen we dat zelf netjes op
        if (!@$showTitle->item(0)->nodeValue) {
            return $mediaInfo;
        } // if

        /*
         * Get the actual episode title
         */
        $mediaInfo->setTitle($showTitle->item(0)->nodeValue);

        $mediaInfo->setValid(true);

        return $mediaInfo;
    }

    // retrieveInfo
} // class Services_MediaInformation_Tvrage
