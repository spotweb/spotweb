<?php
  namespace PHP81_BC;

  use DateTime;
  use DateTimeZone;
  use DateTimeInterface;
  use Exception;
  use IntlDateFormatter;
  use IntlGregorianCalendar;
  use InvalidArgumentException;

  /**
   * Locale-formatted strftime using IntlDateFormatter (PHP 8.1 compatible)
   * This provides a cross-platform alternative to strftime() for when it will be removed from PHP.
   * Note that output can be slightly different between libc sprintf and this function as it is using ICU.
   *
   * Usage:
   * use function \PHP81_BC\strftime;
   * echo strftime('%A %e %B %Y %X', new \DateTime('2021-09-28 00:00:00'), 'fr_FR');
   *
   * Original use:
   * \setlocale(LC_TIME, 'fr_FR.UTF-8');
   * echo \strftime('%A %e %B %Y %X', strtotime('2021-09-28 00:00:00'));
   *
   * @param  string $format Date format
   * @param  integer|string|DateTime $timestamp Timestamp
   * @return string
   * @author BohwaZ <https://bohwaz.net/>
   */
  function strftime (string $format, $timestamp = null, ?string $locale = null) : string {
    if (!($timestamp instanceof DateTimeInterface)) {
      $timestamp = is_int($timestamp) ? '@' . $timestamp : (string) $timestamp;

      try {
        $timestamp = new DateTime($timestamp);
      } catch (Exception $e) {
        throw new InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.', 0, $e);
      }
    }

    $timestamp->setTimezone(new DateTimeZone(date_default_timezone_get()));

    if (empty($locale)) {
      // get current locale
      $locale = setlocale(LC_TIME, '0');
    }
    // remove trailing part not supported by ext-intl locale
    $locale = preg_replace('/[^\w-].*$/', '', $locale);

    $intl_formats = [
      '%a' => 'EEE',	// An abbreviated textual representation of the day	Sun through Sat
      '%A' => 'EEEE',	// A full textual representation of the day	Sunday through Saturday
      '%b' => 'MMM',	// Abbreviated month name, based on the locale	Jan through Dec
      '%B' => 'MMMM',	// Full month name, based on the locale	January through December
      '%h' => 'MMM',	// Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
    ];

    $intl_formatter = function (DateTimeInterface $timestamp, string $format) use ($intl_formats, $locale) {
      $tz = $timestamp->getTimezone();
      $date_type = IntlDateFormatter::FULL;
      $time_type = IntlDateFormatter::FULL;
      $pattern = '';

      switch ($format) {
        // %c = Preferred date and time stamp based on locale
        // Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
        case '%c':
          $date_type = IntlDateFormatter::LONG;
          $time_type = IntlDateFormatter::SHORT;
          break;

        // %x = Preferred date representation based on locale, without the time
        // Example: 02/05/09 for February 5, 2009
        case '%x':
          $date_type = IntlDateFormatter::SHORT;
          $time_type = IntlDateFormatter::NONE;
          break;

        // Localized time format
        case '%X':
          $date_type = IntlDateFormatter::NONE;
          $time_type = IntlDateFormatter::MEDIUM;
          break;

        default:
          $pattern = $intl_formats[$format];
      }

      // In October 1582, the Gregorian calendar replaced the Julian in much of Europe, and
      //  the 4th October was followed by the 15th October.
      // ICU (including IntlDateFormattter) interprets and formats dates based on this cutover.
      // Posix (including strftime) and timelib (including DateTimeImmutable) instead use
      //  a "proleptic Gregorian calendar" - they pretend the Gregorian calendar has existed forever.
      // This leads to the same instants in time, as expressed in Unix time, having different representations
      //  in formatted strings.
      // To adjust for this, a custom calendar can be supplied with a cutover date arbitrarily far in the past.
      $calendar = IntlGregorianCalendar::createInstance();
      $calendar->setGregorianChange(PHP_INT_MIN);

      return (new IntlDateFormatter($locale, $date_type, $time_type, $tz, $calendar, $pattern))->format($timestamp);
    };

    // Same order as https://www.php.net/manual/en/function.strftime.php
    $translation_table = [
      // Day
      '%a' => $intl_formatter,
      '%A' => $intl_formatter,
      '%d' => 'd',
      '%e' => function ($timestamp) {
        return sprintf('% 2u', $timestamp->format('j'));
      },
      '%j' => function ($timestamp) {
        // Day number in year, 001 to 366
        return sprintf('%03d', $timestamp->format('z')+1);
      },
      '%u' => 'N',
      '%w' => 'w',

      // Week
      '%U' => function ($timestamp) {
        // Number of weeks between date and first Sunday of year
        $day = new DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
        return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
      },
      '%V' => 'W',
      '%W' => function ($timestamp) {
        // Number of weeks between date and first Monday of year
        $day = new DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
        return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
      },

      // Month
      '%b' => $intl_formatter,
      '%B' => $intl_formatter,
      '%h' => $intl_formatter,
      '%m' => 'm',

      // Year
      '%C' => function ($timestamp) {
        // Century (-1): 19 for 20th century
        return floor($timestamp->format('Y') / 100);
      },
      '%g' => function ($timestamp) {
        return substr($timestamp->format('o'), -2);
      },
      '%G' => 'o',
      '%y' => 'y',
      '%Y' => 'Y',

      // Time
      '%H' => 'H',
      '%k' => function ($timestamp) {
        return sprintf('% 2u', $timestamp->format('G'));
      },
      '%I' => 'h',
      '%l' => function ($timestamp) {
        return sprintf('% 2u', $timestamp->format('g'));
      },
      '%M' => 'i',
      '%p' => 'A', // AM PM (this is reversed on purpose!)
      '%P' => 'a', // am pm
      '%r' => 'h:i:s A', // %I:%M:%S %p
      '%R' => 'H:i', // %H:%M
      '%S' => 's',
      '%T' => 'H:i:s', // %H:%M:%S
      '%X' => $intl_formatter, // Preferred time representation based on locale, without the date

      // Timezone
      '%z' => 'O',
      '%Z' => 'T',

      // Time and Date Stamps
      '%c' => $intl_formatter,
      '%D' => 'm/d/Y',
      '%F' => 'Y-m-d',
      '%s' => 'U',
      '%x' => $intl_formatter,
    ];

    $out = preg_replace_callback('/(?<!%)%([_#-]?)([a-zA-Z])/', function ($match) use ($translation_table, $timestamp) {
      $prefix = $match[1];
      $char = $match[2];
      $pattern = '%'.$char;
      if ($pattern == '%n') {
        return "\n";
      }
      elseif ($pattern == '%t') {
        return "\t";
      }

      if (!isset($translation_table[$pattern])) {
        throw new InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $pattern));
      }

      $replace = $translation_table[$pattern];

      if (is_string($replace)) {
        $result = $timestamp->format($replace);
      }
      else {
        $result = $replace($timestamp, $pattern);
      }

      switch ($prefix) {
        case '_':
          // replace leading zeros with spaces but keep last char if also zero
          return preg_replace('/\G0(?=.)/', ' ', $result);
        case '#':
        case '-':
          // remove leading zeros but keep last char if also zero
          return preg_replace('/^0+(?=.)/', '', $result);
      }

      return $result;
    }, $format);

    $out = str_replace('%%', '%', $out);
    return $out;
  }
