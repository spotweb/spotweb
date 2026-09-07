<?php

use PHPUnit\Framework\TestCase;

/**
 * Minimal settings object for SpotReq initialization.
 */
class SpotReqPostParametersTestSettings
{
    /**
     * Return the requested setting value.
     */
    public function get($name)
    {
        if ($name === 'xsrfsecret') {
            return 'test-secret';
        } // if

        return null;
    }
}

/**
 * Regression coverage for POST-based bulk NZB routing parameters.
 */
class SpotReqPostParametersTest extends TestCase
{
    /**
     * Reset superglobals touched by these tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
    }

    /**
     * Bulk NZB can pass many message IDs through POST without URL expansion.
     */
    public function testBulkGetNzbParametersCanComeFromPostBody()
    {
        $messageIds = [];
        for ($i = 0; $i < 242; $i++) {
            $messageIds[] = sprintf('<spot-%03d@example.invalid>', $i);
        } // for

        $_GET = [];
        $_POST = [
            'page'      => 'getnzb',
            'action'    => 'push-sabnzbd',
            'messageid' => $messageIds,
        ];

        $req = new SpotReq();
        $req->initialize(new SpotReqPostParametersTestSettings());

        $this->assertSame('getnzb', $req->getDef('page', 'index'));
        $this->assertSame('push-sabnzbd', $req->getDef('action', 'display'));
        $this->assertSame($messageIds, $req->getDef('messageid', []));
    }
}
