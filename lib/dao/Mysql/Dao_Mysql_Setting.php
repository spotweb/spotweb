<?php

class Dao_Mysql_Setting extends Dao_Base_Setting {

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
		
		$this->_conn->modify("INSERT INTO settings(name,value,serialized) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE value = '%s', serialized = %s",
							Array($name, $value, $this->_conn->bool2dt($serialized), $value, $this->_conn->bool2dt($serialized)));
	} # updateSetting


} # class Dao_Mysql_Setting


