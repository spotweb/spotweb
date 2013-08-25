<?php
error_reporting(2147483647);

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {

            if (count($objects) != 2) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
                }
            }
        }
        reset($objects);
        @rmdir($dir);
    }
}

try {
    /*
     * If we are run from another directory, try to change the current
     * working directory to a directory the script is in
     */
    if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
        chdir(dirname(__FILE__));
    } # if

    require_once "lib/SpotClassAutoload.php";
    require_once "lib/Bootstrap.php";

    /*
     * Create a DAO factory. We cannot use the bootstrapper here,
     * because it validates for a valid settings etc. version.
     */
    $bootstrap = new Bootstrap();
    $daoFactory = $bootstrap->getDaoFactory();
    $settings = $bootstrap->getSettings($daoFactory);
    $dbSettings = $bootstrap->getDbSettings();

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

    # Update old blacklisttable
    $schemaVer = $dbConnection->singleQuery("SELECT value FROM settings WHERE name = 'schemaversion'", array());
    if ($schemaVer >= 0.63) {
        throw new Exception("Your cache is already upgraded");
    } # if

    $counter = 0;
    while(true) {
        $counter++;
        echo "Migrating cache content, items " . (($counter - 1) * 1000) . ' to ' . ($counter * 1000);

        $results = $dbConnection->arrayQuery('SELECT id, cachetype, metadata FROM cache LIMIT 1001 OFFSET ' . (($counter - 1) * 1000) );

        foreach($results as $cacheItem) {
            $cacheItem['metadata'] = unserialize($cacheItem['metadata']);
            $cacheDao->migrateCacheToNewStorage($cacheItem['id'], $cacheItem['cachetype'], $cacheItem['metadata']);
        } # results

        echo ", done. " . PHP_EOL;

        if (count($results) == 0) {
            break;
        } # if
    } # while

    /*
      * try to remove the directories
     */
    echo "Removing old and empty cache directories (can take a while)..." . PHP_EOL;
    $cacheBasePath = '.' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    rrmdir($cacheBasePath);

    /*
     * And actually start updating or creating the schema and settings
    */
    echo "Updating schema..(" . $dbSettings['engine'] . ")" . PHP_EOL;

    # update the database with this specific schemaversion
    $dbConnection->rawExec("DELETE FROM settings WHERE name = 'schemaversion'", array());
    $dbConnection->rawExec("INSERT INTO settings(name, value) VALUES('schemaversion', '" . SPOTDB_SCHEMA_VERSION . "')");

}
catch(Exception $x) {
    echo PHP_EOL . PHP_EOL;
    echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
    echo "Cache migration failed:" . PHP_EOL;
    echo "   " . $x->getMessage() . PHP_EOL;
    echo PHP_EOL . PHP_EOL;
    echo $x->getTraceAsString();
    die(1);
} # catch
