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
    $resultList = $conn->arrayQuery("SELECT DISTINCT ON (mc.title, mc.year)
                                           mc.id AS mcid,
                                           mc.title,
                                           mc.tmdb_id,
                                           mc.cattype,
                                           mc.year
                                    FROM mastercollections mc
                                    LEFT JOIN collections c ON c.mcid = mc.id
                                        WHERE cattype = 3
                                          AND tmdb_id IS NULL");

    # $lists = array_chunk($resultList, ceil(count($resultList) / 4));
    $lists = array($resultList); // 1 one list
    for($i = 0; $i < count($lists); $i++) {
        file_put_contents('export' . $i . '.serialize', serialize($lists[$i]));
    } // for

    /*
     * And actually start updating or creating the schema and settings
    */
    echo "Done exporting collections" . PHP_EOL;

}
catch(Exception $x) {
    echo PHP_EOL . PHP_EOL;
    echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
    echo "Export of collections failed:" . PHP_EOL;
    echo "   " . $x->getMessage() . PHP_EOL;
    echo PHP_EOL . PHP_EOL;
    echo $x->getTraceAsString();
    die(1);
} # catch
