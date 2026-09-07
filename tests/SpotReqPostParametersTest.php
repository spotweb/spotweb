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
 * Regression coverage for compact POST-based bulk NZB routing parameters.
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
     * Bulk NZB uses one JSON request value for many message IDs.
     */
    public function testBulkGetNzbParametersDecodeFromPostBody()
    {
        $messageIds = [];
        for ($i = 0; $i < 2000; $i++) {
            $messageIds[] = sprintf('<spot-%04d@example.invalid>', $i);
        } // for

        $_GET = [];
        $_POST = [
            'page'      => 'getnzb',
            'action'    => 'push-sabnzbd',
            'messageids' => json_encode($messageIds),
        ];

        $req = new SpotReq();
        $req->initialize(new SpotReqPostParametersTestSettings());

        $this->assertSame('getnzb', $req->getDef('page', 'index'));
        $this->assertSame('push-sabnzbd', $req->getDef('action', 'display'));
        $this->assertCount(3, $_POST);
        $this->assertSame($messageIds, Services_Actions_DownloadNzb::resolveMessageIds(
            $req->getDef('messageid', ''),
            $req->getDef('messageids', null)
        ));
    }

    /**
     * Legacy single-message callers retain their original scalar input.
     */
    public function testLegacyScalarMessageIdRemainsSupported()
    {
        $messageId = '<single@example.invalid>';

        $this->assertSame($messageId, Services_Actions_DownloadNzb::resolveMessageIds($messageId, null));
    }

    /**
     * Legacy messageid[] callers retain their original array input.
     */
    public function testLegacyMessageIdArrayRemainsSupported()
    {
        $messageIds = ['<first@example.invalid>', '<second@example.invalid>'];

        $this->assertSame($messageIds, Services_Actions_DownloadNzb::resolveMessageIds($messageIds, null));
    }

    /**
     * Invalid compact input fails in a controlled way before download handling.
     */
    public function testInvalidBulkMessageIdsAreRejected()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid bulk NZB message ID list');

        Services_Actions_DownloadNzb::resolveMessageIds('', '{"messageid":"not-an-array"}');
    }

    /**
     * Malformed compact input fails in the same controlled way.
     */
    public function testMalformedBulkMessageIdsAreRejected()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid bulk NZB message ID list');

        Services_Actions_DownloadNzb::resolveMessageIds('', '["unterminated"');
    }
}
