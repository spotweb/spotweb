<?php
error_reporting(2147483647);

function cleanupDirs($dir, $curlevel) {
    echo 'Scanning directory: ' . $dir . ' (level: ' . $curlevel . ')' . PHP_EOL;
} # cleanupDirs

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

# -------------------
# Dummy class to make getCache() available to the public
class MigrateCache extends Dao_Base_Cache {
        public function moveCache($cacheId, $cacheType) {
                $this->getCache($cacheId, $cacheType);
        } // moveCache
} // class MigrateCache

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
    $cacheDao = new MigrateCache($daoFactory->getConnection(), new Dao_Base_ZipCacheStore('./cache/'));

    if (!is_dir('./cache')) {
        mkdir('./cache', 0777);
    } # if

    /*
     * Now try to get all current cache items
     */
    $dbConnection = $daoFactory->getConnection();

    $counter = 0;
    while(true) {
        $counter++;
        echo "Migrating cache content, items " . (($counter - 1) * 1000) . ' to ' . ($counter * 1000);

        $results = $dbConnection->arrayQuery('SELECT id, resourceid, cachetype, metadata FROM cache ORDER BY id LIMIT 1001 OFFSET ' . (($counter - 1) * 1000));

        foreach($results as $cacheItem) {
            try {
            	$cacheDao->moveCache($cacheItem['resourceid'], $cacheItem['cachetype']);
	    } catch(Exception $x) {
		echo $x->getMessage() . PHP_EOL;
	    } // catch
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
