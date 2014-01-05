<?php

class Dto_TmdbImage {
    private $id = null;
    private $tmdbId = null;
    private $tmdbCreditId = null;
    private $imageType = null;
    private $aspectRatio = null;
    private $filePath = null;
    private $height = null;
    private $width = null;


    public function __construct($imageType = null, $filePath = null) {
        $this->setImageType($imageType);
        $this->setFilePath($filePath);
    } // ctor

    /**
     * @param mixed $aspectRatio
     */
    public function setAspectRatio($aspectRatio) {
        $this->aspectRatio = $aspectRatio;
    }

    /**
     * @return mixed
     */
    public function getAspectRatio() {
        return $this->aspectRatio;
    }

    /**
     * @param mixed $filePath
     */
    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }

    /**
     * @return mixed
     */
    public function getFilePath() {
        return $this->filePath;
    }

    /**
     * @param mixed $height
     */
    public function setHeight($height) {
        $this->height = $height;
    }

    /**
     * @return mixed
     */
    public function getHeight() {
        return $this->height;
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
     * @param mixed $imageType
     */
    public function setImageType($imageType) {
        $this->imageType = $imageType;
    }

    /**
     * @return mixed
     */
    public function getImageType() {
        return $this->imageType;
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

    /**
     * @param mixed $width
     */
    public function setWidth($width) {
        $this->width = $width;
    }

    /**
     * @return mixed
     */
    public function getWidth() {
        return $this->width;
    }


}