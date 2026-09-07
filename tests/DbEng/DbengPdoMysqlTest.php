<?php

use PHPUnit\Framework\TestCase;

class DbengPdoMysqlTestWrapper extends dbeng_pdo_mysql
{
    public function connectionString($host, $db, $port)
    {
        return $this->buildConnectionString($host, $db, $port);
    }
}

class DbengPdoMysqlTest extends TestCase
{
    public function testTcpConnectionStringUsesUtf8mb4()
    {
        $db = new DbengPdoMysqlTestWrapper();

        $this->assertSame(
            'mysql:host=db.example:3306;dbname=spotweb;charset=utf8mb4',
            $db->connectionString('db.example', 'spotweb', '3306')
        );
    }

    public function testSocketConnectionStringUsesUtf8mb4()
    {
        $db = new DbengPdoMysqlTestWrapper();

        $this->assertSame(
            'mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=spotweb;charset=utf8mb4',
            $db->connectionString('/run/mysqld/mysqld.sock', 'spotweb', '3306')
        );
    }
}
