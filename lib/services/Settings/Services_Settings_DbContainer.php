<?php

class Services_Settings_DbContainer implements Services_Settings_IContainer
{
    /**
     * List of settings in this container.
     *
     * @var array
     */
    private $_settings;

    /**
     * @var Dao_Settings
     */
    private $_settingsDao;

    /**
     * Loads content of datasource.
     *
     * @param array $cfg
     *
     * @return void
     */
    public function initialize(array $cfg)
    {
        $this->_settingsDao = $cfg['dao'];

        // Retrieve all settings and prepare those
        $this->_settings = $this->_settingsDao->getAllSettings();
    }

    // initialize()

    /**
     * Returns an array with all settings stored in this container.
     *
     * @return array
     */
    public function getAllSettings()
    {
        return $this->_settings;
    }

    // getAllSettings()

    /**
     * Removes a setting from this datasource.
     *
     * @param $name Name of setting to remove
     *
     * @return void
     */
    public function remove($name)
    {
        $this->_settingsDao->removeSetting($name);
    }

    // remove()

    /**
     * Updates a setting. If the source provider cannot update
     * this setting, and it should be (eg: its a read only source),
     * it should throw InvalidSettingsUpdateException. If the setting
     * is not stored in this source, just ignore it.
     *
     * @param $name
     * @param $value
     *
     * @throws InvalidSettingsUpdateException
     *
     * @return void
     */
    public function set($name, $value)
    {
        $this->_settingsDao->updateSetting($name, $value);
    }

    // set()
} // class Services_Settings_DbContainer
