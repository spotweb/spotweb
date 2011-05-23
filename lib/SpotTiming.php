<?php

class SpotTiming {
	static private $_disabled = true;
	static private $_timings = array();
	static private $_inflight = array();
	static private $_curlevel = 0;
	
	static function enable() {
		self::$_disabled = false;
	} # enable
	
	static function disable() {
		self::$_disabled = true;
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
	
	static function display() {
		if (self::$_disabled) return;
		
		echo '<table style="border: 1px solid black; border-collapse: collapse;" border=1><tr><th>Name</th><th>Time</th><th>Extra</th></tr>';
		
		foreach(array_reverse(self::$_timings) as $values) {
			echo '<tr><td>' . str_pad('', $values['level'], '.') . $values['name'] . '</td><td>' . ($values['stop'] - $values['start']) . '</td><td>' . serialize($values['extra']) . '</td></tr>' . PHP_EOL;
		} # foreach
	
		echo '</table><br><br><br><br>';
	} # display()
} # class SpotTiming