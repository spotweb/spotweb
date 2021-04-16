<?php

class dbeng_pdo_sqlite extends dbeng_pdo
{
    protected $_conn;

    public function __construct()
    {
        /*
         * sqlite does not support batch inserts
         */
        $this->_batchInsertChunks = 1;
    }

    // ctor

    public function connect($host, $user, $pass, $db, $port, $schema)
    {
        try {
            if (!$this->_conn instanceof PDO) {
                $this->_conn = new PDO('sqlite:'.$db);
                $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->_conn->setAttribute(PDO::ATTR_TIMEOUT, 300);
            } // if
        } catch (PDOException $e) {
            throw new DatabaseConnectionException($e->getMessage(), -1);
        } // catch
    }

    // connect()
} // class
