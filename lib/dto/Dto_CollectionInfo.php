<?php

class Dto_CollectionInfo {
    const CATTYPE_BOOKS         = 1;
    const CATTYPE_GAMES         = 2;
    const CATTYPE_MOVIES        = 3;
    const CATTYPE_MUSIC         = 4;

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
     * @var int Category types
     */
    private $catType;
    /**
     * @var int Current part number of a collection
     */
    private $partsCurrent = null;
    /**
     * @var int Total amount of parts available
     */
    private $partsTotal = null;

    /**
     * @param $catType
     * @param $title
     * @param $season
     * @param $episode
     * @param year
     * @param $partsCurrent
     * @param $partsTotal
     */
    public function __construct($catType, $title, $season, $episode, $year, $partsCurrent, $partsTotal) {
        $this->setCatType($catType);
        $this->setTitle($title);
        $this->setSeason($season);
        $this->setEpisode($episode);
        $this->setYear($year);
        $this->setPartsCurrent($partsCurrent);
        $this->setPartsTotal($partsTotal);
    } // ctor

    /**
     * Compares an object to the current object but ignores database
     * id's
     */
    public function equalColl(Dto_CollectionInfo $compare) {
        // do not use equals() as we only compare a subet
        return (
                    ($this->getCatType() == $compare->getCatType()) &&
                    ($this->getTitle() == $compare->getTitle()) &&
                    ($this->getSeason() == $compare->getSeason()) &&
                    ($this->getEpisode() == $compare->getEpisode()) &&
                    ($this->getYear() == $compare->getYear()) &&
                    ($this->getPartsCurrent() == $compare->getPartsCurrent()) &&
                    ($this->getPartsTotal() == $compare->getPartsTotal())
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

    /**
     * @param int $catType
     */
    public function setCatType($catType) {
        $this->catType = $catType;
    }

    /**
     * @return int
     */
    public function getCatType() {
        return $this->catType;
    }

    /**
     * @param int $partsCurrent
     */
    public function setPartsCurrent($partsCurrent) {
        $this->partsCurrent = $partsCurrent;
    }

    /**
     * @return int
     */
    public function getPartsCurrent() {
        return $this->partsCurrent;
    }

    /**
     * @param int $partsTotal
     */
    public function setPartsTotal($partsTotal) {
        $this->partsTotal = $partsTotal;
    }

    /**
     * @return int
     */
    public function getPartsTotal() {
        return $this->partsTotal;
    }

} 