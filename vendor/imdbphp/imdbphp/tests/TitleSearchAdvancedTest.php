<?php

use Imdb\Config;
use Imdb\TitleSearchAdvanced;

class TitleSearchAdvancedTest extends PHPUnit\Framework\TestCase
{
    public function test_by_language_year()
    {
        $search = $this->getTitleSearchAdvanced();
        $search->setLanguages(array('en'));
        $search->setYear(2000);
        $list = $search->search();
        $this->assertIsArray($list);
        $this->assertCount(25, $list);
    }

    public function test_episodes()
    {
        $search = $this->getTitleSearchAdvanced();
        $search->setTitleTypes(array(TitleSearchAdvanced::TV_EPISODE));
        $list = $search->search();
        $this->assertIsArray($list);
        $this->assertCount(25, $list);

        foreach ($list as $result) {
            if ($result['imdbid'] === '12763776') {
                // This is a movie not a tv episode .. not sure why it shows in results
                continue;
            }
            $this->assertNotEmpty($result['title']);
            $this->assertEquals('TV Episode', $result['type']);
            $this->assertTrue($result['serial']);
            $this->assertNotEmpty($result['episode_title']);
            $this->assertNotEmpty($result['episode_imdbid']);
        }
    }

    public function test_sort()
    {
        $search = $this->getTitleSearchAdvanced();
        $search->setSort(TitleSearchAdvanced::SORT_NUM_VOTES);
        $search->setYear(2003);
        $search->setTitleTypes(array(TitleSearchAdvanced::TV_EPISODE));
        $list = $search->search();

        $this->assertIsArray($list);
        $this->assertCount(25, $list);

        $trash = current(array_filter($list, function ($item) {
            return $item['episode_imdbid'] == '0579540';
        }));

        $this->assertIsArray($trash);
        $this->assertEquals('0303461', $trash['imdbid']);
        $this->assertEquals('Firefly', $trash['title']);
        $this->assertEquals('2002', $trash['year']);
        $this->assertEquals('TV Episode', $trash['type']);
        $this->assertEquals('0579540', $trash['episode_imdbid']);
        $this->assertEquals('Trash', $trash['episode_title']);
        $this->assertEquals(2003, $trash['episode_year']);

        $theMessage = current(array_filter($list, function ($item) {
            return $item['episode_imdbid'] == '0579538';
        }));

        $this->assertIsArray($theMessage);
        $this->assertEquals('0303461', $theMessage['imdbid']);
        $this->assertEquals('Firefly', $theMessage['title']);
        $this->assertEquals('2002', $theMessage['year']);
        $this->assertEquals('TV Episode', $theMessage['type']);
        $this->assertEquals('0579538', $theMessage['episode_imdbid']);
        $this->assertEquals('The Message', $theMessage['episode_title']);
        $this->assertEquals(2003, $theMessage['episode_year']);
    }

    protected function getTitleSearchAdvanced()
    {
        $config = new Config();
        $config->language = 'en-GB';
        $config->imdbsite = 'www.imdb.com';
        $config->cachedir = realpath(dirname(__FILE__) . '/cache') . '/';
        $config->usezip = false;
        $config->cache_expire = 86400;

        return new TitleSearchAdvanced($config);
    }
}
