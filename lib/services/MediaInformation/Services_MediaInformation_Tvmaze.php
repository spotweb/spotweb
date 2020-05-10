<?php

class Services_MediaInformation_Tvmaze extends Services_MediaInformation_Abs
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
        if ($this->getSearchName() == 'tvmaze') {
            $url = 'http://api.tvmaze.com/shows/'.$this->getSearchid();
        } else {
            $url = 'http://api.tvmaze.com/lookup/shows?tvrage='.$this->getSearchid();
        } //if

        list($http_code, $tvmaze) = $this->_httpProvider->performCachedGet($url, false, 31 * 24 * 60 * 60);

        if (empty($tvmaze)) {
            return $mediaInfo;
        } // if

        // decode remote content
        $jsonoutp = json_decode($tvmaze);

        /*
         * TV maze doesn't returns an 404 or something alike when the content is
         * not found, so we try to mimic this ourselves.
         */
        // TVMaze geeft geen 404 indien niet gevonden, dus vangen we dat zelf netjes op

        if (!$jsonoutp) {
            return $mediaInfo;
        } // if

        /*
         * Get the actual episode title
         */
        $mediaInfo->setTitle($jsonoutp->{'name'});

        $mediaInfo->setValid(true);

        return $mediaInfo;
    }

    // retrieveInfo
} // class Services_MediaInformation_Tvmaze
