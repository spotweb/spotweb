<?php

class SpotCommandline {
	private static $_parsed = null;
	private static $_defaults = null;

	/*
	 * $noopt is the list of parameters without value
	 * $defaults is the list of default values for parameters
	 *
	 */
	static public function initialize($noopt, $defaults) {
		self::$_parsed = self::parseParameters($noopt);
		self::$_defaults = $defaults;
	} # initialize
	
	/*
	 * Are we running from the commandline?
	 */
	static public function isCommandline() {
		return (!isset($_SERVER['SERVER_PROTOCOL']));
	} # isCommandline
	
	/*
	 * Returns either the value of the function or '1' 
	 * when set
	 *
	 * When the parameter is not given, we return the
	 * default value
	 */
	static public function get($val) {
		if (isset(self::$_parsed[$val])) {
			return self::$_parsed[$val];
		} else {
			return self::$_defaults[$val];
		} # else
	} # get
	
   /**
     * 
	 * This code is copied from:
	 *		http://nl.php.net/manual/en/function.getopt.php#83414
	 *
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
     * @return array of parameters with their values if valid
     */
    static private function parseParameters($noopt = array()) {
        $result = array();

        if (!isset($GLOBALS['argv'])) {
            return $result;
        } # if
        
        $params = $GLOBALS['argv'];
		
        // could use getopt() here (since PHP 5.3.0), but it doesn't work reliably
        reset($params);
        foreach ($params as $p) {
            if ($p{0} == '-') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname{0} == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                // check if next parameter is a descriptor or a value
                $nextparm = current($params);
                if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
                $result[$pname] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }
        return $result;
    } # parseParameters
	
} # SpotCommandline
