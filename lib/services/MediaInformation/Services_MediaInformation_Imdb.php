<?php

class Services_MediaInformation_Imdb extends Services_MediaInformation_Abs {

    /**
     * @return Dto_MediaInformation|void
     */
    public function retrieveInfo() {
        $mediaInfo = new Dto_MediaInformation();
        $mediaInfo->setValid(false);

        /*
         * Create URL to retrive info from, for this provider
         * we only support direct id lookups for now
         */
        $url = 'http://www.imdb.com/title/tt' . $this->getSearchid() . '/';
        list($http_code, $imdb) = $this->_httpProvider->performCachedGet($url, false, 31*24*60*60);

        if (empty($imdb)) {
            return $mediaInfo;
        } # if

        /*
         * Extract movie title from system
         */
        /* preg_match('/<h1 itemprop="name" class="">([^\<]*)<span/ms', $imdb, $movieTitle); */
         
        /* Extract the release date from the IMDB info page */
        if (preg_match('/\<a href="\/year\/([0-9]{4})/ms', $imdb, $movieReleaseDate)) {
            $mediaInfo->setReleaseYear($movieReleaseDate[1]);
        } # if
        preg_match('/\<meta property=\'og:title\' content="([^<]*?)\([0-9]*?\)/ms',$imdb,$movieTitle);
        
        if (isset($movieTitle[1])) {
            $movieTitle[1] = trim($movieTitle[1]);
        	$mediaInfo->setTitle($movieTitle[1]);
        } # if

        // imdb sometimes returns the title translated, if so, pass the original title as well
        preg_match('/<meta name="title" content="([^<]*?)\([0-9]*?\)/ms', $imdb, $originalTitle);
        if ((!empty($originalTitle)) && (trim($originalTitle[1]) != $movieTitle[1])) {
            $mediaInfo->setAlternateTitle(trim($originalTitle[1]));
        } # if

        $mediaInfo->setValid(true);
        return $mediaInfo;
    } # retrieveInfo

} # class Services_MediaInformation_Imdb
