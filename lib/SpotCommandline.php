<?php

class SpotCommandline
{
    private static $_parsed = null;
    private static $_defaults = null;

    /*
     * $noopt is the list of parameters without value
     * $defaults is the list of default values for parameters
     *
     */
    public static function initialize($noopt, $defaults)
    {
        self::$_parsed = self::parseParameters($noopt);
        self::$_defaults = $defaults;
    }

    // initialize

    /*
     * Are we running from the commandline?
     */
    public static function isCommandline()
    {
        return !isset($_SERVER['SERVER_PROTOCOL']);
    }

    // isCommandline

    /*
     * Returns either the value of the function or '1'
     * when set
     *
     * When the parameter is not given, we return the
     * default value
     */
    public static function get($val)
    {
        if (isset(self::$_parsed[$val])) {
            return self::$_parsed[$val];
        } else {
            return self::$_defaults[$val];
        } // else
    }

    // get

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     *
     * Supports:
     * -e
     * -e <value>
     * --long-param
     * --long-param=<value>
     * --long-param <value>
     * <value>
     *
     * @param array $noopt List of parameters without values
     *
     * @return array of parameters with their values if valid
     */
    private static function parseParameters($noopt = [])
    {
        $result = [];
        if (!isset($GLOBALS['argv'])) {
            return $result;
        } // if

        $params = $GLOBALS['argv'];
        $pname = null;
        reset($params);
        $skipnext = false;

        foreach ($params as $tmp => $p) {
            if (!$skipnext) {
                if ($p[0] == '-') {
                    $pname = substr($p, 1);
                    $value = true;
                    if ($pname[0] == '-') {
                        // long-opt (--<param>)
                        $pname = substr($pname, 1);
                        if (strpos($p, '=') !== false) {
                            // value specified inline (--<param>=<value>)
                            list($pname, $value) = explode('=', substr($p, 2), 2);
                        }
                    }
                    // check if next parameter is a descriptor or a value
                    if (isset($params[$tmp + 1])) {
                        $nextparm = $params[$tmp + 1];
                    } else {
                        $nextparm = false;
                    }
                    if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm[0] != '-') {
                        $value = $nextparm;
                        $skipnext = true; // Found value, skip next params
                    }
                    $result[$pname] = $value;
                } else {
                    // param doesn't belong to any option, probably a value
                    $result[] = $p;
                } // $p[0] == '-'
            } else { // skipnext
                $skipnext = false;
            }
        } // foreach

        return $result;
    }

    // parseParameters
} // SpotCommandline
