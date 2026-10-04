<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_CommandLock.php';

class ServicesRetrieverCommandLockTestDb extends dbeng_pdo_mysql
{
    public $queries = [];
    public $getLockResult = 1;
    public $throwOnRelease = false;

    public function singleQuery($sql, $params = [])
    {
        $this->queries[] = [$sql, $params];

        if (strpos($sql, 'GET_LOCK') !== false) {
            return $this->getLockResult;
        }

        if (strpos($sql, 'RELEASE_LOCK') !== false) {
            if ($this->throwOnRelease) {
                throw new Exception('release failed');
            }

            return 1;
        }
    }
}

class ServicesRetrieverCommandLockPostgresqlTestDb extends dbeng_pdo_pgsql
{
    public $queries = [];
    public $tryLockResult = true;

    public function singleQuery($sql, $params = [])
    {
        $this->queries[] = [$sql, $params];

        if (strpos($sql, 'pg_try_advisory_lock') !== false) {
            return $this->tryLockResult;
        }

        if (strpos($sql, 'pg_advisory_unlock') !== false) {
            return true;
        }
    }
}

class ServicesRetrieverCommandLockSqliteTestDb extends dbeng_pdo_sqlite
{
}

class ServicesRetrieverCommandLockTest extends TestCase
{
    public function testAcquireAndReleaseUseMysqlAdvisoryLock()
    {
        $db = new ServicesRetrieverCommandLockTestDb();
        $lock = new Services_Retriever_CommandLock($db);

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->isAcquired());

        $lock->release();

        $this->assertFalse($lock->isAcquired());
        $this->assertSame('SELECT GET_LOCK(:lockname, 0)', $db->queries[0][0]);
        $this->assertSame('SELECT RELEASE_LOCK(:lockname)', $db->queries[1][0]);
    }

    public function testFailedAcquireDoesNotReleaseLock()
    {
        $db = new ServicesRetrieverCommandLockTestDb();
        $db->getLockResult = 0;
        $lock = new Services_Retriever_CommandLock($db);

        $this->assertFalse($lock->acquire());
        $this->assertFalse($lock->isAcquired());

        $lock->release();

        $this->assertCount(1, $db->queries);
        $this->assertSame('SELECT GET_LOCK(:lockname, 0)', $db->queries[0][0]);
    }

    public function testReleaseFailureDoesNotMaskPrimaryRetrieverFailure()
    {
        $db = new ServicesRetrieverCommandLockTestDb();
        $db->throwOnRelease = true;
        $lock = new Services_Retriever_CommandLock($db);

        $this->assertTrue($lock->acquire());
        $lock->release();

        $this->assertFalse($lock->isAcquired());
    }

    public function testAcquireAndReleaseUsePostgresqlAdvisoryLock()
    {
        $db = new ServicesRetrieverCommandLockPostgresqlTestDb();
        $lock = new Services_Retriever_CommandLock($db);

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->isAcquired());

        $lock->release();

        $this->assertFalse($lock->isAcquired());
        $this->assertSame('SELECT pg_try_advisory_lock(:classid, :objectid)', $db->queries[0][0]);
        $this->assertSame('SELECT pg_advisory_unlock(:classid, :objectid)', $db->queries[1][0]);
    }

    public function testPostgresqlFailedAcquireDoesNotReleaseLock()
    {
        $db = new ServicesRetrieverCommandLockPostgresqlTestDb();
        $db->tryLockResult = false;
        $lock = new Services_Retriever_CommandLock($db);

        $this->assertFalse($lock->acquire());
        $this->assertFalse($lock->isAcquired());

        $lock->release();

        $this->assertCount(1, $db->queries);
        $this->assertSame('SELECT pg_try_advisory_lock(:classid, :objectid)', $db->queries[0][0]);
    }

    public function testSqliteUsesNonBlockingFileLock()
    {
        $lockPath = sys_get_temp_dir().'/spotweb-lock-test-'.uniqid('', true);
        $firstLock = new Services_Retriever_CommandLock(new ServicesRetrieverCommandLockSqliteTestDb(), Services_Retriever_CommandLock::LockName, $lockPath);
        $secondLock = new Services_Retriever_CommandLock(new ServicesRetrieverCommandLockSqliteTestDb(), Services_Retriever_CommandLock::LockName, $lockPath);

        $this->assertTrue($firstLock->acquire());
        $this->assertFalse($secondLock->acquire());

        $firstLock->release();

        $this->assertTrue($secondLock->acquire());
        $secondLock->release();
    }

    public function testRetrieverBaseDoesNotContainPhaseLevelLocking()
    {
        $source = file_get_contents(__DIR__.'/../../../lib/services/Retriever/Services_Retriever_Base.php');

        $this->assertStringNotContainsString('isRetrieverRunning()', $source);
        $this->assertStringNotContainsString('setRetrieverRunning(true)', $source);
        $this->assertStringNotContainsString('setRetrieverRunning(false)', $source);
    }
}
