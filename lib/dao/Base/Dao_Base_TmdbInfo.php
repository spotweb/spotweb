<?php

class Dao_Base_TmdbInfo implements Dao_TmdbInfo {
    protected $_conn;

    /*
     * constructs a new Dao_Base_TmdbInfo object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn) {
        $this->_conn = $conn;
    } # ctor

    protected function saveCredit(Dto_TmdbCredits $credit) {
        $parameters = array(
            ':tmdb_credit_id' => array($credit->getTmdbCreditId(), PDO::PARAM_INT),
            ':name' => array($credit->getName(), PDO::PARAM_STR),
        );

        $this->_conn->upsert('tmdb_credits', $parameters, array('tmdb_credit_id'));
    } // saveCredit

    function saveCast(Dto_TmdbCast $cast) {
        $this->saveCredit($cast);

        $parameters = array(
            ':charactername' => array($cast->getCharacterName(), PDO::PARAM_STR),
            ':tmdbid' => array($cast->getTmdbId(), PDO::PARAM_INT),
            ':tmdb_credit_id' => array($cast->getTmdbCreditId(), PDO::PARAM_INT),
            ':tmdb_cast_id' => array($cast->getTmdbCastId(), PDO::PARAM_INT),
            ':sortorder' => array($cast->getSortOrder(), PDO::PARAM_INT),
        );

        $this->_conn->upsert('tmdb_cast', $parameters, array('tmdb_cast_id'));
    } // saveCast()

    function saveCrew(Dto_TmdbCrew $crew) {
        $this->saveCredit($crew);

        $parameters = array(
            ':tmdbid' => array($crew->getTmdbId(), PDO::PARAM_INT),
            ':tmdb_credit_id' => array($crew->getTmdbCreditId(), PDO::PARAM_INT),
            ':department' => array($crew->getDepartment(), PDO::PARAM_STR),
            ':job' => array($crew->getJob(), PDO::PARAM_STR)
        );

        $this->_conn->upsert('tmdb_crew', $parameters, array('tmdb_credit_id'));
    } // saveCrew()

    function saveImage(Dto_TmdbImage $image) {
        $parameters = array(
            ':tmdbid' => array($image->getTmdbId(), PDO::PARAM_INT),
            ':tmdb_credit_id' => array($image->getTmdbCreditId(), PDO::PARAM_INT),
            ':imagetype' => array($image->getImageType(), PDO::PARAM_STR),
            ':aspect_ratio' => array($image->getAspectRatio(), PDO::PARAM_INT),
            ':file_path' => array($image->getFilePath(), PDO::PARAM_STR),
            ':height' => array($image->getHeight(), PDO::PARAM_INT),
            ':width' => array($image->getWidth(), PDO::PARAM_INT),
        );

        $this->_conn->upsert('tmdb_images', $parameters, array('file_path'));
    } // saveImage()


    function saveTrailer(Dto_TmdbTrailer $trailer) {
        $parameters = array(
            ':tmdbid' => array($trailer->getTmdbId(), PDO::PARAM_INT),
            ':name' => array($trailer->getName(), PDO::PARAM_STR),
            ':size' => array($trailer->getSize(), PDO::PARAM_INT),
            ':source' => array($trailer->getSource(), PDO::PARAM_STR),
            ':type' => array($trailer->getType(), PDO::PARAM_STR),
        );

        # $this->_conn->upsert('tmdb_trailers', $parameters, array('tmdbid', 'name', 'size', 'source', 'type'));
        $this->_conn->upsert('tmdb_trailers', $parameters, array('tmdbid'));
    } // saveTrailer

    /**
     * Saves a complete TmdbInfo object with all its
     * children.
     *
     * @param Dto_TmdbInfo $tmdb
     */
    function saveInfo(Dto_TmdbInfo $tmdb) {
        $parameters = array(
            ':tmdbid' => array($tmdb->getTmdbId(), PDO::PARAM_INT),
            ':tmdbcollection_id' => array($tmdb->getCollectionId(), PDO::PARAM_INT),
            ':tmdbcollection_name' => array($tmdb->getCollectionName(), PDO::PARAM_STR),
            ':budget' => array($tmdb->getBudget(), PDO::PARAM_INT),
            ':homepage' => array($tmdb->getHomepage(), PDO::PARAM_STR),
            ':imdb_id' => array($tmdb->getImdbDb(), PDO::PARAM_STR),
            ':tmdbtitle' => array($tmdb->getTmdbTitle(), PDO::PARAM_STR),
            ':overview' => array($tmdb->getOverview(), PDO::PARAM_STR),
            ':popularity' => array($tmdb->getPopularity(), PDO::PARAM_INT),
            ':release_date' => array($tmdb->getReleaseDate(), PDO::PARAM_STR),
            ':revenue' => array($tmdb->getRevenue(), PDO::PARAM_INT),
            ':runtime' => array($tmdb->getRuntime(), PDO::PARAM_INT),
            ':tagline' => array($tmdb->getTagline(), PDO::PARAM_STR),
            ':vote_average' => array($tmdb->getVoteAverage(), PDO::PARAM_INT),
            ':vote_count' => array($tmdb->getVoteCount(), PDO::PARAM_INT),
            ':lastretrieve' => array(time(), PDO::PARAM_INT),
        );

        $this->_conn->upsert('tmdb_info', $parameters, array('tmdbid'));

        foreach($tmdb->getTrailerList() as $trailer) {
            $this->saveTrailer($trailer);
        } // foreach

        foreach($tmdb->getCastList() as $cast) {
            $this->saveCast($cast);
        } // foreach

        foreach($tmdb->getCrewList() as $crew) {
            $this->saveCrew($crew);
        } // foreach

        foreach($tmdb->getImageList() as $image) {
            $this->saveImage($image);
        } // foreach
    } // saveInfo()

    function getSpecificCredit($tmdbCreditId) {
        // TODO: Implement getSpecificCredit() method.
    }

    function getSpecificCast($tmdbCastId) {
        // TODO: Implement getSpecificCast() method.
    }

    function getSpecificCrew($tmdbCrewId) {
        // TODO: Implement getSpecificCrew() method.
    }

    function findPosterImages($tmdbId) {
        // TODO: Implement findPosterImages() method.
    }

    function findPosterImage($filePath) {
        // TODO: Implement findPosterImage() method.
    }

    function findBackDropImages($tmdbId) {
        // TODO: Implement findBackDropImages() method.
    }

    function findBackDropImage($filePath) {
        // TODO: Implement findBackDropImage() method.
    }

    function findTrailers($tmdbId) {
        // TODO: Implement findTrailers() method.
    }

    function findTrailer($name, $source, $size) {
        // TODO: Implement findTrailer() method.
    }
} # Dao_Base_TmdbInfo
