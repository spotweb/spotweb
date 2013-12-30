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
    protected static $mc_CacheList;
    /**
     * Signals whether we started with a full cache, eg, we
     * have everything that is in the database.
     *
     * @var bool
     */
    protected static $startedWithFullCacheLoad = false;

    /*
     * constructs a new Dao_Base_Collections object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn) {
        $this->_conn = $conn;
    } # ctor


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
         * else jjust the list we want to return
         */
        if (!empty($titles)) {
            $sqlWhere = " mc.title IN (" . $this->_conn->arrayKeyToIn($titles) . ")";
            self::$startedWithFullCacheLoad = false;
        } else {
            $sqlWhere = ' (1 = 1)';
            self::$startedWithFullCacheLoad = true;
        } // else

        /* Retrieve the current list */
        $resultList = $this->_conn->arrayQuery("SELECT mc.id AS mcid,
                                                           c.id AS cid,
                                                           mc.title,
                                                           mc.tmdbid,
                                                           mc.tvrageid,
                                                           mc.cattype,
                                                           c.season,
                                                           c.episode,
                                                           c.year
                                                    FROM mastercollections mc
                                                        LEFT JOIN collections c ON c.mcid = mc.id
												    WHERE " . $sqlWhere);

        // and merge it into the cache
        foreach($resultList as $result) {
            $collection = new Dto_CollectionInfo($result['cattype'], $result['title'], $result['season'], $result['episode'], $result['year']);
            $collection->setMcId($result['mcid']);
            $collection->setId($result['cid']);

            /*
             * Make sure the master title record is in the local cache
             */
            if (!isset(self::$mc_CacheList[$result['title'] . '|' . $result['cattype']])) {
                self::$mc_CacheList[$result['title']] = array('mcid' => $result['mcid'],
                                                              'cattype' =>  $result['cattype'],
                                                              'collections' => array(),
                                                        );
            } // if

            /*
             * And add it to the local cache array
             */
            self::$mc_CacheList[$result['title'] . '|' . $result['cattype']]['collections'][] = $collection;
        } // foreach
    } // loadCollectionCache

    /**
     * Try to find a specific collection record from the cache, and create
     * it in the database if it does not exist
     *
     * @param Dto_CollectionInfo $collToFind
     * @return Dto_CollectionInfo
     */
    protected function matchCreateSpecificCollection(Dto_CollectionInfo $collToFind) {
        $title = $collToFind->getTitle();
        $catType = $collToFind->getCatType();
        foreach(self::$mc_CacheList[$title . '|' . $catType]['collections'] as $collection) {
            if ($collToFind->equalColl($collection)) {
                return $collection;
            } // if
        } // foreach

        /*
         * We did not find the correct specific collection,
         * so we add it to our database, and add it to the
         * cache
         */
        $this->_conn->exec('INSERT INTO collections(mcid, season, episode, year)
                                              VALUES (:mcid, :season, :episode, :year)',
            array(
                ':mcid' => array($collToFind->getMcId(), PDO::PARAM_INT),
                ':season' => array($collToFind->getSeason(), PDO::PARAM_STR),
                ':episode' => array($collToFind->getEpisode(), PDO::PARAM_STR),
                ':year' => array($collToFind->getYear(), PDO::PARAM_STR),
            ));
        $collToFind->setId($this->_conn->lastInsertId('collections'));

        // and add it to the cache
        self::$mc_CacheList[$title . '|' . $catType]['collections'][] = $collToFind;

        return $collToFind;
    } // matchCreateSpecificCollection

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

                if (isset(self::$mc_CacheList[$title . '|' . $catType])) {
                    $spot['collectionInfo']->setMcId(self::$mc_CacheList[$title . '|' . $catType]['mcid']);

                    /*
                     * Try to find this specific collection id. If it isn't matched,
                     * we know for sure the collection is not in the database, because
                     * we always retrieve the list of collections when retrieving the
                     * master collection.
                     */
                    $spot['collectionInfo'] = $this->matchCreateSpecificCollection($spot['collectionInfo']);
                } else {
                    $toFetch[$spot['collectionInfo']->getTitle()] = $spot['collectionInfo']->getCatType();
                } // else
            } // if
        } // foreach
        unset($spot);

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
                if (!isset(self::$mc_CacheList[$key . '|' . $val])) {
                    $this->_conn->exec('INSERT INTO mastercollections(title, cattype, tmdbid, tvrageid)
                                              VALUES (:title, :cattype, NULL, NULL)',
                        array(
                            ':title' => array($key, PDO::PARAM_STR),
                            ':cattype' => array($val, PDO::PARAM_INT)
                        ));

                    // add the newly generated mastercollection to the local cache
                    self::$mc_CacheList[$key . '|' . $val] = array('mcid' => $this->_conn->lastInsertId('mastercollections'),
                                                                   'cattype' => $val,
                                                                   'collections' => array());
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
            $this->_conn->commit();
        } // if

        return $spotList;
    } // getCollectionIdList


} # Dao_Base_Collections
