<?php

use PHPUnit\Framework\TestCase;

class ServicesNntpArchitectureTest extends TestCase
{
    public function testLegacyNntpStackDoesNotReturnToRuntimeCode()
    {
        $paths = [
            __DIR__.'/../../../lib',
            __DIR__.'/../../../bin',
            __DIR__.'/../../../composer.json',
            __DIR__.'/../../../composer.lock',
            __DIR__.'/../../../vendor/composer',
        ];

        $haystack = $this->readPaths($paths);
        $forbidden = [
            'Services_Nntp_Engine',
            'Services_Nntp_EnginePool',
            'Services_Nntp_SpotReading',
            'Services_Nntp_SpotPosting',
            'Net_NNTP',
            'spotweb/nntp',
        ];

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString($needle, $haystack, $needle.' must not reappear in runtime code or Composer metadata');
        }
    }

    public function testProtocolPrimitivesStayInCentralTransport()
    {
        $allowed = [
            realpath(__DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php'),
            realpath(__DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php'),
        ];
        $scanRoot = realpath(__DIR__.'/../../../lib/services/Nntp');
        $forbidden = [
            'stream_socket_',
            'stream_select',
            'stream_context_create',
            'stream_set_blocking',
            'stream_socket_enable_crypto',
            'AUTHINFO',
            'STARTTLS',
            "'ARTICLE ",
            "'BODY ",
            "'HEAD ",
            "'POST'",
            '"ARTICLE ',
            '"BODY ',
            '"HEAD ',
            '"POST"',
        ];

        foreach ($this->filesUnder($scanRoot) as $file) {
            if (in_array(realpath($file), $allowed, true)) {
                continue;
            }
            $contents = file_get_contents($file);
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $contents, $needle.' found outside Services_Nntp_PipelinedTransport in '.$file);
            }
        }
    }

    private function readPaths(array $paths)
    {
        $contents = '';
        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach ($this->filesUnder($path) as $file) {
                    $contents .= file_get_contents($file)."\n";
                }
            } else {
                $contents .= file_get_contents($path)."\n";
            }
        }

        return $contents;
    }

    private function filesUnder($path)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
