<?php

class Dto_CollectionInfo {
    /**
     * Cleaned up title
     *
     * @var
     */
    private $title;
    /**
     * When the collection is part of a TV series, store the season number
     *
     * @var string
     */
    private $season;
    /**
     * When the collection is part of a TV series, store the episode number
     *
     * @var string
     */
    private $episode;
    /**
     * @var string
     */
    private $year;
    /**
     * @var int Id in the database of the master collection
     */
    private $mcId = null;
    /**
     * @var int Id in the database of this collection
     */
    private $id = null;

    /**
     * @param $title
     * @param $season
     * @param $episode
     * @param year
     */
    public function __construct($title, $season, $episode, $year) {
        $this->setTitle($title);
        $this->setSeason($season);
        $this->setEpisode($episode);
        $this->setYear($year);
    } // ctor

    /**
     * Compares an object to the current object but ignores database
     * id's
     */
    public function equalColl(Dto_CollectionInfo $compare) {
        // do not use equals() as we only compare a subet
        return (
                    ($this->getTitle() == $compare->getTitle()) &&
                    ($this->getSeason() == $compare->getSeason()) &&
                    ($this->getEpisode() == $compare->getEpisode()) &&
                    ($this->getYear() == $compare->getYear())
        );
    } // equalColl

    /**
     * Returns an hash code of the current object
     */
    public function hashCode() {
        return sha1(serialize($this));
    }  // hashCode

    /**
     * @param string $episode
     */
    public function setEpisode($episode) {
        $this->episode = $episode;
    }

    /**
     * @return string
     */
    public function getEpisode() {
        return $this->episode;
    }

    /**
     * @param string $season
     */
    public function setSeason($season) {
        $this->season = $season;
    }

    /**
     * @return string
     */
    public function getSeason() {
        return $this->season;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title) {
        /*
         * Make sure we only use valid UTF-8
         */
        $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $year
     */
    public function setYear($year) {
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function getYear() {
        return $this->year;
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
     * @param int $mcId
     */
    public function setMcId($mcId) {
        $this->mcId = $mcId;
    }

    /**
     * @return int
     */
    public function getMcId() {
        return $this->mcId;
    }

} 