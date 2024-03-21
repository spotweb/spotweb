<?php
  declare(strict_types=1);

  use PHPUnit\Framework\TestCase;
  use function PHP81_BC\strftime;

  class strftimeTest extends TestCase {
    public function setUp () : void {
      setlocale(LC_TIME, 'en');
      date_default_timezone_set('Europe/Madrid');
    }

    public function testTimestamp () {
      $result = strftime('%Y-%m-%d %H:%M:%S', strtotime('20220306 01:02:03'));
      $this->assertEquals('2022-03-06 01:02:03', $result, 'int $timestamp test fail');

      $result = strftime('%Y-%m-%d %H:%M:%S', '20220306 01:02:03');
      $this->assertEquals('2022-03-06 01:02:03', $result, 'string $timestamp test fail');

      $result = strftime('%Y-%m-%d %H:%M:%S', new DateTime('20220306 01:02:03'));
      $this->assertEquals('2022-03-06 01:02:03', $result, 'DateTime $timestamp test fail');
    }

    public function testException () {
      $this->expectException(InvalidArgumentException::class);
      $result = strftime('%Y-%m-%d %H:%M:%S', 'InvalidArgumentException');

      $this->expectException(InvalidArgumentException::class);
      $result = strftime('%ñ', '20220306 13:02:03');
    }

    public function testDayFormats () {
      $result = strftime('%a', '20220306 13:02:03');
      $this->assertEquals('Sun', $result, '%a: An abbreviated textual representation of the day');

      $result = strftime('%A', '20220306 13:02:03');
      $this->assertEquals('Sunday', $result, '%A: A full textual representation of the day');

      $result = strftime('%d', '20220306 13:02:03');
      $this->assertEquals('06', $result, '%d: Two-digit day of the month (with leading zeros)');

      $result = strftime('%e', '20220306 13:02:03');
      $this->assertEquals(' 6', $result, '%e: Day of the month, with a space preceding single digits');

      $result = strftime('%j', '20220306 13:02:03');
      $this->assertEquals('065', $result, '%j: Day of the year, 3 digits with leading zeros');

      $result = strftime('%u', '20220306 13:02:03');
      $this->assertEquals('7', $result, '%u: ISO-8601 numeric representation of the day of the week');

      $result = strftime('%w', '20220306 13:02:03');
      $this->assertEquals('0', $result, '%w: Numeric representation of the day of the week');
    }

    public function testWeekFormats () {
      $result = strftime('%U', '20220306 13:02:03');
      $this->assertEquals('10', $result, '%U: Week number of the given year, starting with the first Sunday as the first week');

      $result = strftime('%V', '20220306 13:02:03');
      $this->assertEquals('09', $result, '%V: ISO-8601:1988 week number of the given year, starting withthe first week of the year with at least 4 weekdays, with Monday being the start of the week');

      $result = strftime('%W', '20220306 13:02:03');
      $this->assertEquals('09', $result, '%W: A numeric representation of the week of the year, starting with the first Monday as the first week');
    }

    public function testMonthFormats () {
      $result = strftime('%b', '20220306 13:02:03');
      $this->assertEquals('Mar', $result, '%b: Abbreviated month name, based on the locale');

      $result = strftime('%B', '20220306 13:02:03');
      $this->assertEquals('March', $result, '%B: Full month name, based on the locale');

      $result = strftime('%h', '20220306 13:02:03');
      $this->assertEquals('Mar', $result, '%h: Abbreviated month name, based on the locale (an alias of %b)');

      $result = strftime('%m', '20220306 13:02:03');
      $this->assertEquals('03', $result, '%m: Two digit representation of the month');
    }

    public function testYearFormats () {
      $result = strftime('%C', '20220306 13:02:03');
      $this->assertEquals('20', $result, '%C: Two digit representation of the century (year divided by 100, truncated to an integer)');

      $result = strftime('%g', '20220306 13:02:03');
      $this->assertEquals('22', $result, '%g: Two digit representation of the year going by ISO-8601:1988 standards (see %V)');

      $result = strftime('%G', '20220306 13:02:03');
      $this->assertEquals('2022', $result, '%G: The full four-digit version of %g');

      $result = strftime('%y', '20220306 13:02:03');
      $this->assertEquals('22', $result, '%y: Two digit representation of the year');

      $result = strftime('%Y', '20220306 13:02:03');
      $this->assertEquals('2022', $result, '%Y: Four digit representation for the year');
    }

    public function testTimeFormats () {
      $result = strftime('%H', '20220306 13:02:03');
      $this->assertEquals('13', $result, '%H: Two digit representation of the hour in 24-hour format');

      $result = strftime('%k', '20220306 01:02:03');
      $this->assertEquals(' 1', $result, '%k: Hour in 24-hour format, with a space preceding single digits');

      $result = strftime('%I', '20220306 13:02:03');
      $this->assertEquals('01', $result, '%I: Two digit representation of the hour in 12-hour format');

      $result = strftime('%l', '20220306 13:02:03');
      $this->assertEquals(' 1', $result, '%l: (lower-case "L") Hour in 12-hour format, with a space preceding single digits');

      $result = strftime('%M', '20220306 13:02:03');
      $this->assertEquals('02', $result, '%M: Two digit representation of the minute');

      $result = strftime('%p', '20220306 13:02:03');
      $this->assertEquals('PM', $result, '%p: UPPER-CASE "AM" or "PM" based on the given time');

      $result = strftime('%P', '20220306 13:02:03');
      $this->assertEquals('pm', $result, '%P: lower-case "am" or "pm" based on the given time');

      $result = strftime('%r', '20220306 13:02:03');
      $this->assertEquals('01:02:03 PM', $result, '%r: Same as "%I:%M:%S %p"');

      $result = strftime('%R', '20220306 13:02:03');
      $this->assertEquals('13:02', $result, '%R: Same as "%H:%M"');

      $result = strftime('%S', '20220306 13:02:03');
      $this->assertEquals('03', $result, '%S: Two digit representation of the second');

      $result = strftime('%T', '20220306 13:02:03');
      $this->assertEquals('13:02:03', $result, '%T: Same as "%H:%M:%S"');

      $result = strftime('%X', '20220306 13:02:03');
      $this->assertEquals('1:02:03 PM', $result, '%X: Preferred time representation based on locale, without the date');

      $result = strftime('%z', '20220306 13:02:03');
      $this->assertEquals('+0100', $result, '%z: The time zone offset');

      $result = strftime('%Z', '20220306 13:02:03');
      $this->assertEquals('CET', $result, '%Z: The time zone abbreviation');
    }

    public function testStampsFormats () {
      $result = strftime('%c', '20220306 13:02:03');
      $this->assertEquals('March 6, 2022 at 1:02 PM', $result, '%c: Preferred date and time stamp based on locale');

      $result = strftime('%D', '20220306 13:02:03');
      $this->assertEquals('03/06/2022', $result, '%D: Same as "%m/%d/%y"');

      $result = strftime('%F', '20220306 13:02:03');
      $this->assertEquals('2022-03-06', $result, '%F: Same as "%Y-%m-%d" (commonly used in database datestamps)');

      $result = strftime('%s', '20220306 13:02:03');
      $this->assertEquals('1646568123', $result, '%s: Unix Epoch Time timestamp (same as the time() function)');

      $result = strftime('%x', '20220306 13:02:03');
      $this->assertEquals('3/6/22', $result, '%x: Preferred date representation based on locale, without the time');
    }

    public function testMiscellaneousFormats () {
      $result = strftime('%n', '20220306 13:02:03');
      $this->assertEquals("\n", $result, '%n: A newline character ("\n")');

      $result = strftime('%t', '20220306 13:02:03');
      $this->assertEquals("\t", $result, '%t: A Tab character ("\t")');

      $result = strftime('%%', '20220306 13:02:03');
      $this->assertEquals('%', $result, '%%: A literal percentage character ("%")');
    }

    public function testLocale () {
      $result = strftime('%c', '20220306 13:02:03', 'eu');
      $this->assertEquals('2022(e)ko martxoaren 6(a) 13:02', $result, '%x: Preferred date representation based on locale, without the time');

      $result = strftime('%b', '20220306 13:02:03', 'eu');
      $this->assertEquals('mar.', $result, '%b: Abbreviated month name, based on the locale');

      $result = strftime('%B', '20220306 13:02:03', 'eu');
      $this->assertEquals('martxoa', $result, '%B: Full month name, based on the locale');
    }

    /**
     * In October 1582, the Gregorian calendar replaced the Julian in much of Europe, and
     *   the 4th October was followed by the 15th October.
     *  ICU (including IntlDateFormattter) interprets and formats dates based on this cutover.
     *  Posix (including strftime) and timelib (including DateTimeImmutable) instead use
     *   a "proleptic Gregorian calendar" - they pretend the Gregorian calendar has existed forever.
     *  This leads to the same instants in time, as expressed in Unix time, having different representations
     *   in formatted strings.
     */
    public function testJulianCutover () {
      // 1st October 1582 in proleptic Gregorian is the same date as 21st September 1582 Julian
      $prolepticTimestamp = DateTimeImmutable::createFromFormat('Y-m-d|', '1582-10-01')->getTimestamp();
      $result = strftime('%x', $prolepticTimestamp, 'eu');
      $this->assertEquals('82/10/1', $result, '1st October 1582 in proleptic Gregorian is the same date as 21st September 1582 Julian');

      // In much of Europe, the 10th October 1582 never existed
      $prolepticTimestamp = DateTimeImmutable::createFromFormat('Y-m-d|', '1582-10-10')->getTimestamp();
      $result = strftime('%x', $prolepticTimestamp, 'eu');
      $this->assertEquals('82/10/10', $result, 'In much of Europe, the 10th October 1582 never existed');

      // The 15th October was the first day after the cutover, after which both systems agree
      $prolepticTimestamp = DateTimeImmutable::createFromFormat('Y-m-d|', '1582-10-15')->getTimestamp();
      $result = strftime('%x', $prolepticTimestamp, 'eu');
      $this->assertEquals('82/10/15', $result, 'The 15th October was the first day after the cutover, after which both systems agree');
    }
  }
