<?php

class SpotTiming
{
    private static $_disabled = true;
    private static $_timings = [];
    private static $_inflight = [];
    private static $_curlevel = 0;
    private static $_useHtml = true;
    private static $_discardExtra = false;

    public static function enable()
    {
        self::$_disabled = false;
    }

    // enable

    public static function disable()
    {
        self::$_disabled = true;

        /* Clear any outstanding timings */
        self::clear();
    }

    //disable

    public static function isEnabled()
    {
        return !self::$_disabled;
    }

    // isEnabled

    public static function clear()
    {
        self::$_inflight = [];
        self::$_timings = [];
    }

    // clear

    public static function start($name)
    {
        if (self::$_disabled) {
            return;
        }

        self::$_curlevel++;
        self::$_inflight[$name] = ['start' => microtime(true)];
    }

    // start

    public static function stop($name, $extra = '')
    {
        if (self::$_disabled) {
            return;
        }

        self::$_inflight[$name]['stop'] = microtime(true);
        if (!self::$_discardExtra) {
            self::$_inflight[$name]['extra'] = $extra;
        } else {
            self::$_inflight[$name]['extra'] = '';
        } // else
        self::$_inflight[$name]['level'] = self::$_curlevel;
        self::$_curlevel--;

        self::$_timings[] = array_merge(self::$_inflight[$name], ['name' => $name]);
        unset(self::$_inflight[$name]);
    }

    // stop

    public static function enableHtml($b)
    {
        self::$_useHtml = $b;
    }

    // setHtml

    public static function disableExtra($b)
    {
        self::$_discardExtra = $b;
    }

    // disableExtra

    private static function doHeader()
    {
        if (self::$_useHtml) {
            echo '<table style="border: 1px solid black; border-collapse: collapse;" border=1><tr><th>Name</th><th>Time</th><th>Extra</th></tr>';
        } else {
            echo '+'.str_repeat('-', 157).'+'.PHP_EOL;
            echo '|'.str_pad('Name', 70).' | '.str_pad('Time', 20).' | '.str_pad('Extra', 61).'|'.PHP_EOL;
            echo '|'.str_repeat('-', 157).'|'.PHP_EOL;
        } // else
    }

    // doHeader

    private static function doFooter()
    {
        if (self::$_useHtml) {
            echo '</table><br><br><br><br>';
        } else {
            echo '+'.str_repeat('-', 157).'+'.PHP_EOL;
        } // else
    }

    // doFooter

    private static function makeLen($s, $l)
    {
        if (strlen($s) < $l) {
            $s = str_pad($s, $l);
        } else {
            $s = substr($s, 0, $l - 3).'...';
        } // else

        return $s;
    }

    // makeLen

    private static function displayLine($values)
    {
        if (isset($values['start'])) {
            if (self::$_useHtml) {
                try {
                    echo '<tr><td>'.str_pad('', $values['level'], '.').$values['name'].'</td><td>'.($values['stop'] - $values['start']).'</td><td>'.serialize($values['extra']).'</td></tr>'.PHP_EOL;
                } catch (Exception $x) {
                    echo '<tr><td>'.str_pad('', $values['level'], '.').$values['name'].'</td><td>'.($values['stop'] - $values['start']).'</td><td> [Unserializable data]</td></tr>'.PHP_EOL;
                } // catch
            } else {
                echo '|'.self::makeLen(str_pad('', $values['level'], '.').$values['name'], 70).' | '.self::makeLen(($values['stop'] - $values['start']), 20).' | '.self::makeLen(serialize($values['extra']), 61).'|'.PHP_EOL;
            } // else
        }
    }

    // displayLine

    public static function displayCumul()
    {
        if (self::$_disabled) {
            return;
        }

        $processed = [];

        self::doHeader();
        foreach (array_reverse(self::$_timings) as $values) {
            if (!isset($processed[$values['level'].$values['name']])) {
                /*
                 * Create a sum of all actions with the same name
                */
                $totalTime = 0;
                $callCount = 0;
                foreach (self::$_timings as $tmp) {
                    if (($tmp['name'] == $values['name']) && ($tmp['level'] == $values['level'])) {
                        $totalTime += ($tmp['stop'] - $tmp['start']);
                        $callCount++;
                    } // if
                } // foreach

                $values['start'] = 0;
                $values['stop'] = $totalTime;
                $values['extra'] = 'callcount: '.$callCount;

                self::displayLine($values);
            }  // if

            $processed[$values['level'].$values['name']] = true;
        } // foreach

        self::doFooter();
    }

    // displayCumul

    public static function display()
    {
        if (self::$_disabled) {
            return;
        }

        self::doHeader();
        foreach (array_reverse(self::$_timings) as $values) {
            self::displayLine($values);
        } // foreach
        self::doFooter();
    }

    // display()
} // class SpotTiming
