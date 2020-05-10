<?php

class Services_Settings_Container
{
    /**
     * @var null Services_Settings_Container
     */
    private static $_instance = null;

    /**
     * Merged array with all settings (both db, and php).
     *
     * @var array
     */
    private static $_settings = [];

    /**
     * List of container implementations.
     *
     * @var array[Services_Settings_IContainer]
     */
    private static $_sources;

    /**
     * Services_Settings_Container is a singleton class, this function instantiates Services_Settings_Container.
     */
    public static function singleton()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        } // if

        return self::$_instance;
    }

    // singleton

    /**
     * Add a source of settings. For all these observers, we notify
     * when to update.
     *
     * @param Services_Settings_IContainer $source
     */
    public function addSource(Services_Settings_IContainer $source)
    {
        self::$_sources[] = $source;
        self::$_settings = array_merge(self::$_settings, $source->getAllSettings());

        /*
         * When no specific NNTP header / comments server is entered, we override these with the NZB server
         * header. This allows us to always assume those are entered by the user.
         */
        if ((empty(self::$_settings['nntp_hdr']['host'])) && (!empty(self::$_settings['nntp_nzb']))) {
            self::$_settings['nntp_hdr'] = self::$_settings['nntp_nzb'];
        } // if

        // Same for the NNTP upload server
        if ((empty(self::$_settings['nntp_post']['host'])) && (!empty(self::$_settings['nntp_nzb']))) {
            self::$_settings['nntp_post'] = self::$_settings['nntp_nzb'];
        } // if
    }

    // addSource

    /**
     * Returns the value of a setting.
     */
    public function get($name)
    {
        return self::$_settings[$name];
    }

    // get

    /**
     * Removes a certain setting from our settings, and
     * notify our observers.
     */
    public function remove($name)
    {
        unset(self::$_settings[$name]);

        foreach (self::$_sources as $src) {
            $src->remove($name);
        } // foreach
    }

    // remove

    /**
     * Updates a setting. It will throw an exception if the
     * setting is set from within PHP to ensure we don't create
     * an setting which seems to revert magically.
     *
     * Otherwise directly persists the setting, so be careful
     */
    public function set($name, $value)
    {
        // Make sure we update our own settings system
        self::$_settings[$name] = $value;

        foreach (self::$_sources as $src) {
            $src->set($name, $value);
        } // foreach
    }

    // set

    /**
     * Does the setting actually exist?
     */
    public function exists($name)
    {
        return isset(self::$_settings[$name]);
    }

    // isSet

    /**
     * Returns a list of all settings in an array.
     */
    public function getAllSettings()
    {
        return self::$_settings;
    }

    // getAllSettings
} // class Services_Settings_Container
