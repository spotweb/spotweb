<?php

interface Services_Settings_IContainer {
    /**
     * Loads content of datasource
     *
     * @param array $cfg
     * @return void
     */
    public function initialize(array $cfg);

    /**
     * Returns an array with all settings stored in this container
     * @return array
     */
    public function getAllSettings();

    /**
     * Removes a setting from this datasource
     *
     * @param $name Name of setting to remove
     * @return void
     * @throws InvalidSettingsUpdateException
     */
    public function remove($name);

    /**
     * Updates a setting. If the source provider cannot update
     * this setting, and it should be (eg: its a read only source),
     * it should throw InvalidSettingsUpdateException. If the setting
     * is not stored in this source, just ignore it.
     *
     * @param $name
     * @param $value
     * @return void
     * @throws InvalidSettingsUpdateException
     */
    public function set($name, $value);

} # interface Services_Settings_IContainer