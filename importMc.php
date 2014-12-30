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

    echo $argv[0] . " - tmdb distribution import script" . PHP_EOL . PHP_EOL;
    if ($argc < 2) {
        echo "Je moet de filenaam welke je gekregen hebt als eerste parameter meegeven" . PHP_EOL;
        die();
    }

    if (!is_readable($argv[1])) {
        echo "Bestand '" . $argv[1] . "' kan niet worden gelezen" . PHP_EOL;
        die();
    } // if

    /* Retrieve the current connection */
    $conn = $daoFactory->getConnection();

    /*
     * Start reading the CSV file
     */
    $h = fopen($argv[1], "r");
    $conn->beginTransaction();
    while (($buffer = fgets($h, 4096)) !== false) {
        $line = explode(';', trim($buffer, "\r\n"));

        echo "Updating TMDBid for '" . $line[3] . "', (id: " . $line[0] . ') ';

        $conn->exec("UPDATE mastercollections SET tmdb_id = " . $line[1] .
                        " WHERE id = " . $line[0]);

        echo ", done" . PHP_EOL;
    }
    fclose($h);
    $conn->commit();

    echo "Done importing collections" . PHP_EOL;

}
catch(Exception $x) {
    echo PHP_EOL . PHP_EOL;
    echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
    echo "Import of collections failed:" . PHP_EOL;
    echo "   " . $x->getMessage() . PHP_EOL;
    echo PHP_EOL . PHP_EOL;
    echo $x->getTraceAsString();
    die(1);
} # catch
