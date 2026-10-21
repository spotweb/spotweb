<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_StartArticleLocator.php';

/*
 * Fake group where article $n is posted at $start + $n * $interval seconds,
 * except for the article numbers listed as missing
 */
class ServicesRetrieverStartArticleLocatorTransport
{
    public $calls = 0;
    private $_start;
    private $_interval;
    private $_last;
    private $_missing;

    public function __construct($start, $interval, $last, $missing = null)
    {
        $this->_start = $start;
        $this->_interval = $interval;
        $this->_last = $last;
        $this->_missing = $missing;
    }

    public function stampOf($articleNr)
    {
        return $this->_start + $articleNr * $this->_interval;
    }

    public function getOverview($first, $last)
    {
        $this->calls++;
        $hdrList = [];

        for ($nr = $first; $nr <= min($last, $this->_last); $nr++) {
            if (($this->_missing !== null) && call_user_func($this->_missing, $nr)) {
                continue;
            } // if

            $hdrList[] = ['Number' => $nr, 'Date' => date('r', $this->stampOf($nr))];
        } // for

        return $hdrList;
    }
}

class ServicesRetrieverStartArticleLocatorTest extends TestCase
{
    const Start = 1262304000; // 2010-01-01

    public function testFindsArticleJustBeforeTheRequestedDate()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 5000000);
        $locator = new Services_Retriever_StartArticleLocator($transport);

        $target = 3000000;
        $result = $locator->locate(1, 5000000, $transport->stampOf($target));

        // never later than the requested date, and at most the safety margin (plus one probe) earlier
        $this->assertLessThanOrEqual($target, $result);
        $marginArticles = Services_Retriever_StartArticleLocator::SafetyMargin / 60;
        $this->assertGreaterThanOrEqual($target - $marginArticles - Services_Retriever_StartArticleLocator::ProbeSize, $result);

        // a binary search, not a walk through the group
        $this->assertLessThan(30, $transport->calls);
    }

    public function testDateBeforeFirstArticleStartsAtFirstArticle()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);
        $locator = new Services_Retriever_StartArticleLocator($transport);

        $this->assertSame(1, $locator->locate(1, 100000, self::Start - 365 * 86400));
    }

    public function testDateAfterLastArticleStartsNearTheEnd()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 100000);
        $locator = new Services_Retriever_StartArticleLocator($transport);

        $result = $locator->locate(1, 100000, time());

        $this->assertGreaterThan(100000 - 2 * Services_Retriever_StartArticleLocator::ProbeSize, $result);
        $this->assertLessThanOrEqual(100000, $result);
    }

    public function testGapsNeverMakeUsStartTooLate()
    {
        // large holes in the group, eg: expired or cancelled articles
        $missing = function ($nr) {
            return ($nr % 10000) < 7000;
        };
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 1000000, $missing);
        $locator = new Services_Retriever_StartArticleLocator($transport);

        foreach ([7000, 123456, 500000, 987654] as $target) {
            $result = $locator->locate(1, 1000000, $transport->stampOf($target));

            $this->assertLessThanOrEqual($target, $result, 'target '.$target);
        } // foreach
    }

    public function testUndatedHeadersAreIgnored()
    {
        $transport = $this->getMockBuilder('stdClass')->addMethods(['getOverview'])->getMock();
        $transport->method('getOverview')->willReturnCallback(function ($first, $last) {
            return [['Number' => $first, 'Date' => 'not a date']];
        });
        $locator = new Services_Retriever_StartArticleLocator($transport);

        $this->assertSame(1, $locator->locate(1, 100000, time()));
    }

    public function testSmallGroupIsNotSearched()
    {
        $transport = new ServicesRetrieverStartArticleLocatorTransport(self::Start, 60, 40);
        $locator = new Services_Retriever_StartArticleLocator($transport);

        $this->assertSame(1, $locator->locate(1, 40, time()));
        $this->assertSame(0, $transport->calls);
    }
}
