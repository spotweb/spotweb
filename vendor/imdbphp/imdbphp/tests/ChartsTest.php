<?php

use Imdb\Charts;

class ChartsTest extends PHPUnit\Framework\TestCase
{
    public function test_getChartsTop10()
    {
        $charts = $this->getCharts();
        $moviemeter = $charts->getChartsTop10();
        $this->assertIsArray($moviemeter);
        $this->assertEquals(count($moviemeter), count(array_unique($moviemeter)), "Results contain duplicates");
        $this->assertCount(10, $moviemeter);

        for ($i = 0; $i < 10; $i++) {
            $this->assertIsString($moviemeter[$i]);
            $this->assertThat(strlen($moviemeter[$i]), $this->logicalOr(
                $this->equalTo(7),
                $this->equalTo(8)
            ), "imdb IDs should be 7 or 8 digits");
        }
    }

    public function test_getChartsBoxOffice()
    {
        $charts = $this->getCharts();
        $boxOffice = $charts->getChartsBoxOffice();

        $this->assertIsArray($boxOffice);
        // Commented out while cinemas are closed
        // $this->assertTrue(count($boxOffice) >= 9);
        // $this->assertTrue(count($boxOffice) < 11);
        foreach ($boxOffice as $film) {
            $this->assertIsArray($film);
            $this->assertCount(3, $film);
            $this->assertTrue(is_numeric($film['weekend']));
            $this->assertTrue(is_numeric($film['gross']));
        }
    }

    protected function getCharts()
    {
        $config = new \Imdb\Config();
        $config->language = 'en-GB';
        $config->imdbsite = 'www.imdb.com';
        $config->cachedir = realpath(dirname(__FILE__) . '/cache') . '/';
        $config->usezip = false;
        $config->cache_expire = 86400;

        return new Charts($config);
    }
}
