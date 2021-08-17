<?php

use \Imdb\Request;

class RequestTest extends PHPUnit\Framework\TestCase
{
    public function test_get()
    {
        $config = new \Imdb\Config();
        $request = new Request('https://m.media-amazon.com/images/M/MV5BNzQzOTk3OTAtNDQ0Zi00ZTVkLWI0MTEtMDllZjNkYzNjNTc4L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_UX182_CR0,0,182,268_AL_.jpg', $config);
        $ok = $request->sendRequest();
        $this->assertTrue($ok);
        $headers = $request->getLastResponseHeaders();
        $this->assertTrue(count($headers) > 5);
        $this->assertEquals($request->getresponseheader('Content-Type'), 'image/jpeg');
    }
}
