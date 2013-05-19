<?php

class Dto_MediaInformation {
    private $_title = null;
    private $_alternateTitle = null;
    private $_releaseYear = null;
    private $_valid = false;

    /**
     * @param mixed $valid
     */
    public function setValid($valid)
    {
        $this->_valid = $valid;
    }

    /**
     * @return mixed
     */
    public function isValid()
    {
        return ($this->_valid) && ($this->_title != null);
    }

    /**
     * @param null $alternateTitle
     */
    public function setAlternateTitle($alternateTitle)
    {
        $this->_alternateTitle = trim($alternateTitle);
    }

    /**
     * @return null
     */
    public function getAlternateTitle()
    {
        return $this->_alternateTitle;
    }

    /**
     * @param null $releaseYear
     */
    public function setReleaseYear($releaseYear)
    {
        $this->_releaseYear = trim($releaseYear);
    }

    /**
     * @return null
     */
    public function getReleaseYear()
    {
        return $this->_releaseYear;
    }

    /**
     * @param null $title
     */
    public function setTitle($title)
    {
        $this->_title = trim($title);
    }

    /**
     * @return null
     */
    public function getTitle()
    {
        return $this->_title;
    }
} # Dto_MediaInformation