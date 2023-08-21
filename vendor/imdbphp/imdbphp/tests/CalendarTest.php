<?php

use Imdb\Calendar;

class CalendarTest extends PHPUnit\Framework\TestCase
{
    public function test_getUpcomingReleases()
    {
        $cal = $this->getCalendar();
        $calendar = $cal->upcomingReleases("GB");

        $this->assertIsArray($calendar);
        $this->assertTrue(count($calendar) >= 1);

        foreach ($calendar as $calendarItem) {
            $this->assertIsArray($calendarItem);
            $this->assertInstanceOf('DateTime', $calendarItem['release_date']);
            $this->assertNotEmpty($calendarItem['title']);
            $this->assertIsNumeric($calendarItem['year']);
            $this->assertIsNumeric($calendarItem['imdbid']);
        }
    }

    protected function getCalendar()
    {
        $config = new \Imdb\Config();
        $config->language = 'en-GB';
        $config->imdbsite = 'www.imdb.com';
        $config->cachedir = realpath(dirname(__FILE__) . '/cache') . '/';
        $config->usezip = false;
        $config->cache_expire = 3600;

        return new Calendar($config);
    }
}
