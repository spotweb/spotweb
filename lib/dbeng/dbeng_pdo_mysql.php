<?php

class dbeng_pdo_mysql extends dbeng_pdo
{
    /**
     * @var PDO
     */
    protected $_conn;

    public function __construct()
    {
        /*
         * arbitrarily chosen because some insert statements might
         * be very large.
         */
        $this->_batchInsertChunks = 100;
    }

    public function connect($host, $user, $pass, $db, $port, $schema)
    {
        if (!$this->_conn instanceof PDO) {
            if ($host[0] === '/') {
                $db_conn = 'unix_socket='.$host;
            } else {
                $db_conn = 'host='.$host.':'.$port;
            }

            try {
                $this->_conn = new PDO(
                    'mysql:'.$db_conn.';dbname='.$db.';charset=utf8',
                    $user,
                    $pass,
                    [PDO::MYSQL_ATTR_FOUND_ROWS => true]
                );
            } catch (PDOException $e) {
                throw new DatabaseConnectionException($e->getMessage(), -1);
            }

            $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } // if
    }

    // connect()

    public function connectRoot($host, $pass, $port)
    {
        $this->connect($host, 'root', $pass, '', $port, '');
    }

    public function createDb($db, $usr, $pass)
    {
        $rowsFound = $this->exec(
            'SELECT *  FROM INFORMATION_SCHEMA.SCHEMATA where SCHEMA_NAME = :dbid',
            [':dbid' => [$db, PDO::PARAM_STR]]
        )->rowCount();
        if ($rowsFound == 0) {
            $this->exec('CREATE DATABASE '.$db);
        } //$rowsFound == 0

        try {
            $userexists = $this->exec(
                'SELECT 1 FROM mysql.user WHERE user = :user',
                [':user' => [$usr, PDO::PARAM_STR]]
            )->rowCount();
            if ($userexists <= 0) {
                $this->exec(
                    'CREATE USER :user IDENTIFIED by :pwd',
                    [':user'    => [$usr, PDO::PARAM_STR],
                        ':pwd'  => [$pass, PDO::PARAM_STR],
                    ]
                );
            }
            $this->exec(
                'GRANT ALL privileges ON '.$db.'.* TO :user',
                [':user' => [$usr, PDO::PARAM_STR],
                ]
            );
        } catch (Exception $e) {
            $this->exec('DROP DATABASE '.$db);

            throw $e;
        }
    }
} // class
