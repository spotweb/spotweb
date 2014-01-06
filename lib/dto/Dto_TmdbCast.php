<?php

class Dto_TmdbCast extends Dto_TmdbCredits {
    private $internalCastId = null;
    private $tmdbCastId = null;
    private $characterName = null;
    private $sortOrder = null;

    /**
     * @param mixed $characterName
     */
    public function setCharacterName($characterName) {
        $this->characterName = $characterName;
    }

    /**
     * @return mixed
     */
    public function getCharacterName() {
        return $this->characterName;
    }

    /**
     * @param mixed $id
     */
    public function setInternalCastId($id) {
        $this->internalCastId = $id;
    }

    /**
     * @return mixed
     */
    public function getInternalCastId() {
        return $this->internalCastId;
    }

    /**
     * @param mixed $sortOrder
     */
    public function setSortOrder($sortOrder) {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return mixed
     */
    public function getSortOrder() {
        return $this->sortOrder;
    }

    /**
     * @param mixed $tmdbCastId
     */
    public function setTmdbCastId($tmdbCastId) {
        $this->tmdbCastId = $tmdbCastId;
    }

    /**
     * @return mixed
     */
    public function getTmdbCastId() {
        return $this->tmdbCastId;
    }

}
