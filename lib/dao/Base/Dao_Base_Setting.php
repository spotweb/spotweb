<?php

interface Dao_Setting {
	public function getAllSettings();
	public function removeSetting($name);
	public function updateSetting($name, $value);
	function getSchemaVer();
} # Dao_Setting
