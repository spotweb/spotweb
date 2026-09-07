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

    public function testSpotwebRootConfigDiscoveryUsesExplicitReadOnlyRoot()
    {
        $root = sys_get_temp_dir().'/spotweb-root-'.getmypid().'-'.bin2hex(random_bytes(4));
        mkdir($root);
        file_put_contents($root.'/dbsettings.inc.php', "<?php\n\$dbsettings = ['engine' => 'pdo_mysql'];\n");
        file_put_contents($root.'/settings.php', "<?php\n\$settings = [];\n");

        $cmd = escapeshellarg(PHP_BINARY).' '.
            escapeshellarg(__DIR__.'/../../utils/pipelined_comments_benchmark.php').
            ' --read-only --config-discovery-check --spotweb-root '.escapeshellarg($root);

        exec($cmd, $output, $exitCode);

        unlink($root.'/dbsettings.inc.php');
        unlink($root.'/settings.php');
        rmdir($root);

        $this->assertSame(0, $exitCode);
        $this->assertCount(1, $output);
        $row = json_decode($output[0], true);
        $this->assertSame('config-discovery-check', $row['mode']);
        $this->assertTrue($row['ok']);
        $this->assertTrue($row['dbsettings_present']);
        $this->assertTrue($row['settings_present']);
        $this->assertArrayNotHasKey('host', $row);
        $this->assertArrayNotHasKey('user', $row);
        $this->assertArrayNotHasKey('pass', $row);
    }
}
