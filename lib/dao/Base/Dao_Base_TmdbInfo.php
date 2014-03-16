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

    function addCredit(Dto_TmdbCredits $credit) {
        $parameters = array(
            ':tmdb_credit_id' => array($credit->getTmdbCreditId(), PDO::PARAM_INT),
            ':name' => array($credit->getName(), PDO::PARAM_STR),
        );

        $this->_conn->upsert('tmdb_credits', $parameters, array('tmdb_credit_id'));
    } // addCredit

    function addCast(Dto_TmdbCast $cast) {
        $this->addCredit($cast);

        $parameters = array(
            ':character_name' => array($cast->getCharacterName(), PDO::PARAM_STR),
            ':tmdb_id' => array($cast->getTmdbId(), PDO::PARAM_INT),
            ':tmdb_credit_id' => array($cast->getTmdbCreditId(), PDO::PARAM_INT),
            ':tmdb_cast_id' => array($cast->getTmdbCastId(), PDO::PARAM_INT),
            ':sort_order' => array($cast->getSortOrder(), PDO::PARAM_INT),
            ':profile_path' => array($cast->getProfilePath(), PDO::PARAM_STR),
        );

        $this->_conn->sqlInsert('tmdb_cast', $parameters, array('tmdb_credit_id', 'tmdb_cast_id', 'tmdb_id'));
    } // addCast()

    function addCrew(Dto_TmdbCrew $crew) {
        $this->addCredit($crew);

        $parameters = array(
            ':tmdb_id' => array($crew->getTmdbId(), PDO::PARAM_INT),
            ':tmdb_credit_id' => array($crew->getTmdbCreditId(), PDO::PARAM_INT),
            ':department' => array($crew->getDepartment(), PDO::PARAM_STR),
            ':job' => array($crew->getJob(), PDO::PARAM_STR),
            ':profile_path' => array($crew->getProfilePath(), PDO::PARAM_STR),
        );

        $this->_conn->upsert('tmdb_crew', $parameters, array('tmdb_credit_id'));
    } // addCrew()

    function addImage(Dto_TmdbImage $image) {
        $parameters = array(
            ':tmdb_id' => array($image->getTmdbId(), PDO::PARAM_INT),
            ':image_type' => array($image->getImageType(), PDO::PARAM_STR),
            ':aspect_ratio' => array($image->getAspectRatio(), PDO::PARAM_INT),
            ':file_path' => array($image->getFilePath(), PDO::PARAM_STR),
            ':height' => array($image->getHeight(), PDO::PARAM_INT),
            ':width' => array($image->getWidth(), PDO::PARAM_INT),
        );

        $this->_conn->sqlInsert('tmdb_images', $parameters);
    } // addImage()

    function addTrailer(Dto_TmdbTrailer $trailer) {
        $parameters = array(
            ':tmdb_id' => array($trailer->getTmdbId(), PDO::PARAM_INT),
            ':name' => array($trailer->getName(), PDO::PARAM_STR),
            ':size' => array($trailer->getSize(), PDO::PARAM_INT),
            ':source' => array($trailer->getSource(), PDO::PARAM_STR),
            ':type' => array($trailer->getType(), PDO::PARAM_STR),
        );

        $this->_conn->sqlInsert('tmdb_trailers', $parameters);
    } // addTrailer

    /**
     * Adds a complete TmdbInfo object with all its
     * children but removes it first.
     *
     * @param Dto_TmdbInfo $tmdb
     */
    function addInfo(Dto_TmdbInfo $tmdb) {
        $parameters = array(
            ':tmdb_id' => array($tmdb->getTmdbId(), PDO::PARAM_INT),
            ':tmdb_collection_id' => array($tmdb->getTmdbCollectionId(), PDO::PARAM_INT),
            ':tmdb_collection_name' => array($tmdb->getTmdbCollectionName(), PDO::PARAM_STR),
            ':budget' => array($tmdb->getBudget(), PDO::PARAM_INT),
            ':homepage' => array($tmdb->getHomepage(), PDO::PARAM_STR),
            ':imdb_id' => array($tmdb->getImdbId(), PDO::PARAM_STR),
            ':tmdb_title' => array($tmdb->getTmdbTitle(), PDO::PARAM_STR),
            ':overview' => array($tmdb->getOverview(), PDO::PARAM_STR),
            ':popularity' => array($tmdb->getPopularity(), PDO::PARAM_INT),
            ':release_date' => array($tmdb->getReleaseDate(), PDO::PARAM_STR),
            ':revenue' => array($tmdb->getRevenue(), PDO::PARAM_INT),
            ':runtime' => array($tmdb->getRuntime(), PDO::PARAM_INT),
            ':tagline' => array($tmdb->getTagline(), PDO::PARAM_STR),
            ':vote_average' => array($tmdb->getVoteAverage(), PDO::PARAM_INT),
            ':vote_count' => array($tmdb->getVoteCount(), PDO::PARAM_INT),
            ':last_retrieve' => array(time(), PDO::PARAM_INT),
        );

        $this->_conn->sqlInsert('tmdb_info', $parameters);

        foreach($tmdb->getTrailerList() as $trailer) {
            $this->addTrailer($trailer);
        } // foreach

        foreach($tmdb->getCastList() as $cast) {
            $this->addCast($cast);
        } // foreach

        foreach($tmdb->getCrewList() as $crew) {
            $this->addCrew($crew);
        } // foreach

        foreach($tmdb->getImageList() as $image) {
            $this->addImage($image);
        } // foreach
    } // saveInfo()

    function getTrailers($tmdbId) {
        return $this->_conn->sqlQuery('tmdb_trailers', 'Dto_TmdbTrailer', 'tmdb_id', $tmdbId);
    } // getTrailers

    function getCastList($tmdbId) {
        $additionalJoinList = array(
            array('jointype' => '',
                  'tablename' => 'tmdb_credits',
                  'tablealias' => 'tc',
                  'joincondition' => 't1.tmdb_credit_id = tc.tmdb_credit_id')
        );

        return $this->_conn->sqlQuery('tmdb_cast', 'Dto_TmdbCast', 'tmdb_id', $tmdbId, $additionalJoinList);
    } // getCastList

    function getCrewList($tmdbId) {
        $additionalJoinList = array(
            array('jointype' => '',
                'tablename' => 'tmdb_credits',
                'tablealias' => 'tc',
                'joincondition' => 't1.tmdb_credit_id = tc.tmdb_credit_id')
        );

        return $this->_conn->sqlQuery('tmdb_crew', 'Dto_TmdbCrew', 'tmdb_id', $tmdbId, $additionalJoinList);
    } // getCrewList

    function getImageList($tmdbId) {
        return $this->_conn->sqlQuery('tmdb_images', 'Dto_TmdbImage', 'tmdb_id', $tmdbId);
    } // getImageList

    function getInfo($tmdbId) {
        $tmdb = new Dto_TmdbInfo();
        $tmdb->setTmdbId($tmdbId);

        $tmpResults = $this->_conn->sqlQuery('tmdb_info', 'Dto_TmdbInfo', 'tmdb_id', $tmdbId);
        if (empty($tmpResults)) {
            return null;
        } else {
            $tmpResults[0]->setTrailerList($this->getTrailers($tmdbId));
            $tmpResults[0]->setCastList($this->getCastList($tmdbId));
            $tmpResults[0]->setImageList($this->getImageList($tmdbId));
            $tmpResults[0]->setCrewList($this->getCrewList($tmdbId));

            return $tmpResults[0];
        } // else
    } // getInfo

} # Dao_Base_TmdbInfo
