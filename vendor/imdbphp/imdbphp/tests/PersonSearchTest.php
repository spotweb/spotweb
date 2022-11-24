<?php

use Imdb\Config;
use Imdb\Person;
use Imdb\PersonSearch;

class PersonSearchTest extends PHPUnit\Framework\TestCase
{
    public function test_searching_for_a_specific_actor_returns_him()
    {
        $search = $this->getimdbpersonsearch();
        $results = $search->search('Forest Whitaker');

        $this->assertIsArray($results);
        //print_r($results);
        /* @var $firstResult \Imdb\Person */
        $firstResult = $results[0];
        $this->assertInstanceOf('\Imdb\Person', $firstResult);
        // Break its imdbsite so it can't make any external requests. This ensures the search class added these properties
        $firstResult->imdbsite = '';
        $this->assertEquals("0001845", $firstResult->imdbid());
        $this->assertEquals("Forest Whitaker", $firstResult->name());
    }

    public function test_searching_returns_person_with_id_of_8_char()
    {
        $search = $this->getimdbpersonsearch();
        $results = $search->search('John Zuberek');

        $firstResult = $results[0];
        $this->assertEquals("11373523", $firstResult->imdbid());
    }

    protected function getimdbpersonsearch()
    {
        $config = new Config();
        $config->language = 'en';
        $config->cachedir = realpath(dirname(__FILE__) . '/cache') . '/';
        $config->usezip = false;
        $config->cache_expire = 3600;
        $config->debug = false;

        $imdbsearch = new PersonSearch($config);
        return $imdbsearch;
    }
    // @TODO more tests
}
