<?php

abstract class Services_MediaInformation_Abs {
    /**
     * @var Services_Providers_Http
     */
    protected $_httpProvider;

    /**
     * @var Dao_Cache
     */
    protected $_cacheDao;
    /**
     * @var Services_Settings_Container
     */
    protected $_settings;

    /*
     * Contains the name of string for the item we are looking for
     * @var string
     */
    private $_searchName;

    /**
     * Contains the id used by the directory service internally for reference
     * @var int
     */
    private $_searchid;


    public function __construct(Dao_cache $cacheDao, Services_Settings_Container $settings) {
        $this->_cacheDao = $cacheDao;
        $this->_settings = $settings;
        $this->_httpProvider = new Services_Providers_Http($cacheDao);
    } # ctor

    public function setSearchid($id) {
        $this->_searchid = $id;
    } # setSearchId

    public function getSearchid() {
        return $this->_searchid;
    } # getSearchId

    public function setSearchName($s) {
        $this->_name = $s;
    } # setSeearchName

    public function getSearchName() {
        return $this->_searchName;
    } # getSearchName

    /**
     * @return Dto_MediaInformation
     */
    abstract function retrieveInfo();

} # class Services_MediaInformation_Abs
