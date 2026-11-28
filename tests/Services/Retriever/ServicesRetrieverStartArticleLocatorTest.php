<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/SpotDebug.php';
require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_StartArticleLocator.php';

/*
 * Fake group where article $n is posted at $start + $n * $interval seconds,
 * with optional per article overrides and missing articles
 */
class ServicesRetrieverStartArticleLocatorTransport
{
    public $calls = 0;
    private $_start;
    private $_interval;
    private $_last;
    private $_stamps;
    private $_missing;

    public function __construct($start, $interval, $last, array $stamps = [], $missing = null)
    {
        $this->_start = $start;
        $this->_interval = $interval;
        $this->_last = $last;
        $this->_stamps = $stamps;
        $this->_missing = $missing;
    }

    public function stampOf($articleNr)
    {
        return isset($this->_stamps[$articleNr]) ? $this->_stamps[$articleNr] : $this->_start + $articleNr * $this->_interval;
    }

    public function getOverview($first, $last)
    {
        $this->calls++;
        $hdrList = [];

        for ($nr = $first; $nr <= min($last, $this->_last); $nr++) {
            if (($this->_missing !== null) && call_user_func($this->_missing, $nr)) {
                continue;
            }

            $hdrList[] = ['Number' => $nr, 'Date' => is_string($this->stampOf($nr)) ? $this->stampOf($nr) : date('r', $this->stampOf($nr))];
        }

        return $hdrList;
    }
}

class ServicesRetrieverStartArticleLocatorTest extends TestCase
{
    const Start = 1262304000; // 2010-01-01

    private function locate($transport, $first, $last, $newerThan)
    {
        return (new Services_Retriever_StartArticleLocator($transport))->locate($first, $last, $newerThan);
    }

    public function testStartsJustBeforeTheRequestedDate()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);
        $target = 60000;

        $result = $this->locate($transport, 1, 100000, $transport->stampOf($target));

        // at most the safety margin earlier, never later than the requested date
        $this->assertLessThanOrEqual($target, $result);
        $this->assertGreaterThanOrEqual($target - Services_Retriever_StartArticleLocator::SafetyMargin / 60, $result);
    }

    public function testOldOutlierDoesNotMakeUsStartLate()
    {
        // an article far in the past in the middle of the recent articles
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000, [60500 => self::Start - 5 * 365 * 86400]);
        $target = 60000;

        $result = $this->locate($transport, 1, 100000, $transport->stampOf($target));

        $this->assertLessThanOrEqual($target, $result);
    }

    public function testFutureOutlierOnlyMakesUsStartEarlier()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000, [100 => self::Start + 10 * 365 * 86400]);
        $target = 60000;

        $result = $this->locate($transport, 1, 100000, $transport->stampOf($target));

        $this->assertSame(100, $result);
    }

    public function testLowerArticleNumberWithNewerDateIsNeverSkipped()
    {
        // article 49,900 is recent while 49,950 and later are older (dates out of order)
        $recent = self::Start + 90000 * 60;
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000, [49900 => $recent]);

        $result = $this->locate($transport, 1, 100000, $recent);

        $this->assertLessThanOrEqual(49900, $result);
    }

    public function testGapsNeverMakeUsStartTooLate()
    {
        $missing = function ($nr) {
            return ($nr % 10000) < 7000;
        };
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000, [], $missing);

        foreach ([7000, 12345, 50000, 98765] as $target) {
            $this->assertLessThanOrEqual($target, $this->locate($transport, 1, 100000, $transport->stampOf($target)), 'target '.$target);
        }
    }

    public function testUnparseableDatesAreIgnored()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 50000, [10 => 'not a date']);

        $result = $this->locate($transport, 1, 50000, self::Start + 30000 * 60);

        $this->assertNotSame(10, $result);
        $this->assertGreaterThan(1, $result);
    }

    public function testDateBeforeFirstArticleStartsAtFirstArticle()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);

        $this->assertSame(1, $this->locate($transport, 1, 100000, self::Start - 365 * 86400));
    }

    public function testNothingNewerStartsAtTheEnd()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);

        $this->assertSame(100000, $this->locate($transport, 1, 100000, time()));
    }

    public function testNoRetrieveDateStartsAtFirstArticleWithoutScanning()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);

        $this->assertSame(1, $this->locate($transport, 1, 100000, 0));
        $this->assertSame(0, $transport->calls);
    }

    public function testProgressIsReported()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);
        $reported = [];
        $locator = new Services_Retriever_StartArticleLocator($transport, function ($cur, $last) use (&$reported) {
            $reported[] = [$cur, $last];
        });

        $locator->locate(1, 100000, time());

        $this->assertSame([1, 100000], $reported[0]);
    }
}
