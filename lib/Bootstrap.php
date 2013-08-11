<?php

/*
 * Define several version constants
 * used throughput Spotweb
 */
define('SPOTWEB_SETTINGS_VERSION', '0.27');
define('SPOTWEB_SECURITY_VERSION', '0.31');
define('SPOTDB_SCHEMA_VERSION', '0.60');
define('SPOTWEB_VERSION', '0.' . (SPOTDB_SCHEMA_VERSION * 100) . '.' . (SPOTWEB_SETTINGS_VERSION * 100) . '.' . (SPOTWEB_SECURITY_VERSION * 100));

/*
 * Define several constants regarding "fixed"
 * userids, used by Spotweb.
 */
define('SPOTWEB_ANONYMOUS_USERID', 1);
define('SPOTWEB_ADMIN_USERID', 2);


/*
 * Spotweb bootstrapping code.
 * 
 */
class Bootstrap {
    private $_dbSettings;

    /**
     * Boot up the Spotweb system
     *
     * @return (Services_Settings_Base|Dao_Factory_Base|SpotReq)[]
     */
	public function boot() {
        SpotTiming::start('bootstrap');
		$daoFactory = $this->getDaoFactory();
		$settings = $this->getSettings($daoFactory);
		$spotReq = $this->getSpotReq($settings);

        /*
         * Set the cache path
         */
        if ($settings->exists('cache_path')) {
            $daoFactory->setCachePath($settings->get('cache_path'));
        } # if

		/*
		 * Run the validation of the most basic systems
		 * in Spotweb
		 */
		$this->validate($settings);

		/*
		 * Disable the timing part as soon as possible because it 
		 * gobbles memory
		 */
		if (!$settings->get('enable_timing')) {
			SpotTiming::disable();
		} # if

        /*
         * Disable XML entity loader as this might be an
         * security issue.
         */
		libxml_disable_entity_loader(true);


        SpotTiming::stop('bootstrap');
		return array($settings, $daoFactory, $spotReq);
	} # boot


	/**
	 * Returns the DAO factory used by all of 
	 * Spotweb
     *
     * @return Dao_Base_Factory
	 */
	public function getDaoFactory() {
        SpotTiming::start(__FUNCTION__);

		@include "dbsettings.inc.php";
        if (empty($dbsettings)) {
                throw new InvalidOwnSettingsSettingException("No database settings have been entered, please use the 'install.php' wizard to install and configure Spotweb." . PHP_EOL .
                                                             "If you are upgrading from an earlier version of Spotweb, please consult https://github.com/spotweb/spotweb/wiki/Frequently-asked-questions/ first");
        } # if

        /*
         * Store the DB settings so we can retrieve them later, if so desired,
         * we do overwrite the password to make sure it doesn't show up in a
         * stacktrace.
         */
        $this->_dbSettings = $dbsettings;
        $this->_dbSettings['pass'] = '**overwritten**';
        $this->_dbSettings['user'] = '**overwritten**';

		$dbCon = dbeng_abs::getDbFactory($dbsettings['engine']);
		$dbCon->connect($dbsettings['host'],
						$dbsettings['user'], 
						$dbsettings['pass'], 
						$dbsettings['dbname']);

		$daoFactory = Dao_Factory::getDAOFactory($dbsettings['engine']);
		$daoFactory->setConnection($dbCon);

        SpotTiming::stop(__FUNCTION__);
		return $daoFactory;
	} # getDaoFactory

	/*
	 * Returns a sort of pre-flight check to see if 
	 * everything is setup the way we like.
	 */
	private function validate(Services_Settings_Base $settings) {
		/*
		 * The basics has been setup, lets check if the schema needs
		 * updating
		 */
		if (!$settings->schemaValid()) {
			throw new SchemaNotUpgradedException();
		} # if

		/*
		 * Does our global setting table need updating? 
		 */
		if (!$settings->settingsValid()) {
			throw new SettingsNotUpgradedException();
		} # if

		/*
		 * Because users are asked to modify ownsettings.php themselves, it is 
		 * possible they create a mistake and accidentally create output from it.
		 *
		 * This output breaks a lot of stuff like download integration, image generation
		 * and more.
		 *
		 * We try to check if any output has been submitted, and if so, we refuse
		 * to continue to prevent all sorts of confusing bug reports
		 */
		if ((headers_sent()) || ((int) ob_get_length() > 0)) {
			throw new OwnsettingsCreatedOutputException();
		} # if
	} # validate

	/*
	 * Bootup the settings system
	 */
	public function getSettings(Dao_Factory $daoFactory) {
		require_once "settings.php";

        return Services_Settings_Base::singleton($daoFactory->getSettingDao(),
									 			 $daoFactory->getBlackWhiteListDao(),
									   			 $settings);
	} # getSettings

    /**
     * Instantiate an Request object
     *
     * @param Services_Settings_Base $settings
     * @return SpotReq
     */
	private function getSpotReq(Services_Settings_Base $settings) {
		$req = new SpotReq();
		$req->initialize($settings);

		return $req;
	} # getSpotReq

    /*
     * Returns the dbSettings object if already set
     */
    function getDbSettings() {
        return $this->_dbSettings;
    } # getDbSettings

} # Bootstrap

