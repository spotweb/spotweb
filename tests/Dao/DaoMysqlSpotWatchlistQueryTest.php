<?php

use PHPUnit\Framework\TestCase;

class DaoMysqlSpotWatchlistQueryTest extends TestCase
{
    public function testWatchlistQueryStartsFromSpotStateList()
    {
        $dao = new Dao_Mysql_Spot($this->newDatabaseMock());
        $query = $dao->getQuerystr(
            '',
            '',
            '',
            7,
            ' WHERE (bl.spotterid IS NULL) AND (l.watch IS NOT NULL)',
            'l.watchstamp DESC',
            25,
            0
        );

        $this->assertStringContainsString('FROM spotstatelist AS l STRAIGHT_JOIN spots AS s', $query);
        $this->assertStringNotContainsString('FORCE INDEX FOR JOIN (idx_spotstatelist_1)', $query);
        $this->assertStringContainsString('(l.ouruserid = 7)', $query);
        $this->assertStringContainsString('(l.watch IS NOT NULL)', $query);
    }

    public function testNonWatchlistQueryKeepsExistingSpotStateJoin()
    {
        $dao = new Dao_Mysql_Spot($this->newDatabaseMock());
        $query = $dao->getQuerystr(
            '',
            '',
            '',
            7,
            ' WHERE (bl.spotterid IS NULL) AND (s.category = 1)',
            's.stamp DESC',
            25,
            0
        );

        $this->assertStringContainsString('FROM spots AS s', $query);
        $this->assertStringContainsString('LEFT JOIN spotstatelist AS l FORCE INDEX FOR JOIN (idx_spotstatelist_1)', $query);
    }

    private function newDatabaseMock()
    {
        $database = $this->getMockForAbstractClass(dbeng_abs::class);
        $database->method('safe')->willReturnCallback(function ($value) {
            return (string) $value;
        });

        return $database;
    }
}
