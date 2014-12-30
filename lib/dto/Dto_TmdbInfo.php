<?php

class Dto_TmdbInfo {
    private $id = null;
    private $tmdbId = null;
    private $tmdbCollectionId = null;
    private $tmdbCollectionName = null;
    private $budget = null;
    private $homepage = null;
    private $imdbId = null;
    private $tvRageId = null;
    private $tmdbTitle = null;
    private $overview = null;
    private $popularity = null;
    private $releaseDate = null;
    private $revenue = null;
    private $runtime = null;
    private $tagline = null;
    private $voteAverage = null;
    private $voteCount = null;
    private $lastretrieve = null;

    private $trailerList = array();
    private $castList = array();
    private $crewList = array();
    private $imageList = array();

    /**
     * @param mixed $budget
     */
    public function setBudget($budget) {
        $this->budget = $budget;
    }

    /**
     * @return mixed
     */
    public function getBudget() {
        return $this->budget;
    }

    /**
     * @param mixed $collectionId
     */
    public function setTmdbCollectionId($collectionId) {
        $this->tmdbCollectionId = $collectionId;
    }

    /**
     * @return mixed
     */
    public function getTmdbCollectionId() {
        return $this->tmdbCollectionId;
    }

    /**
     * @param mixed $collectionName
     */
    public function setTmdbCollectionName($collectionName) {
        $this->tmdbCollectionName = $collectionName;
    }

    /**
     * @return mixed
     */
    public function getTmdbCollectionName() {
        return $this->tmdbCollectionName;
    }

    /**
     * @param mixed $creditList
     */
    public function setCreditList($creditList) {
        $this->creditList = $creditList;
    }

    /**
     * @return mixed
     */
    public function getCreditList() {
        return $this->creditList;
    }

    /**
     * @param mixed $homepage
     */
    public function setHomepage($homepage) {
        $this->homepage = $homepage;
    }

    /**
     * @return mixed
     */
    public function getHomepage() {
        return $this->homepage;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $imageList
     */
    public function setImageList($imageList) {
        $this->imageList = $imageList;
    }

    /**
     * @return mixed
     */
    public function getImageList() {
        return $this->imageList;
    }

    /**
     * @param mixed $imdbDb
     */
    public function setImdbId($imdbDb) {
        $this->imdbId = $imdbDb;
    }

    public function setTvRageid($tvRageId) {
	$this->tvRageId = $tvRageId;
    }

    /**
     * @return mixed
     */
    public function getImdbId() {
        return $this->imdbId;
    }

    public function getTvRageId() {
	return $this->tvRageId;
    }

    /**
     * @param mixed $lastretrieve
     */
    public function setLastretrieve($lastretrieve) {
        $this->lastretrieve = $lastretrieve;
    }

    /**
     * @return mixed
     */
    public function getLastretrieve() {
        return $this->lastretrieve;
    }

    /**
     * @param mixed $overview
     */
    public function setOverview($overview) {
        $this->overview = $overview;
    }

    /**
     * @return mixed
     */
    public function getOverview() {
        return $this->overview;
    }

    /**
     * @param mixed $popularity
     */
    public function setPopularity($popularity) {
        $this->popularity = $popularity;
    }

    /**
     * @return mixed
     */
    public function getPopularity() {
        return $this->popularity;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating) {
        $this->rating = $rating;
    }

    /**
     * @return mixed
     */
    public function getRating() {
        return $this->rating;
    }

    /**
     * @param mixed $releaseDate
     */
    public function setReleaseDate($releaseDate) {
        $this->releaseDate = $releaseDate;
    }

    /**
     * @return mixed
     */
    public function getReleaseDate() {
        return $this->releaseDate;
    }

    /**
     * @param mixed $revenu
     */
    public function setRevenue($revenu) {
        $this->revenue = $revenu;
    }

    /**
     * @return mixed
     */
    public function getRevenue() {
        return $this->revenue;
    }

    /**
     * @param mixed $runtime
     */
    public function setRuntime($runtime) {
        $this->runtime = $runtime;
    }

    /**
     * @return mixed
     */
    public function getRuntime() {
        return $this->runtime;
    }

    /**
     * @param mixed $tagline
     */
    public function setTagline($tagline) {
        $this->tagline = $tagline;
    }

    /**
     * @return mixed
     */
    public function getTagline() {
        return $this->tagline;
    }

    /**
     * @param mixed $tmdbId
     */
    public function setTmdbId($tmdbId) {
        $this->tmdbId = $tmdbId;
    }

    /**
     * @return mixed
     */
    public function getTmdbId() {
        return $this->tmdbId;
    }

    /**
     * @param mixed $tmdbTitle
     */
    public function setTmdbTitle($tmdbTitle) {
        $this->tmdbTitle = $tmdbTitle;
    }

    /**
     * @return mixed
     */
    public function getTmdbTitle() {
        return $this->tmdbTitle;
    }

    /**
     * @param mixed $trailerList
     */
    public function setTrailerList($trailerList) {
        $this->trailerList = $trailerList;
    }

    /**
     * @return mixed
     */
    public function getTrailerList() {
        return $this->trailerList;
    }

    /**
     * @param mixed $voteAverage
     */
    public function setVoteAverage($voteAverage) {
        $this->voteAverage = $voteAverage;
    }

    /**
     * @return mixed
     */
    public function getVoteAverage() {
        return $this->voteAverage;
    }

    /**
     * @param mixed $voteCount
     */
    public function setVoteCount($voteCount) {
        $this->voteCount = $voteCount;
    }

    /**
     * @return mixed
     */
    public function getVoteCount() {
        return $this->voteCount;
    }


    public function addTrailer(Dto_TmdbTrailer $trailer) {
        $this->trailerList[] = $trailer;
    } // addTrailer

    public function addCastMember(Dto_TmdbCast $cast) {
        $this->castList[] = $cast;;
    } // addTrailer

    public function addCrewMember(Dto_TmdbCrew $crew) {
        $this->crewList[] = $crew;
    }

    public function addImage(Dto_TmdbImage $image) {
        $this->imageList[] = $image;
    }

    /**
     * @param mixed $castList
     */
    public function setCastList($castList) {
        $this->castList = $castList;
    }

    /**
     * @return mixed
     */
    public function getCastList() {
        return $this->castList;
    }

    /**
     * @param mixed $crewList
     */
    public function setCrewList($crewList) {
        $this->crewList = $crewList;
    }

    /**
     * @return mixed
     */
    public function getCrewList() {
        return $this->crewList;
    }


} // Dto_TmdbInfo

