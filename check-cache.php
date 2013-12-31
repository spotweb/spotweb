<?php
error_reporting(2147483647);

try {
    /*
     * If we are run from another directory, try to change the current
     * working directory to a directory the script is in
     */
    if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
        chdir(dirname(__FILE__));
    } # if

    require_once "lib/SpotClassAutoload.php";
    SpotClassAutoload::register();
    require_once "lib/Bootstrap.php";

    /*
     * Create a DAO factory. We cannot use the bootstrapper here,
     * because it validates for a valid settings etc. version.
     */
    $bootstrap = new Bootstrap();
    list($settings, $daoFactory, $req) = $bootstrap->boot();

    /*
     * disable timing, all queries which are ran by retrieve this would make it use
     * large amounts of memory
     */
    SpotTiming::disable();

    /*
     * Try to create the directory, we hardcode it here because
     * it cannot be made configurable in the database anyway
     * and this is just the lazy way out, really
     */
    $daoFactory->setCachePath('./cache/');
    $cacheDao = $daoFactory->getCacheDao();

    if (!is_dir('./cache')) {
        mkdir('./cache', 0777);
    } # if

    /*
     * Now try to get all current cache items
     */
    $dbConnection = $daoFactory->getConnection();

    /*
     * Initialize the NZB retrieval provider
     */
    $svcFullSpot = new Services_Providers_FullSpot($daoFactory->getSpotDao(), new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($settings, 'hdr')));
    $svcNzb = new Services_Providers_Nzb($cacheDao, new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($settings, 'bin')));
    $svcPrvHttp = new Services_Providers_Http($cacheDao);
    $svcImage = new Services_Providers_SpotImage($svcPrvHttp,
                                                 new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($settings, 'bin')),
                                                 $cacheDao);

    $counter = 0;
    while(true) {
        $counter++;
        echo "Validating cache content, items " . (($counter - 1) * 1000) . ' to ' . ($counter * 1000);

        $results = $dbConnection->arrayQuery("SELECT * FROM cache LIMIT 1001 OFFSET " . (($counter - 1) * 1000) );

        foreach($results as $cacheItem) {
            $cacheItem['metadata'] = unserialize($cacheItem['metadata']);
            try {
                $cacheDao->getCacheContent($cacheItem['id'], $cacheItem['cachetype'], $cacheItem['metadata']);
            } catch(CacheIsCorruptException $x) {
                echo PHP_EOL . '  Trying to fetch #' . $cacheItem['id'] . ' for ' . $cacheItem['resourceid'] . ' again, ';

                switch($cacheItem['cachetype']) {
                    case Dao_Cache::SpotNzb             : {
                        try {
                            $fullSpot = $svcFullSpot->fetchFullSpot($cacheItem['resourceid'], 1);
                            $svcNzb->fetchNzb($fullSpot);

                            $cacheInfo = $cacheDao->getCachedNzb($cacheItem['resourceid']);
                            echo 'retrieved NZB as ' . $cacheInfo['id'] . PHP_EOL;
                        } catch(Exception $x) {
                            echo 'error redownloading NZB: '. $x->getMessage() . PHP_EOL;
                        } # catch

                        break;
                    }

                    case Dao_Cache::SpotImage           : {
                        try {
                            $fullSpot = $svcFullSpot->fetchFullSpot($cacheItem['resourceid'], 1);
                            $svcImage->fetchSpotImage($fullSpot);

                            $cacheInfo = $cacheDao->getCachedSpotImage($cacheItem['resourceid']);
                            echo 'retrieved image as ' . $cacheInfo['id'] . PHP_EOL;
                        } catch(Exception $x) {
                            echo 'error redownloading image: '. $x->getMessage() . PHP_EOL;
                        } # catch

                        break;
                    }
                    default : ;
                } # switch
            } # catch
        } # results

        echo ", done. " . PHP_EOL;

        if (count($results) == 0) {
            break;
        } # if
    } # while

}
catch(Exception $x) {
    echo PHP_EOL . PHP_EOL;
    echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
    echo "Validation of complete cache:" . PHP_EOL;
    echo "   " . $x->getMessage() . PHP_EOL;
    echo PHP_EOL . PHP_EOL;
    echo $x->getTraceAsString();
    die(1);
} # catch


