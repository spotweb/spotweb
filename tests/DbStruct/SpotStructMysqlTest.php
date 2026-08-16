<?php

use PHPUnit\Framework\TestCase;

class SpotStructMysqlTestDb extends dbeng_abs
{
    public $rawExecQueries = [];
    private $tableExists;
    private $tableCollation;
    private $engine;

    public function __construct($tableExists = false, $tableCollation = 'utf8mb4_unicode_ci', $engine = 'InnoDB')
    {
        $this->tableExists = $tableExists;
        $this->tableCollation = $tableCollation;
        $this->engine = $engine;
    }

    public function connect($host, $user, $pass, $db, $port, $schema)
    {
    }

    public function rawExec($sql)
    {
        $this->rawExecQueries[] = $sql;
    }

    public function singleQuery($sql, $params = [])
    {
        if (strpos($sql, 'SELECT TABLE_COLLATION') !== false) {
            return $this->tableCollation;
        }

        if (strpos($sql, 'SELECT ENGINE') !== false) {
            return $this->engine;
        }
    }

    public function arrayQuery($sql, $params = [])
    {
        if (strpos($sql, 'SHOW TABLES LIKE') !== false) {
            return $this->tableExists ? [['table']] : [];
        }

        return [];
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
    }

    public function exec($s, $p = [])
    {
    }

    public function modify($s, $p = [])
    {
    }
}

class SpotStructMysqlTest extends TestCase
{
    public function testCreateTableUsesInnoDbAndCanonicalUtf8mb4Default()
    {
        $db = new SpotStructMysqlTestDb(false);
        $schema = new SpotStruct_mysql($db);

        $schema->createTable('commentsxover', 'utf8');

        $this->assertSame(
            'CREATE TABLE commentsxover (id INTEGER PRIMARY KEY AUTO_INCREMENT) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $db->rawExecQueries[0]
        );
    }

    public function testExistingTableDefaultConvergesToCanonicalUtf8mb4()
    {
        $db = new SpotStructMysqlTestDb(true, 'ascii_general_ci');
        $schema = new SpotStruct_mysql($db);

        $schema->createTable('commentsxover', 'utf8');

        $this->assertSame(
            'ALTER TABLE commentsxover DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $db->rawExecQueries[0]
        );
    }

    public function testUtf8ColumnsUseCanonicalUtf8mb4Collation()
    {
        $db = new SpotStructMysqlTestDb(false);
        $schema = new SpotStruct_mysql($db);

        $schema->addColumn('title', 'spots', 'VARCHAR(128)', null, false, 'utf8');

        $this->assertStringContainsString(
            'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $db->rawExecQueries[0]
        );
    }

    public function testSchemaDeclarationsDoNotRequestMyIsam()
    {
        $schemaSource = file_get_contents(__DIR__.'/../../lib/dbstruct/SpotStruct_abs.php');

        $this->assertStringNotContainsString('MyISAM', $schemaSource);
    }
}
