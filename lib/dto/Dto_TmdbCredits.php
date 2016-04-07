<?php

class Dto_TmdbCredits {
    private $id = null;
    private $tmdbCreditId = null;
    private $name = null;
    private $tmdbId = null;

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
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $tmdbCreditId
     */
    public function setTmdbCreditId($tmdbCreditId) {
        $this->tmdbCreditId = $tmdbCreditId;
    }

    /**
     * @return mixed
     */
    public function getTmdbCreditId() {
        return $this->tmdbCreditId;
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

}