<?php

namespace RetrieveExternalListsTest;

use PHPUnit\Framework\TestCase;

class Services_BWList_Retriever
{
    public static $calls = [];
    public static $instances = 0;
    public static $result = false;

    public function __construct($blackWhiteListDao, $cacheDao)
    {
        self::$instances++;
    }

    public function retrieveBlackList($url)
    {
        self::$calls[] = ['black', $url];

        return self::$result;
    }

    public function retrieveWhiteList($url)
    {
        self::$calls[] = ['white', $url];
        if (self::$result instanceof \Exception) {
            throw self::$result;
        }

        return self::$result;
    }
}

class RetrieveExternalListsTest extends TestCase
{
    public function testWhitelistOnlyInitializesServiceAndRetrievesWhitelist()
    {
        $this->assertSame('Whitelist not modified, no need to update'.PHP_EOL, $this->runListPhase(false, true));
        $this->assertSame([['white', 'https://example.invalid/white.xml']], Services_BWList_Retriever::$calls);
    }

    public function testBlacklistOnlyRetrievesBlacklist()
    {
        $this->assertSame('Blacklist not modified, no need to update'.PHP_EOL, $this->runListPhase(true, false));
        $this->assertSame([['black', 'https://example.invalid/black.xml']], Services_BWList_Retriever::$calls);
    }

    public function testBothListsShareOneServiceAndRunInOrder()
    {
        $output = $this->runListPhase(true, true);
        $this->assertSame('Blacklist not modified, no need to update'.PHP_EOL.'Whitelist not modified, no need to update'.PHP_EOL, $output);
        $this->assertSame([['black', 'https://example.invalid/black.xml'], ['white', 'https://example.invalid/white.xml']], Services_BWList_Retriever::$calls);
    }

    public function testNeitherListCreatesServiceOrRetrievesAnything()
    {
        $this->assertSame('', $this->runListPhase(false, false));
        $this->assertSame([], Services_BWList_Retriever::$calls);
    }

    public function testWhitelistOnlyReportsSuccessfulUpdate()
    {
        $output = $this->runListPhase(false, true, ['added' => 2, 'removed' => 1, 'skipped' => 3, 'total' => 6]);
        $this->assertSame('Finished updating whitelist. Added 2, removed 1, skipped 3 of 6 lines.'.PHP_EOL, $output);
        $this->assertSame([['white', 'https://example.invalid/white.xml']], Services_BWList_Retriever::$calls);
    }

    public function testCorruptWhitelistKeepsExistingNonFatalHandling()
    {
        $output = $this->runListPhase(false, true, new \CorruptBWListException());
        $this->assertSame(PHP_EOL.'Non-fatal: Updating black/whitelist failed, most likely unreachable!', $output);
    }

    private function runListPhase($blacklist, $whitelist, $result = false)
    {
        Services_BWList_Retriever::$calls = [];
        Services_BWList_Retriever::$instances = 0;
        Services_BWList_Retriever::$result = $result;
        $settings = $this->createMock(\Services_Settings_Container::class);
        $settings->method('get')->willReturnMap([
            ['external_blacklist', $blacklist],
            ['external_whitelist', $whitelist],
            ['blacklist_url', 'https://example.invalid/black.xml'],
            ['whitelist_url', 'https://example.invalid/white.xml'],
        ]);
        $daoFactory = $this->createMock(\Dao_Factory::class);
        $daoFactory->expects($blacklist || $whitelist ? $this->once() : $this->never())
            ->method('getBlackWhiteListDao')->willReturn($this->createMock(\Dao_BlackWhiteList::class));
        $daoFactory->expects($blacklist || $whitelist ? $this->once() : $this->never())
            ->method('getCacheDao')->willReturn($this->createMock(\Dao_Cache::class));

        // Execute the actual list phase, with namespaced doubles, without booting the retriever.
        $source = file_get_contents(__DIR__.'/../../../retrieve.php');
        $marker = strpos($source, '//# External blacklist');
        $start = strrpos(substr($source, 0, $marker), 'try {');
        $end = strpos($source, '//# Statistics', $marker);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        ob_start();

        try {
            eval('namespace '.__NAMESPACE__.'; use \\CorruptBWListException; '.substr($source, $start, $end - $start));
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertSame($blacklist || $whitelist ? 1 : 0, Services_BWList_Retriever::$instances);

        return $output;
    }
}
