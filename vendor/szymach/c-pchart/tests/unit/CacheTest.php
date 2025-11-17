<?php

declare(strict_types=1);

namespace Tests\CpChart\Unit;

use Codeception\Test\Unit;
use CpChart\Cache;
use CpChart\Data;
use CpChart\Image;
use Tests\CpChart\Support\UnitTester;

use const DIRECTION_VERTICAL;

final class CacheTest extends Unit
{
    protected UnitTester $tester;

    public function testWritingAndRetrievingOperations(): void
    {
        [$data, $image] = $this->createImageData();

        // Write to cache
        $cache = new Cache();
        $chartHash = $cache->getHash($data);
        $cache->writeToCache($chartHash, $image);
        $this->tester->seeFileFound($this->getCacheFilePath('cache.db'));
        $this->tester->seeFileFound($this->getCacheFilePath('index.db'));
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));

        // Render and then remove the chart
        $filename = $this->tester->getOutputPathForChart('drawCachedSpline.png');
        $image->render($filename);
        $this->tester->seeFileFound($filename);
        $this->tester->deleteFile($filename);
        $this->tester->cantSeeFileFound($filename);

        // Test retrieving image from cache
        $cache->saveFromCache($chartHash, $filename);
        $this->tester->seeFileFound($filename);
        $this->tester->assertEquals(true, $cache->strokeFromCache($chartHash));
    }

    public function testRemovalOperations(): void
    {
        [$data, $image] = $this->createImageData();

        // Write to cache
        $cache = new Cache();
        $chartHash = $cache->getHash($data);
        $cache->writeToCache($chartHash, $image);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));

        // Remove by name
        $cache->remove($chartHash);
        $this->tester->assertEquals(false, $cache->isInCache($chartHash));

        // Remove older than x seconds
        $cache->writeToCache($chartHash, $image);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));
        $cache->removeOlderThan(4);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));
        sleep(5);
        $cache->removeOlderThan(4);
        $this->tester->assertEquals(false, $cache->isInCache($chartHash));

        // Flush the cache
        $cache->writeToCache($chartHash, $image);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));
        $cache->flush();
        $this->tester->assertEquals(false, $cache->isInCache($chartHash));
    }

    protected function _before(): void
    {
        $this->clearCache();
    }

    protected function _after(): void
    {
        $this->clearCache();
    }

    /**
     * @return array{ 0: Data, 1: Image }
     */
    private function createImageData(): array
    {
        $data = new Data();
        $data->addPoints([1, 3, 4, 3, 5]);

        $image = new Image(700, 230, $data);
        $image->setFontProperties(['FontName' => 'Forgotte.ttf', 'FontSize' => 11]);
        $image->setGraphArea(60, 40, 670, 190);
        $image->drawScale();
        $image->drawSplineChart();
        $image->drawGradientArea(
            0,
            0,
            700,
            20,
            DIRECTION_VERTICAL,
            [   'StartR' => 0, 'StartG' => 0, 'StartB' => 0, 'EndR' => 50, 'EndG' => 50,
                'EndB' => 50, 'Alpha' => 100
            ]
        );
        $image->setFontProperties(['FontName' => 'Silkscreen.ttf', 'FontSize' => 6]);
        $image->drawText(
            10,
            13,
            'Test of the pCache final class',
            ['R' => 255, 'G' => 255, 'B' => 255]
        );

        return [$data, $image];
    }

    private function clearCache(): void
    {
        foreach (['cache.db', 'index.db'] as $cacheFile) {
            $filename = $this->getCacheFilePath($cacheFile);
            if (true === file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    private function getCacheFilePath(string $filename): string
    {
        return sprintf('%s/%s', $this->tester->getCacheDirectory(), $filename);
    }
}
