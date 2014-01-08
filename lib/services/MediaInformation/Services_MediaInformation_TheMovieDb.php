<?php

class Services_MediaInformation_TheMovieDb extends Services_MediaInformation_Abs {
    protected $tmdb_api_key = '';

    /**
     * @return Dto_MediaInformation|void
     */
    public function retrieveInfo() {
        $daoTmdb = $this->_daoFactory->getTmdbInfo();
        $tmdb = $daoTmdb->getInfo($this->getSearchid());

        if ($tmdb == null) {
            $tmdb = $this->queryTmdbApi();

            if ($tmdb !== null) {
                $daoTmdb->addInfo($tmdb);
            } // if
        } // if

        return $tmdb;
    } // retrieveInfo()

    protected function queryTmdbApi() {
        $mediaInfo = new Dto_TmdbInfo();

        /*
         * Create URL to retriev info from, for this provider
         */
        $baseUrl = 'http://api.themoviedb.org/3/movie/' . (int)$this->getSearchid() .
            '?api_key=' . $this->_settings->get('tmdb_api_key') .
            '&append_to_response=trailers,credits,images&language=en';

        list($http_code, $tmdb) = $this->_httpProvider->performCachedGet($baseUrl, false, 365 * 24 * 60 * 60);
        if (empty($tmdb)) {
            return null;
        } # if

        /*
         * Parse the results as JSON
         */
        $tmdb = json_decode($tmdb);

        $mediaInfo->setTmdbId($tmdb->id);

        /* Movie collections from TMDB */
        if (!empty($tmdb->belongs_to_collection)) {
            $mediaInfo->setTmdbCollectionId($tmdb->belongs_to_collection->id);
            $mediaInfo->setTmdbCollectionName($tmdb->belongs_to_collection->name);
        } else {
            $mediaInfo->setTmdbCollectionId(null);
            $mediaInfo->setTmdbCollectionName(null);
        } // else

        $mediaInfo->setBudget($tmdb->budget);
        $mediaInfo->setHomepage($tmdb->homepage);
        $mediaInfo->setImdbId($tmdb->imdb_id);
        $mediaInfo->setTmdbTitle($tmdb->original_title);
        $mediaInfo->setOverview($tmdb->overview);
        $mediaInfo->setPopularity($tmdb->popularity);
        $mediaInfo->setReleaseDate($tmdb->release_date);
        $mediaInfo->setRevenue($tmdb->revenue);
        $mediaInfo->setRuntime($tmdb->runtime);
        $mediaInfo->setTagline($tmdb->tagline);
        $mediaInfo->setVoteAverage($tmdb->vote_average);
        $mediaInfo->setVoteCount($tmdb->vote_count);
        $mediaInfo->setLastretrieve(time());

        /*
         * Parse the list of trailers as individual trailers
         */
        foreach(array('quicktime', 'youtube') as $trailerType) {
            foreach($tmdb->trailers->$trailerType as $trailers) {
                $trailerDto = new Dto_TmdbTrailer();
                $trailerDto->setName($trailers->name);
                $trailerDto->setTmdbId($mediaInfo->getTmdbId());
                $trailerDto->setType($trailerType);

                /*
                 * Sometimes we do not get a sources array back, but a single
                 * source
                 */
                if (!isset($trailers->sources)) {
                    $trailerDto->setSize($trailers->size);
                    $trailerDto->setSource($trailers->source);

                    $mediaInfo->addTrailer($trailerDto);
                } else {
                    foreach($trailers->sources as $source) {
                        // need to clone the object to prvent overwriting ourselves
                        $trailerDto = clone($trailerDto);

                        $trailerDto->setSize($source->size);
                        $trailerDto->setSource($source->source);

                        $mediaInfo->addTrailer($trailerDto);
                    } // foreach
                }
            } // foreach
        } // foreach

        /*
         * And do the same for the movies' cast
         */
        foreach($tmdb->credits->cast as $crew) {
            $castDto = new Dto_TmdbCast();
            $castDto->setTmdbCastId($crew->cast_id);
            $castDto->setTmdbId($mediaInfo->getTmdbId());
            $castDto->setTmdbCreditId($crew->id);
            $castDto->setName($crew->name);
            $castDto->setSortOrder($crew->order);
            $castDto->setCharacterName($crew->character);
            $castDto->setProfilePath($crew->profile_path);

            $mediaInfo->addCastMember($castDto);
        } // foreach

        /*
         * And do the same for the credits
         */
        foreach($tmdb->credits->crew as $crew) {
            $crewDto = new Dto_TmdbCrew();
            $crewDto->setTmdbCreditId($crew->id);
            $crewDto->setTmdbId($mediaInfo->getTmdbId());
            $crewDto->setName($crew->name);
            $crewDto->setDepartment($crew->department);
            $crewDto->setJob($crew->job);
            $castDto->setProfilePath($crew->profile_path);

            $mediaInfo->addCrewMember($crewDto);
        } // foreach

        /*
         * And eventually, the images. In the JSON object we get,
         * we get most images seperately, but some are contained in the
         * info object, so we fake add them.
         */
        $imageDto = new Dto_TmdbImage('backdrops', $tmdb->backdrop_path);
        $mediaInfo->addImage($imageDto);

        $imageDto = new Dto_TmdbImage('posters', $tmdb->poster_path);
        $mediaInfo->addImage($imageDto);

       /*
        * Parse the list of trailers as individual trailers
        */
        foreach(array('backdrops', 'posters') as $imageType) {
            foreach($tmdb->images->$imageType as $image) {
                $imageDto = new Dto_TmdbImage();

                $imageDto->setTmdbCreditId(null);
                $imageDto->setTmdbId($mediaInfo->getTmdbId());
                $imageDto->setAspectRatio($image->aspect_ratio);
                $imageDto->setFilePath($image->file_path);
                $imageDto->setHeight($image->height);
                $imageDto->setWidth($image->width);
                $imageDto->setImageType($imageType);

                $mediaInfo->addImage($imageDto);
            } // foreach
        } // foreach


        return $mediaInfo;
    } # queryTmdbApi

} 