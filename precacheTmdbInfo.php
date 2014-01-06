<?php
error_reporting(2147483647);
/*
 * We need loads of memory to make sure we can cache everything, preventing
 * us from going to the database too often
 */
ini_set('memory_limit', '2048M');

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

    /* Retrieve the current list */
    $conn = $daoFactory->getConnection();
    $resultList = $conn->arrayQuery("SELECT DISTINCT ON (mc.tmdbid)
                                           mc.tmdbid
                                    FROM mastercollections mc
                                    WHERE tmdbid IS NOT NULL
                                    ORDER BY tmdbid");

    $startTime = time();
    $counter = 0;
    $svcTmdbInfo = new Services_MediaInformation_TheMovieDb($daoFactory->getCacheDao(), $settings);
    $daoTmdb = $daoFactory->getTmdbInfo();
    foreach($resultList as $mc) {
        echo "Fetching TMDB id: " . $mc['tmdbid'] . ", ";

        $svcTmdbInfo->setSearchid($mc['tmdbid']);
        $tmdb = $svcTmdbInfo->retrieveInfo();
        $counter++;

        if ($tmdb !== null) {
            $daoTmdb->saveInfo($tmdb);
            echo "retrieved " . $tmdb->getTitle() . PHP_EOL;
        } else {
            echo "failure" . PHP_EOL;
        } // else

        if ($counter > 29) {
            echo "  backing off for " . max(10, 12 - (time() - $startTime)) . " seconds" . PHP_EOL;
            // we try to limit ourselves to 30 records per 22 seconds
            sleep(max(10, 12 - (time() - $startTime)));

            $counter = 0;
            $startTime = time();
        } // if
    } // foreach

    echo "Done precaching tmdb information collections" . PHP_EOL;

}
catch(Exception $x) {
    echo PHP_EOL . PHP_EOL;
    echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
    echo "Precaching tmdb information failed:" . PHP_EOL;
    echo "   " . $x->getMessage() . PHP_EOL;
    echo PHP_EOL . PHP_EOL;
    echo $x->getTraceAsString();
    die(1);
} # catch
