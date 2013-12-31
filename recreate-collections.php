<?php
error_reporting(2147483647);
/*
 * We need loads of memory to make sure we can cache everything, preventing
 * us from going to the database too often
 */
ini_set('memory_limit', '2048M');

function displayProgress($type, $startingPoint, $increment) {
    if ($type == 'start') {
        echo "Creating collection from spots " . ($startingPoint) . ' to ' . ($startingPoint + $increment) . ', ';;
    } elseif ($type == 'finish') {
        echo "done. " . PHP_EOL;
    } // elseif
} // displayProgress

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

    # Initialize commandline arguments
    SpotCommandline::initialize(array('clean'), array('clean' => false));

    /*
     * Now try to get all current cache items
     */
    $dbConnection = $daoFactory->getConnection();

//    $spot['title'] = 'Donald Duck 1960 25 35 36';
//    $y = new Services_ParseCollections_Books($spot);
//    var_dump($y->parseSpot());
//    die();


    # Truncate the current collections table, and reset all collection id's
    if (SpotCommandline::get('clean')) {
        echo "Cleaning up all existing collections, ";
        $dbConnection->rawExec('UPDATE spots SET collectionid = NULL WHERE collectionid IS NOT NULL');
        $dbConnection->rawExec('TRUNCATE collections');
        $dbConnection->rawExec('TRUNCATE mastercollections');
        echo "done.". PHP_EOL;
    } // if

    /**
     * Load the complete collection cache in memory
     */
    echo "Loading all existing collections in memory, ";
    $daoFactory->getCollectionsDao()->loadCollectionCache(array());
    echo "done" . PHP_EOL;

    /* Retrieve list of spots from the database */
    $svcCreateColl= new Services_Collections_Create($daoFactory);
    $svcCreateColl->createCollections(0, 'displayProgress');

    /*
     * And actually start updating or creating the schema and settings
    */
    echo "Done creating collections" . PHP_EOL;

}
catch(Exception $x) {
    echo PHP_EOL . PHP_EOL;
    echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
    echo "Creation of collections failed:" . PHP_EOL;
    echo "   " . $x->getMessage() . PHP_EOL;
    echo PHP_EOL . PHP_EOL;
    echo $x->getTraceAsString();
    die(1);
} # catch
