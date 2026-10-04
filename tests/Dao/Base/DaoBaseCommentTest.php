<?php

use PHPUnit\Framework\TestCase;

class DaoBaseCommentTestDb extends dbeng_abs
{
    public $batchInsertRows = [];

    public function connect($host, $user, $pass, $db, $port, $schema)
    {
    }

    public function rawExec($sql)
    {
    }

    public function singleQuery($sql, $params = [])
    {
    }

    public function arrayQuery($sql, $params = [])
    {
    }

    public function safe($s)
    {
        return $s;
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
        $this->batchInsertRows = $ar;
    }

    public function exec($s, $p = [])
    {
    }

    public function modify($s, $p = [])
    {
    }
}

class DaoBaseCommentTest extends TestCase
{
    public function testAddFullCommentsNormalizesFromHeaderBeforeInsert()
    {
        $db = new DaoBaseCommentTestDb();
        $dao = new Dao_Base_Comment($db);

        $dao->addFullComments([
            [
                'messageid'      => '<comment@example>',
                'fromhdr'        => "Valid \xC3\x28 snowman ☃",
                'stamp'          => '123',
                'user-signature' => 'signature',
                'user-key'       => ['public' => 'key'],
                'spotterid'      => 'spotter',
                'body'           => 'body',
                'verified'       => true,
                'user-avatar'    => '',
            ],
        ]);

        $this->assertCount(1, $db->batchInsertRows);
        $this->assertSame(
            mb_convert_encoding("Valid \xC3\x28 snowman ☃", 'UTF-8', 'UTF-8'),
            $db->batchInsertRows[0]['fromhdr']
        );
        $this->assertSame('UTF-8', mb_detect_encoding($db->batchInsertRows[0]['fromhdr'], 'UTF-8', true));
    }
}
