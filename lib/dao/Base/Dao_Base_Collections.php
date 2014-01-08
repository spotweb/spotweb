<?php

class Dao_Base_Collections implements Dao_Collections {
    /**
     * @var dbeng_abs
     */
    protected $_conn;

    /**
     * Keep a cached list of static mastercollection mappings
     *
     * @var array
     */
    protected static $mc_CacheList = array();
    /**
     * Signals whether we started with a full cache, eg, we
     * have everything that is in the database.
     *
     * @var bool
     */
    protected static $startedWithFullCacheLoad = false;
    /**
     * List of mastercollections to update with the latest stamp and
     * spots' id
     *
     * @var array
     */
    protected $mc_updateMcStamp = array();

    /*
     * constructs a new Dao_Base_Collections object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn) {
        $this->_conn = $conn;
    } # ctor


    private function fixCollectionYear($title, $catType, $year) {
        /*
         * We try to be something intelligent about movies without a year,
         * because they usually /do/ belong to another collection.
         */
        if (($year === null) && ($catType == Dto_CollectionInfo::CATTYPE_MOVIES)) {
            /*
             * For each movie where there is no year, we try to see if we can find
             * the most recent year entry as it might belong to that one. Ugly, but
             * we have to decide somehow.
             */
            $idxStr = $title . '|' . $catType;
            if (isset(self::$mc_CacheList[$idxStr])) {
                foreach(array_keys(self::$mc_CacheList[$idxStr]) as $cntYear) {
                    if (($cntYear !== null) && (($cntYear > $year) || ($year === null))) {
                        $year = $cntYear;
                    } // if
                } // foreach
            } // if
        } // if

        return $year;
    } // fixCollectionYear

    /**
     * Create the id to the array
     *
     * @param $title
     * @param $catType
     * @param $year
     * @return array
     */
    private function makeLocalCacheKey($title, $catType, $year) {
        return array($title . '|' . $catType,
                     $year);
    } // makeLocalCacheKey

    /**
     * Returns whether a given item is in the local
     * mastercollections cache
     */
    private function isInLocalCache($title, $catType, $year) {
        list($idKey1, $idKey2) = $this->makeLocalCacheKey($title, $catType, $year);

        return ((isset(self::$mc_CacheList[$idKey1])) && (isset(self::$mc_CacheList[$idKey1][$idKey2])));
    } // isInLocalCache

    /**
     * Adds an item to the local msatercollectioncache
     *
     * @param $mcId
     * @param $title
     * @param $catType
     * @param $year
     */
    private function addMcToLocalCache($mcId, $title, $catType, $year) {
        list($idKey1, $idKey2) = $this->makeLocalCacheKey($title, $catType, $year);

        # echo '  Adding MC to local cache: (' . $title. '),(' . $catType . '),('. $year . ')' . PHP_EOL;

        self::$mc_CacheList[$idKey1][$idKey2] =
            array('mcid' => $mcId,
                  'cattype' =>  $catType,
                  'collections' => array(),
            );
    } // addMcToLocalCache

    /**
     * Returns the mastercollection id from the local cache
     *
     * @param $title
     * @param $catType
     * @param $year
     * @return mixed
     */
    private function getMcIdFromLocalCache($title, $catType, $year) {
        list($idKey1, $idKey2) = $this->makeLocalCacheKey($title, $catType, $year);
        return self::$mc_CacheList[$idKey1][$idKey2]['mcid'];
    } // getMcIdFromLocalCache

    /**
     * Adds a specific collectoin to the local cache
     *
     * @param $title
     * @param $catType
     * @param $year
     * @param Dto_CollectionInfo $collection
     */
    private function addSpecificCollectionToLocalCache($title, $catType, $year, Dto_CollectionInfo $collection) {
        list($idKey1, $idKey2) = $this->makeLocalCacheKey($collection->getTitle(),
                                                          $collection->getCatType(),
                                                          $year);
        self::$mc_CacheList[$idKey1][$idKey2]['collections'][] = $collection;
    } // addSpecificCollectionToLocalCache

    /**
     * Return all collections from the local cache
     *
     * @param $title
     * @param $catType
     * @param $year
     * @return mixed
     */
    private function getCollectionsFromLocalCache($title, $catType, $year) {
        list($idKey1, $idKey2) = $this->makeLocalCacheKey($title, $catType, $year);
        return self::$mc_CacheList[$idKey1][$idKey2]['collections'];
    } // getCollectionsFromLocalCache

    /**
     * Load a list of titles into the collection cache
     *
     * @param array $titles
     */
    public function loadCollectionCache(array $titles) {
        /*
         * If we already have everything in our memory, do not
         * bother with trying to find any more records in the database
         */
        if (self::$startedWithFullCacheLoad) {
            return ;
        } // if

        /*
         * If we get an empty list of titles to fetch, fetch everything
         * else just the list we want to return
         */
        if (!empty($titles)) {
            $sqlWhere = " mc.title IN (" . $this->_conn->arrayKeyToIn($titles, PDO::PARAM_STR) . ")";
            self::$startedWithFullCacheLoad = false;
        } else {
            $sqlWhere = ' (1 = 1)';
            self::$startedWithFullCacheLoad = true;
        } // else

        /* Retrieve the current list */
        $resultList = $this->_conn->arrayQuery("SELECT mc.id AS mcid,
                                                           c.id AS cid,
                                                           mc.title,
                                                           mc.tmdb_id,
                                                           mc.tvrage_id,
                                                           mc.cattype,
                                                           mc.year AS year,
                                                           c.season,
                                                           c.episode,
                                                           c.partscurrent,
                                                           c.partstotal
                                                    FROM mastercollections mc
                                                        LEFT JOIN collections c ON c.mcid = mc.id
												    WHERE " . $sqlWhere);

        // and merge it into the cache
        foreach($resultList as $result) {
            $collection = new Dto_CollectionInfo($result['cattype'], $result['title'], $result['season'],
                                                 $result['episode'], $result['year'], $result['partscurrent'],
                                                 $result['partstotal']);
            $collection->setMcId($result['mcid']);
            $collection->setId($result['cid']);

            /*
             * Make sure the master record is in the local cache
             */
            if (!$this->isInLocalCache($result['title'], $result['cattype'], $result['year'])) {
                $this->addMcToLocalCache($result['mcid'], $result['title'], $result['cattype'], $result['year']);
            } // if

            /*
             * And add it to the local cache array
             */
            $this->addSpecificCollectionToLocalCache($collection->getTitle(),
                                                     $collection->getCatType(),
                                                     $collection->getYear(),
                                                     $collection);
        } // foreach
    } // loadCollectionCache

    /**
     * Try to find a specific collection record from the cache, and create
     * it in the database if it does not exist
     *
     * @param Dto_CollectionInfo $collToFind
     * @param int $stamp
     * @param int $spotId
     * @return Dto_CollectionInfo
     */
    protected function matchCreateSpecificCollection(Dto_CollectionInfo $collToFind, $stamp, $spotId) {
        $title = $collToFind->getTitle();
        $catType = $collToFind->getCatType();
        $year = $collToFind->getYear();

        foreach($this->getCollectionsFromLocalCache($title, $catType, $year) as $collection) {

            if ($collToFind->equalColl($collection)) {
                /* Save stamp to update collection stamp later on */
                $this->mc_updateMcStamp[$collToFind->getMcId()] = array('stamp' => $stamp,
                                                                        'spotid' => $spotId);

                return $collection;
            } // if
        } // foreach

        /*
         * We did not find the correct specific collection,
         * so we add it to our database, and add it to the
         * cache
         */
        $this->_conn->exec('INSERT INTO collections(mcid, season, episode, year, partscurrent, partstotal)
                                              VALUES (:mcid, :season, :episode, :year, :partscurrent, :partstotal)',
            array(
                ':mcid' => array($collToFind->getMcId(), PDO::PARAM_INT),
                ':season' => array($collToFind->getSeason(), PDO::PARAM_STR),
                ':episode' => array($collToFind->getEpisode(), PDO::PARAM_STR),
                ':year' => array($collToFind->getYear(), PDO::PARAM_STR),
                ':partscurrent' => array($collToFind->getPartsCurrent(), PDO::PARAM_STR),
                ':partstotal' => array($collToFind->getPartsTotal(), PDO::PARAM_STR),
            ));
        $collToFind->setId($this->_conn->lastInsertId('collections'));

        // and add it to the cache
        $this->addSpecificCollectionToLocalCache($title, $catType, $collToFind->getYear(), $collToFind);

        /* Save stamp to update collection stamp later on */
        $this->mc_updateMcStamp[$collToFind->getMcId()] = array('stamp' => $stamp,
                                                                'spotid' => $spotId);

        return $collToFind;
    } // matchCreateSpecificCollection

    /**
     * Update the master collection table with the latest Spot and
     * stamp in the spots
     */
    protected function updateMcCollectionStamp() {
        foreach($this->mc_updateMcStamp as $mcId => $val) {
            $this->_conn->exec("UPDATE mastercollections
                                        SET lateststamp = :lateststamp,
                                            latestspotid = :latestspotid
                                WHERE id = :mcid",
                array(
                    ':lateststamp' => array($val['stamp'], PDO::PARAM_INT),
                    ':latestspotid' => array($val['spotid'], PDO::PARAM_INT),
                    ':mcid' => array($mcId, PDO::PARAM_INT),
                ));
        } // foreach

        // and clear the updates-pending list
        $this->mc_updateMcStamp = array();
    } // updateMcCollectionStamp

    /**
     * Returns an list of spots with the mastercollection id inserted into the
     * collectionInfo variable of the actual spot record. It will create the
     * master collection record if necessary
     *
     * @param array $spotList
     * @param bool $isRecursive
     * @return array
     */
    public function getCollectionIdList(array $spotList, $isRecursive = false) {
        $toFetch = array();

        if (!$isRecursive) {
            $this->_conn->beginTransaction();
        } # if

        /*
         * This is a very crude way to prevent us from running
         * out of memory.
         */
        if ((count(self::$mc_CacheList) > 250000) && (count($spotList) < 250000)) {
            self::$mc_CacheList = array();
            self::$startedWithFullCacheLoad = false;
        } // if

        // fetch from cache where possible
        foreach($spotList as & $spot) {
            if ($spot['collectionInfo'] !== null) {
                $title = $spot['collectionInfo']->getTitle();
                $catType = $spot['collectionInfo']->getCatType();
                $year = $spot['collectionInfo']->getYear();

                if ($this->isInLocalCache($title, $catType, $year)) {
                    $spot['collectionInfo']->setMcId($this->getMcIdFromLocalCache($title, $catType, $year));

                    /*
                     * Try to find this specific collection id. If it isn't matched,
                     * we know for sure the collection is not in the database, because
                     * we always retrieve the list of collections when retrieving the
                     * master collection.
                     */
                    $spot['collectionInfo'] = $this->matchCreateSpecificCollection($spot['collectionInfo'], $spot['stamp'], $spot['id']);;
                } else {
                    $toFetch[$spot['collectionInfo']->getTitle()] =
                        array('cattype' => $spot['collectionInfo']->getCatType(),
                              'year' => $spot['collectionInfo']->getYear()
                        );
                } // else
            } // if
        } // foreach
        unset($spot);

        /*
         * Update the local collection information with the latest stamp
         */
        $this->updateMcCollectionStamp();

        // get remaining titles from database
        if (!empty($toFetch)) {
            $this->loadCollectionCache($toFetch);

            /*
             * Loop through all titles once more, and if we still do not have
             * a cache record for these, create the mastercollection record.
             */
            foreach($toFetch as $key => $val) {
                /*
                 * Make sure the master title record is in the db, if we didn't get it
                 * the first time, create it now.
                 */
                if (!$this->isInLocalCache($key, $val['cattype'], $val['year'])) {
                     # echo 'Creating mastercollections: (' . $key . '),(' . $val['cattype'] . '),('. $val['year'] . ')' . PHP_EOL;

                    $this->_conn->exec('INSERT INTO mastercollections(title, cattype, year, tmdb_id, tvrage_id)
                                              VALUES (:title, :cattype, :year, NULL, NULL)',
                        array(
                            ':title' => array($key, PDO::PARAM_STR),
                            ':cattype' => array($val['cattype'], PDO::PARAM_INT),
                            ':year' => array($val['year'], PDO::PARAM_INT)
                        ));

                    // add the newly generated mastercollection to the local cache
                    $this->addMcToLocalCache($this->_conn->lastInsertId('mastercollections'),           // mcid
                                             $key,                                                      // title
                                             $val['cattype'],                                           // cattype
                                             $val['year']);
                } // if
            } // foreach

            /*
             * We call ourselves recursively. This is an ugly solution, but
             * we are rather lazy to not further optimize this until necessary.
             *
             * The above code garantuees us both the specific collection and master
             * record are set, so the second time this is run, the toFetch list
             * will always be empty, so we never recurse more than once. You can
             * get out your pitchforks now.
             */
            $spotList = $this->getCollectionIdList($spotList, true);
        } // if

        if (!$isRecursive) {
            /*
             * We try one more time to see if we can re-arrange the movies
             * with the year NULL
             */
            foreach($spotList as &$spot) {
                if ($spot['collectionInfo'] !== null) {
                    $collInfo = $spot['collectionInfo'];

                    /*
                     * Is there any other year available yet? If so, use that one.
                     */
                    $fixedYear = $this->fixCollectionYear($collInfo->getTitle(), $collInfo->getCatType(), $collInfo->getYear());
                    if ($fixedYear !== null) {
                        $collInfo->setYear($fixedYear);
                        $spot['collectionInfo'] = $this->matchCreateSpecificCollection($collInfo, $spot['stamp'], $spot['id']);
                    } // if
                }
            } // foreach
            unset($spot);

            $this->_conn->commit();
        } // if

        return $spotList;
    } // getCollectionIdList


} # Dao_Base_Collections
