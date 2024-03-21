<?php

namespace PhpCoveralls\Tests\Component\System\Git;

use PhpCoveralls\Component\System\Git\GitCommand;
use PhpCoveralls\Tests\ProjectTestCase;

/**
 * @covers \PhpCoveralls\Component\System\Git\GitCommand
 * @covers \PhpCoveralls\Component\System\SystemCommandExecutor
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 *
 * @internal
 */
final class GitCommandTest extends ProjectTestCase
{
    /**
     * @test
     */
    public function shouldReturnBranches()
    {
        $object = new GitCommand();
        $actual = $object->getBranches();

        self::assertIsArray($actual);
        self::assertNotEmpty($actual);
    }

    /**
     * @test
     */
    public function shouldReturnHeadCommit()
    {
        $object = new GitCommand();
        $actual = $object->getHeadCommit();

        self::assertIsArray($actual);
        self::assertNotEmpty($actual);
        self::assertCount(6, $actual);
    }

    /**
     * @test
     */
    public function shouldReturnRemotes()
    {
        $object = new GitCommand();
        $actual = $object->getRemotes();

        self::assertIsArray($actual);
        self::assertNotEmpty($actual);
    }
}
