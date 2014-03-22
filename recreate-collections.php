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

    $spot['subcata'] = 'a0|';
    $spot['title'] = 'The Glee Project E5';
    $spot['subcatz'] = 'z1|';
    $spot['category'] = '0';
    $spot = $daoFactory->getSpotDao()->getSpotHeader('CLswObnJ7C8jIIxTgARyw@spot.net');
    $user = array('userid' => 0);

    $y = Services_ParseCollections_Factory::factory($spot);
    // $y = new Services_ParseCollections_Movies($spot);
    $y = $y->parseSpot();
    if ($y !== null){
        var_dump(mb_detect_encoding($y->getTitle()));
        var_dump(mb_check_encoding($y->getTitle()));
        var_dump($y);
    } else {
        echo "Spot is marked as INVALID!";
    }
    die();

    # Truncate the current collections tables, and reset all collection id's
    if (SpotCommandline::get('clean')) {
        $dbConnection = $daoFactory->getConnection();

        echo "Cleaning up all existing collections, ";
        $dbConnection->rawExec('UPDATE spots SET collectionid = NULL WHERE collectionid IS NOT NULL');
        $dbConnection->rawExec('TRUNCATE collections');
        $dbConnection->rawExec('TRUNCATE mastercollections');
        echo "done.". PHP_EOL;
    } // if

    /* Load the complete collection cache in memory */
    echo "Loading all existing collections in memory, ";
    $daoFactory->getCollectionsDao()->loadCollectionCache(array());
    echo "done" . PHP_EOL;

    /* And start creating ocllections */
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
