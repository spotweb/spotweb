<?php

//use imdbphp\Imdb;

class Services_MediaInformation_Imdb extends Services_MediaInformation_Abs
{
    /**
     * @return Dto_MediaInformation|void
     */
    //public function retrieveInfo()
    //{
    //    $mediaInfo = new Dto_MediaInformation();
    //    $mediaInfo->setValid(false);

    //    $config = new \Imdb\Config();
    //    $config->usecache = false;
    //    $config->storecache = false;
    //    $config->throwHttpExceptions = true;

    //    $titleobj = new \Imdb\Title($this->getSearchid(), $config);
    //    $mediaInfo->setTitle($titleobj->title());
    //    $mediaInfo->setReleaseYear($titleobj->year());

    //    $mediaInfo->setValid(true);

    //    return $mediaInfo;
    //    /*
    //     * Create URL to retrive info from, for this provider
    //     * we only support direct id lookups for now
    //     */
    //}

    public function retrieveinfo()
    {
        $token = $this->getCurrentsession()['user']['prefs']['tmdb_api_key'];
        if (trim($token) === '') {
            throw new Exception('no tmdb api key provided in user settings');
        }
        $mediaInfo = new Dto_MediaInformation();
        $mediaInfo->setValid(false);
        $endPoint = 'find/tt'.$this->getSearchid().'?external_source=imdb_id';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.themoviedb.org/3/'.$endPoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer '.$token,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:117.0) Gecko/20100101 Firefox/117.0',
                'Cache-Control: no-cache',
                'Host: api.themoviedb.org',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
            ],
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);

        if (PHP_MAJOR_VERSION < 8) {
            // deprecated code
            curl_close($ch);
        }
        
        if ($err) {
            throw new Exception($err);
        } else {
            $data = json_decode($response, true);
            $statusmsg = $data['status_message'] ?? null;
            if ($statusmsg) {
                throw new Exception($statusmsg);
            }
            $title = $data['movie_results'][0]['title'] ?? null;
            $original_title = $data['movie_results'][0]['original_title'] ?? null;
            if ($title) {
                $mediaInfo->setTitle($title);
                $mediaInfo->setAlternateTitle($original_title);
                $mediaInfo->setValid(true);
            } else {
                throw new Exception('no title found via tmdb');
            }
            $releaseDate = $data['movie_results'][0]['release_date'] ?? null;
            if ($releaseDate) {
                $year = (new DateTime($releaseDate))->format('Y');
                $mediaInfo->setReleaseYear($year);
                $mediaInfo->setValid(true);
            } else {
                throw new Exception('no release year found via tmdb');
            }

            return $mediaInfo;
        }
    }

    // retrieveInfo
} // class Services_MediaInformation_Imdb
