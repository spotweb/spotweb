<?php

class SpotTiming {
	static private $_disabled = true;
	static private $_timings = array();
	static private $_inflight = array();
	static private $_curlevel = 0;
    static private $_useHtml = true;
	
	static function enable() {
		self::$_disabled = false;
	} # enable
	
	static function disable() {
		self::$_disabled = true;

		/* Clear any outstanding timings */
		self::$_inflight = array();
		self::$_timings = array();	
	} #disable
	
	static function start($name) {
		if (self::$_disabled) return;
		
		self::$_curlevel++;
		self::$_inflight[$name] = array('start' => microtime(true));
	} # start

	static function stop($name, $extra = '') {
		if (self::$_disabled) return;

		self::$_inflight[$name]['stop'] = microtime(true);
		self::$_inflight[$name]['extra'] = $extra;
		self::$_inflight[$name]['level'] = self::$_curlevel;
		self::$_curlevel--;

		self::$_timings[] = array_merge(self::$_inflight[$name], array('name' => $name));
		unset(self::$_inflight[$name]);
	} # stop

    static function enableHtml($b) {
        self::$_useHtml = $b;
    } # setHtml

    private static function doHeader() {
        if (self::$_useHtml) {
            echo '<table style="border: 1px solid black; border-collapse: collapse;" border=1><tr><th>Name</th><th>Time</th><th>Extra</th></tr>';
        } else {
            echo "+" . str_repeat('-', 137) . '+' . PHP_EOL;
            echo "|" . str_pad('Name', 50) . ' | ' . str_pad('Time', 20) . ' | ' . str_pad('Extra', 61) . '|' . PHP_EOL;
            echo "|" . str_repeat('-', 137) . '|' . PHP_EOL;
        } # else
    } # doHeader

    private static function doFooter() {
        if (self::$_useHtml) {
            echo '</table><br><br><br><br>';
        } else {
            echo "+" . str_repeat('-', 137) . '+' . PHP_EOL;
        } # else
    } # doFooter

    private static function makeLen($s,$l) {
        if (strlen($s) < $l) {
            $s = str_pad($s, $l);
        } else {
            $s = substr($s, 0, $l - 3) . '...';
        } # else

        return $s;
    } # makeLen

    private static function displayLine($values) {
        if (self::$_useHtml) {
            try {
                echo '<tr><td>' . str_pad('', $values['level'], '.') . $values['name'] . '</td><td>' . ($values['stop'] - $values['start']) . '</td><td>' . serialize($values['extra']) . '</td></tr>' . PHP_EOL;
            } catch(Exception $x) {
                echo '<tr><td>' . str_pad('', $values['level'], '.') . $values['name'] . '</td><td>' . ($values['stop'] - $values['start']) . '</td><td> [Unserializable data]</td></tr>' . PHP_EOL;
            } # catch
        } else {
            echo "|" . self::makeLen($values['name'], 50) . ' | ' . self::makeLen(($values['stop'] - $values['start']), 20) . ' | ' . self::makeLen(serialize($values['extra']), 61) . '|' . PHP_EOL;
        } # else
    } # displayLine

    static function displayCumul() {
        if (self::$_disabled) return;

        $processed = array();

        self::doHeader();
        foreach(array_reverse(self::$_timings) as $values) {
            if (!isset($processed[$values['name']])) {
                /*
                 * Create a sum of all actions with the same name
                */
                $totalTime = 0;
                $callCount = 0;
                foreach(self::$_timings as $tmp) {
                    if ($tmp['name'] == $values['name']) {
                        $totalTime += ($values['stop'] - $values['start']);
                        $callCount++;
                    } # if
                } # foreach

                $values['start'] = 0;
                $values['stop'] = $totalTime;
                $values['extra'] = 'callcount: ' . $callCount;

                self::displayLine($values);
            }  # if

            $processed[$values['name']] = true;
        } # foreach

        self::doFooter();
    } # displayCumul

	static function display() {
		if (self::$_disabled) return;

        self::doHeader();
		foreach(array_reverse(self::$_timings) as $values) {
            self::displayLine($values);
		} # foreach
        self::doFooter();
	} # display()
} # class SpotTiming
