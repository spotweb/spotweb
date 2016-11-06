#!/usr/bin/php
<?php
error_reporting(2147483647);

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
    if ($schemaVer >= 0.60) {
        throw new Exception("Your schemaversion is already upgraded");
    } # if

    /*
     * Remove any serialized caches as we don't support them anymore
     */
    echo "Removing serialized entries from database";
    $dbConnection->modify("DELETE FROM cache WHERE cachetype = 4");
    $dbConnection->modify("DELETE FROM cache WHERE cachetype = 5");
    echo ", done. " . PHP_EOL;

    $counter = 1;
    while(true) {
        $counter++;
        echo "Migrating cache content, items " . (($counter - 1) * 100) . ' to ' . ($counter * 100);

        $results = null;
        switch ($dbSettings['engine']) {
            case 'mysql'                :
            case 'pdo_mysql'            : {
                $results = $dbConnection->arrayQuery(
                        'SELECT resourceid, stamp, metadata, serialized, cachetype, UNCOMPRESS(content) AS content FROM cache WHERE content IS NOT NULL LIMIT 100');
                break;
            } # mysql

            case 'pgsql'                :
            case 'pdo_pgsql'            : {
                $results = $dbConnection->arrayQuery(
                        "SELECT resourceid, stamp, metadata, serialized, cachetype, content FROM cache WHERE content IS NOT NULL LIMIT 100");
                foreach($results as &$v) {
                    $v['content'] = stream_get_contents($v['content']);
                } # foreach

                break;
            } # case Postgresql

            case 'pdo_sqlite'          : {
                $results = $dbConnection>arrayQuery(
                    'SELECT resourceid, stamp, metadata, serialized, cachetype, content FROM cache WHERE content IS NOT NULL LIMIT 100');

                break;
            } # mysql
        }
        foreach($results as $cacheItem) {
            if ($cacheItem['metadata']) {
                $cacheItem['metadata'] = unserialize($cacheItem['metadata']);
            } # if

            echo '.';
            $cacheDao->putCacheContent($cacheItem['resourceid'], $cacheItem['cachetype'], $cacheItem['content'], $cacheItem['metadata'], 0);

            /*
             * Actually invalidate the cache content
             */
            $dbConnection->modify("UPDATE cache SET content = NULL where resourceid = :resourceid AND cachetype = :cachetype",
                       array(':resourceid' => array($cacheItem['resourceid'], PDO::PARAM_STR),
                             ':cachetype' => array($cacheItem['cachetype'], PDO::PARAM_INT)));
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
        echo "Cache migration failed:" . PHP_EOL;
        echo "   " . $x->getMessage() . PHP_EOL;
        echo PHP_EOL . PHP_EOL;
        echo $x->getTraceAsString();
        die(1);
    } # catch
