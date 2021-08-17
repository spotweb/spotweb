<?php

use imdbphp\Imdb;

class Services_MediaInformation_Imdb extends Services_MediaInformation_Abs
{
    /**
     * @return Dto_MediaInformation|void
     */
    public function retrieveInfo()
    {
        $mediaInfo = new Dto_MediaInformation();
        $mediaInfo->setValid(false);

        $config = new \Imdb\Config();
        $config->usecache = false;
        $config->storecache = false;
        $config->throwHttpExceptions = false;

        $titleobj = new \Imdb\Title($this->getSearchid(), $config);
        $mediaInfo->setTitle($titleobj->title());
        $mediaInfo->setReleaseYear($titleobj->year());

        $mediaInfo->setValid(true);

        return $mediaInfo;
        /*
         * Create URL to retrive info from, for this provider
         * we only support direct id lookups for now
         */
    }

    // retrieveInfo
} // class Services_MediaInformation_Imdb
