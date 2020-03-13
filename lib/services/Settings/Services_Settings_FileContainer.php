<?php

class Services_Settings_FileContainer implements Services_Settings_IContainer
{
    /**
     * Settings originated from PHP's ownsettings.php, settings.php etc.
     *
     * @var array
     */
    private $_phpSettings;

    /**
     * Loads content of datasource.
     *
     * @param array $cfg
     *
     * @return void
     */
    public function initialize(array $cfg)
    {
        $this->_phpSettings = $cfg;
    }

    // initialize

    /**
     * Returns an array with all settings stored in this container.
     *
     * @return array
     */
    public function getAllSettings()
    {
        return $this->_phpSettings;
    }

    // getAllSettings()

    /**
     * Removes a setting from this datasource.
     *
     * @param $name Name of setting to remove
     *
     * @throws InvalidSettingsUpdateException
     *
     * @return void
     */
    public function remove($name)
    {
        /*
         * If setting originates from PHP, throw an exception
         */
        if (isset($this->_phpSettings[$name])) {
            throw new InvalidSettingsUpdateException("InvalidSettingUpdate Exception for '".$name.'"');
        } // if
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
        /*
         * If setting originates from PHP, throw an exception
         */
        if (isset($this->_phpSettings[$name])) {
            throw new InvalidSettingsUpdateException("InvalidSettingUpdate Exception for '".$name.'"');
        } // if
    }

    // set()
} // class Services_Settings_FileContainer
