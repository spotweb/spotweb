<?php

interface SettingDao {
	public function getAllSettings();
	public function removeSetting($name);
	public function updateSetting($name, $value);
	function getSchemaVer();
} # SettingDao
