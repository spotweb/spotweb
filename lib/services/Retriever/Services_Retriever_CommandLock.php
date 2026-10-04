<?php

class Services_Retriever_CommandLock
{
    const LockName = 'spotweb.retrieve';
    const PgLockClassId = 1397778242;
    const PgLockObjectId = 1919247986;

    private $_conn;
    private $_lockName;
    private $_lockPath;
    private $_fileHandle;
    private $_acquired = false;

    public function __construct(dbeng_abs $conn, $lockName = self::LockName, $lockPath = null)
    {
        $this->_conn = $conn;
        $this->_lockName = $lockName;
        $this->_lockPath = is_null($lockPath) ? __DIR__ : $lockPath;
    }

    public function acquire()
    {
        if ($this->_acquired) {
            return true;
        } // if

        if ($this->_conn instanceof dbeng_pdo_mysql) {
            return $this->acquireMysql();
        } // if

        if ($this->_conn instanceof dbeng_pdo_pgsql) {
            return $this->acquirePostgresql();
        } // if

        if ($this->_conn instanceof dbeng_pdo_sqlite) {
            return $this->acquireSqlite();
        } // if

        throw new NotImplementedException('Retriever command locking requires a supported PDO connection');
    }

    private function acquireMysql()
    {
        $result = $this->_conn->singleQuery(
            'SELECT GET_LOCK(:lockname, 0)',
            [
                ':lockname' => [$this->_lockName, PDO::PARAM_STR],
            ]
        );

        $this->_acquired = ((int) $result === 1);

        return $this->_acquired;
    }

    private function acquirePostgresql()
    {
        $result = $this->_conn->singleQuery(
            'SELECT pg_try_advisory_lock(:classid, :objectid)',
            [
                ':classid'  => [self::PgLockClassId, PDO::PARAM_INT],
                ':objectid' => [self::PgLockObjectId, PDO::PARAM_INT],
            ]
        );

        $this->_acquired = ($result === true) || ($result === 't') || ((int) $result === 1);

        return $this->_acquired;
    }

    private function sqliteLockFile()
    {
        return sys_get_temp_dir().'/spotweb-retrieve-'.sha1($this->_lockPath.':'.$this->_lockName).'.lock';
    }

    private function acquireSqlite()
    {
        $this->_fileHandle = fopen($this->sqliteLockFile(), 'c');
        if ($this->_fileHandle === false) {
            throw new Exception('Unable to open SQLite retriever command lock file');
        } // if

        $this->_acquired = flock($this->_fileHandle, LOCK_EX | LOCK_NB);
        if (!$this->_acquired) {
            fclose($this->_fileHandle);
            $this->_fileHandle = null;
        } // if

        return $this->_acquired;
    }

    public function release()
    {
        if (!$this->_acquired) {
            return;
        } // if

        try {
            if ($this->_conn instanceof dbeng_pdo_mysql) {
                $this->_conn->singleQuery(
                    'SELECT RELEASE_LOCK(:lockname)',
                    [
                        ':lockname' => [$this->_lockName, PDO::PARAM_STR],
                    ]
                );
            } elseif ($this->_conn instanceof dbeng_pdo_pgsql) {
                $this->_conn->singleQuery(
                    'SELECT pg_advisory_unlock(:classid, :objectid)',
                    [
                        ':classid'  => [self::PgLockClassId, PDO::PARAM_INT],
                        ':objectid' => [self::PgLockObjectId, PDO::PARAM_INT],
                    ]
                );
            } elseif ($this->_conn instanceof dbeng_pdo_sqlite) {
                flock($this->_fileHandle, LOCK_UN);
                fclose($this->_fileHandle);
                $this->_fileHandle = null;
            } // if
        } catch (Exception $x) {
            SpotDebug::msg(SpotDebug::DEBUG, 'Unable to release retriever command lock: '.$x->getMessage());
        } // catch

        $this->_acquired = false;
    }

    public function isAcquired()
    {
        return $this->_acquired;
    }
}
