<?php

use Imdb\Pages;
use Imdb\Config;
use Imdb\Cache;
use Imdb\Logger;

class PagesTest extends PHPUnit\Framework\TestCase
{
    public function testGetRetrievesFromCache()
    {
        $cache = Mockery::mock('\Imdb\Cache', array(
                'get' => 'test'
        ));

        $pages = new Pages(new Config(), $cache, new Logger(false));

        $result = $pages->get('/');

        $this->assertEquals('test', $result);
    }

    // In particular PSR16 caches don't allow {}()/\@: in keys
    public function testGetRetrievesFromAnotherPSR16Cache()
    {
        $cache = new \Cache\Adapter\PHPArray\ArrayCachePool();
        $cache->set('search.title?locations=.test..', 'test');

        $pages = new Pages(new Config(), $cache, new Logger(false));

        // Get something that has characters the PSR16 spec doesn't like
        $result = $pages->get('/search/title?locations=(test:)');

        $this->assertEquals('test', $result);
    }

    // It should use its internal cache of the returned strings rather than using the cache object every time
    // The default cache is on disk and replacement cache would also probably involve some IO
    public function testGetDoesNotUseCacheObjectForSecondCall()
    {
        $cache = Mockery::mock('\Imdb\Cache');
        $cache->shouldReceive('get')->once()->andReturn('test');

        $pages = new Pages(new Config(), $cache, new Logger(false));

        $result = $pages->get('/');

        $this->assertEquals('test', $result);

        $result2 = $pages->get('/');

        $this->assertEquals('test', $result2);
    }

    public function testGetMakesRequestIfNotInCache()
    {
        $cache = Mockery::mock('\Imdb\Cache', array(
                'get' => null,
                'set' => true
        ));
        $pages = Mockery::Mock('\Imdb\Pages[requestPage]', array(new Config(), $cache, new Logger(false)));
        $pages->shouldAllowMockingProtectedMethods();
        $pages->shouldReceive('requestPage')->once()->andReturn('test');

        $result = $pages->get('/');
        $this->assertEquals('test', $result);
    }

    public function testGetSavesToCache()
    {
        $config = new Config();

        $cache = Mockery::mock('\Imdb\Cache');
        $cache->shouldReceive('get')->once()->andReturn(null);
        $cache->shouldReceive('set')->with('title.whatever', 'test', $config->cache_expire)->once()->andReturn(true);

        $pages = Mockery::Mock('\Imdb\Pages[requestPage]', array($config, $cache, new Logger(false)));
        $pages->shouldAllowMockingProtectedMethods();
        $pages->shouldReceive('requestPage')->once()->andReturn('test');

        $result = $pages->get('/title/whatever');
        $this->assertEquals('test', $result);
        \Mockery::close();
    }

    public function testGetThrowsExceptionIfHttpFails()
    {
        $this->expectException(\Imdb\Exception\Http::class);
        $cache = Mockery::mock('\Imdb\Cache', array(
                'get' => null,
                'set' => true
        ));
        $request = Mockery::mock(array(
                'sendRequest' => false
        ));
        $pages = Mockery::Mock('\Imdb\Pages[buildRequest]', array(new Config(), $cache, new Logger(false)));
        $pages->shouldAllowMockingProtectedMethods();
        $pages->shouldReceive('buildRequest')->once()->andReturn($request);
        $pages->get('test');
    }

    public function testGetDoesNotThrowExceptionIfHttpFailsAndThrowHttpExceptionsIsFalse()
    {
        $cache = Mockery::mock('\Imdb\Cache', array(
                'get' => null,
                'set' => true
        ));
        $request = Mockery::mock(array(
                'sendRequest' => false
        ));
        $config = new Config();
        $config->throwHttpExceptions = false;
        $pages = Mockery::Mock('\Imdb\Pages[buildRequest]', array($config, $cache, new Logger(false)));
        $pages->shouldAllowMockingProtectedMethods();
        $pages->shouldReceive('buildRequest')->once()->andReturn($request);
        $pages->get('test');
        $this->assertTrue(true);
    }
}
