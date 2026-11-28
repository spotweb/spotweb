<?php

use PHPUnit\Framework\TestCase;

/*
 * Fake database holding cache rows, it evaluates the TTL conditions of the
 * cache queries in PHP so we test what is selected, not how the SQL looks
 */
class DaoBaseCacheTtlTestDb extends dbeng_abs
{
    public $rows = [];
    public $deletedIds = [];

    public function arrayQuery($sql, $params = [])
    {
        $now = time();
        $result = [];

        foreach ($this->rows as $row) {
            $isPermanent = ($row['ttl'] == 0);
            $isTtlExpired = ($row['ttl'] + $row['stamp']) < $now;

            if (strpos($sql, 'stamp < :stamp1') !== false) {
                // expireCache()
                $this->assertTtlCondition($sql, '((ttl > 0) AND ((ttl + stamp) < :stamp2))');
                $match = ($row['stamp'] < $params[':stamp1'][0]) || (!$isPermanent && $isTtlExpired);
            } else {
                // isCached() and getMassCacheRecords()
                $this->assertTtlCondition($sql, '((ttl = 0) OR ((ttl + stamp) >= :');
                $match = $isPermanent || !$isTtlExpired;
            } // else

            if ($match) {
                $result[] = $row;
            } // if
        } // foreach

        return $result;
    }

    public function exec($s, $p = [])
    {
        $this->deletedIds[] = $p[':cacheid'][0];
    }

    public function safe($s)
    {
        return "'".$s."'";
    }

    private function assertTtlCondition($sql, $condition)
    {
        if (strpos($sql, $condition) === false) {
            throw new Exception('Unexpected TTL condition in: '.$sql);
        } // if
    }

    public function connect($host, $user, $pass, $db, $port, $schema)
    {
    }

    public function rawExec($sql)
    {
    }

    public function singleQuery($sql, $params = [])
    {
    }

    public function rows()
    {
        return 0;
    }

    public function beginTransaction()
    {
    }

    public function commit()
    {
    }

    public function rollback()
    {
    }

    public function lastInsertId($tableName)
    {
    }

    public function batchInsert($ar, $sql, $typs, $fields, $sql2)
    {
    }

    public function modify($s, $p = [])
    {
    }
}

class DaoBaseCacheTtlTest extends TestCase
{
    // never created, removing cache files from it is a no-op
    const CachePath = '/nonexistent/spotweb-cache-ttl-test';
    const Metadata = 'a:1:{s:9:"imagetype";i:2;}';

    public function testExpireCacheKeepsPermanentItemsWithinRetention()
    {
        $db = $this->newDb();
        $dao = new Dao_Base_Cache($db, self::CachePath);

        $dao->expireCache(30);

        // only the expired error image and the item beyond retention
        $this->assertSame([2, 4], $db->deletedIds);
    }

    public function testUnexpiredItemIsCached()
    {
        $db = new DaoBaseCacheTtlTestDb();
        $db->rows = [['id' => 3, 'resourceid' => 'x', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => time() - 86400, 'ttl' => 7 * 86400, 'metadata' => self::Metadata]];
        $dao = new Dao_Base_Cache($db, self::CachePath);

        $this->assertTrue($dao->hasCachedSpotImage('x'));
        $this->assertSame([Dao_Cache::SpotImage => ['x' => 1]], $dao->getMassCacheRecords([['Message-ID' => 'x']]));
    }

    public function testExpiredItemIsNotCached()
    {
        $db = new DaoBaseCacheTtlTestDb();
        $db->rows = [['id' => 2, 'resourceid' => 'x', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => time() - 8 * 86400, 'ttl' => 7 * 86400, 'metadata' => self::Metadata]];
        $dao = new Dao_Base_Cache($db, self::CachePath);

        $this->assertFalse($dao->hasCachedSpotImage('x'));
        $this->assertSame([], $dao->getMassCacheRecords([['Message-ID' => 'x']]));
    }

    public function testPermanentItemIsCached()
    {
        $db = new DaoBaseCacheTtlTestDb();
        $db->rows = [['id' => 1, 'resourceid' => 'x', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => time() - 86400, 'ttl' => 0, 'metadata' => self::Metadata]];
        $dao = new Dao_Base_Cache($db, self::CachePath);

        $this->assertTrue($dao->hasCachedSpotImage('x'));
    }

    private function newDb()
    {
        $now = time();
        $db = new DaoBaseCacheTtlTestDb();
        $db->rows = [
            // prefetched image, never expires
            ['id' => 1, 'resourceid' => 'a', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => $now - 86400, 'ttl' => 0, 'metadata' => self::Metadata],
            // error image cached for 7 days, expired
            ['id' => 2, 'resourceid' => 'b', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => $now - 8 * 86400, 'ttl' => 7 * 86400, 'metadata' => self::Metadata],
            // error image cached for 7 days, still valid
            ['id' => 3, 'resourceid' => 'c', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => $now - 86400, 'ttl' => 7 * 86400, 'metadata' => self::Metadata],
            // permanent, but not used for longer than the retention
            ['id' => 4, 'resourceid' => 'd', 'cachetype' => Dao_Cache::SpotImage, 'stamp' => $now - 40 * 86400, 'ttl' => 0, 'metadata' => self::Metadata],
        ];

        return $db;
    }
}
