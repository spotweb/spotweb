<?php

use PHPUnit\Framework\TestCase;

class PipelinedCommentsBenchmarkTest extends TestCase
{
    public function testFixtureBenchmarkEmitsJsonLinesWithoutDatabase()
    {
        $cmd = escapeshellarg(PHP_BINARY).' '.
            escapeshellarg(__DIR__.'/../../utils/pipelined_comments_benchmark.php').
            ' --read-only --fixture --group free.pt --first 100 --count 5 --windows 1,4 --samples 2 --sweep --seed 123';

        exec($cmd, $output, $exitCode);

        $this->assertSame(0, $exitCode);
        $this->assertCount(4, $output);
        foreach ($output as $line) {
            $row = json_decode($line, true);
            $this->assertSame('fixture', $row['mode']);
            $this->assertSame(5, $row['header_count']);
            $this->assertSame(5, $row['terminal_count']);
            $this->assertSame(0, $row['unresolved_count']);
            $this->assertTrue($row['ok']);
            $this->assertArrayNotHasKey('host', $row);
            $this->assertArrayNotHasKey('user', $row);
            $this->assertArrayNotHasKey('pass', $row);
        }
    }

    public function testBenchmarkRefusesWithoutReadOnlyGuard()
    {
        $cmd = escapeshellarg(PHP_BINARY).' '.
            escapeshellarg(__DIR__.'/../../utils/pipelined_comments_benchmark.php').
            ' --fixture --group free.pt --first 100 --count 5 2>/dev/null';

        exec($cmd, $output, $exitCode);

        $this->assertSame(2, $exitCode);
    }
}
