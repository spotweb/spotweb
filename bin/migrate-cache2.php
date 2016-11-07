#!/usr/bin/php
<?php
error_reporting(2147483647);

function cleanupDirs($dir, $curlevel) {
    echo 'Scanning directory: ' . $dir . ' (level: ' . $curlevel . ')' . PHP_EOL;

    $objects = scandir($dir);
    foreach ($objects as $object) {

        if ($object != "." && $object != "..") {
            if (strlen(basename($object)) == 3) {
                if (is_dir($dir)) {
                    cleanupDirs($dir."/".$object, $curlevel+1);
                }
            } elseif ($curlevel > 2) {
                echo 'Removing file: ' . $dir."/".$object . PHP_EOL;
                unlink($dir."/".$object);
            }
        }

    }

    echo 'Trying to remove directory: ' . $dir . PHP_EOL;
    @rmdir($dir);
} # cleanupDirs

try {
    require_once __DIR__ . '/../vendor/autoload.php';

    /*
     * Create a DAO factory. We cannot use the bootstrapper here,
     * because it validates for a valid settings etc. version.
     */
    $bootstrap = new Bootstrap();
    $daoFactory = $bootstrap->getDaoFactory();
    $settings = $bootstrap->getSettings($daoFactory, true);
    $dbSettings = $bootstrap->getDbSettings();

    /*
     * Try to create the directory, we hardcode it here because
     * it cannot be made configurable in the database anyway
     * and this is just the lazy way out, really
     */
    $dirCache = __DIR__.'/../cache/';
    $daoFactory->setCachePath($dirCache);
    $cacheDao = $daoFactory->getCacheDao();

    if (!file_exists($dirCache)) {
        mkdir($dirCache, 0777);
    } # if

    /*
     * Now try to get all current cache items
     */
    $dbConnection = $daoFactory->getConnection();

    # Update old blacklisttable
    $schemaVer = $dbConnection->singleQuery("SELECT `value` FROM `settings` WHERE `name` = 'schemaversion'", array());
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

    /*
     * Removing orphaned files
     */
    $cacheBasePath = '.' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    cleanUpDirs($cacheBasePath . 'image' . DIRECTORY_SEPARATOR, 0);
    cleanUpDirs($cacheBasePath . 'nzb' . DIRECTORY_SEPARATOR, 0);
    cleanUpDirs($cacheBasePath . 'stats' . DIRECTORY_SEPARATOR, 0);
    cleanUpDirs($cacheBasePath . 'web' . DIRECTORY_SEPARATOR, 0);
    cleanUpDirs($cacheBasePath . 'translatedcomments' . DIRECTORY_SEPARATOR, 0);
    cleanUpDirs($cacheBasePath . 'translatertoken' . DIRECTORY_SEPARATOR, 0);

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
