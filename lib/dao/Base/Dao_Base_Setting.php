<?php

class Dao_Base_Setting implements Dao_Setting {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Comment object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor

	/* 
	 * Retrieves all settings from the database
	 */
	function getAllSettings() {
		$tmpSettings = array();

		$dbSettings = $this->_conn->arrayQuery('SELECT name,value,serialized FROM settings');
		foreach($dbSettings as $item) {
			if ($item['serialized']) {
				$item['value'] = unserialize($item['value']);
			} # if
			
			$tmpSettings[$item['name']] = $item['value'];
		} # foreach
		
		return $tmpSettings;
	} # getAllSettings

	/*
	 * Removes a setting from the database
	 */
	function removeSetting($name) {
		$this->_conn->exec("DELETE FROM settings WHERE name = '%s'", Array($name));
	} # removeSetting
	
	/*
	 * Update setting
	 */
	function updateSetting($name, $value) {
		# When necessary, serialize the data
		if ((is_array($value) || is_object($value))) {
			$value = serialize($value);
			$serialized = true;
		} else {
			$serialized = false;
		} # if
		
		$this->_conn->exec("UPDATE settings SET value = '%s', serialized = '%s' WHERE name = '%s'", Array($value, $this->_conn->bool2dt($serialized), $name));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO settings(name,value,serialized) VALUES('%s', '%s', '%s')", Array($name, $value, $this->_conn->bool2dt($serialized)));
		} # if
	} # updateSetting

	/*
	 * Returns the current schema version
	 */
	function getSchemaVer() {
		return $this->_conn->singleQuery("SELECT value FROM settings WHERE name = 'schemaversion'");
	} # getSchemaVer

} # Dao_Base_Setting
