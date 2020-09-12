<?php
/*
 * Class to storage all settings in. Contains both 'ownsettings.php' settings as database settings
 */
class Services_Settings_Base
{
    /**
     * @var Services_Settings_Container
     */
    private $_settings;
    /**
     * @var Dao_BlackWhiteList
     */
    private $_blackWhiteListDao;

    /*
     * Private constructor, class is singleton
     */
    public function __construct(Services_Settings_Container $settings, Dao_BlackWhiteList $blackWhiteListDao)
    {
        $this->_settings = $settings;
        $this->_blackWhiteListDao = $blackWhiteListDao;
    }

    // ctor

    /*
     * Validate settings
     */
    public function validateSettings($settings)
    {
        $result = new Dto_FormResult();

        // Define arrays with valid settings
        $validNntpEnc = [false, 'ssl', 'tls'];
        $validModerationAction = ['disable', 'act', 'markspot'];
        $validRetentionTypes = ['fullonly', 'everything'];

        // Get the given value for NNTP encryption
        $settings['nntp_nzb']['enc'] = (isset($settings['nntp_nzb']['enc']['switch'])) ? $settings['nntp_nzb']['enc']['select'] : false;
        $settings['nntp_hdr']['enc'] = (isset($settings['nntp_hdr']['enc']['switch'])) ? $settings['nntp_hdr']['enc']['select'] : false;
        $settings['nntp_post']['enc'] = (isset($settings['nntp_post']['enc']['switch'])) ? $settings['nntp_post']['enc']['select'] : false;

        // Get the given value for NNTP encryption validate name
        $settings['nntp_nzb']['verifyname'] = (isset($settings['nntp_nzb']['verifyname']['switch'])) ? true : false;
        $settings['nntp_hdr']['verifyname'] = (isset($settings['nntp_hdr']['verifyname']['switch'])) ? true : false;
        $settings['nntp_post']['verifyname'] = (isset($settings['nntp_post']['verifyname']['switch'])) ? true : false;

        // Trim human-entered text fields
        $settings['nntp_nzb']['host'] = trim($settings['nntp_nzb']['host']);
        $settings['nntp_hdr']['host'] = trim($settings['nntp_hdr']['host']);
        $settings['nntp_post']['host'] = trim($settings['nntp_post']['host']);

        // Verify settings with the previous declared arrays
        if (in_array($settings['nntp_nzb']['enc'], $validNntpEnc) === false || in_array($settings['nntp_hdr']['enc'], $validNntpEnc) === false || in_array($settings['nntp_post']['enc'], $validNntpEnc) === false) {
            $result->addError(_('Invalid encryption setting'));
        } // if
        if (in_array($settings['spot_moderation'], $validModerationAction) === false) {
            $result->addError(_('Invalid spot moderation setting'));
        } // if
        if (in_array($settings['retentiontype'], $validRetentionTypes) === false) {
            $result->addError(_('Invalid spot retentiontype setting'));
        } // if

        // Verify settings
        $settings['cookie_expires'] = (int) $settings['cookie_expires'];
        if ($settings['cookie_expires'] < 0) {
            $result->addError(_('Invalid cookie_expires setting'));
        } // if

        $settings['retention'] = (int) $settings['retention'];
        if ($settings['retention'] < 0) {
            $result->addError(_('Invalid retention setting'));
        } // if

        $settings['retrieve_newer_than'] = strtotime($settings['retrieve_newer_than']);
        if (($settings['retrieve_newer_than'] === false) || $settings['retrieve_newer_than'] > time()) {
            $result->addError(_('Invalid retrieve_newer_than setting'));
        } elseif ($settings['retrieve_newer_than'] < 1230789600) {
            /* We don't allow settings earlier than january 1st 2009 */
            $settings['retrieve_newer_than'] = 1230789600;
        } // elseif

        $settings['retrieve_increment'] = (int) $settings['retrieve_increment'];
        if ($settings['retrieve_increment'] < 1) {
            $result->addError(_('Invalid retrieve_increment setting'));
        } // if

        // check the mailaddress
        if (!filter_var($settings['systemfrommail'], FILTER_VALIDATE_EMAIL)) {
            $result->addError(_('Not a valid email address'));
        } // if

        // check the highcount
        $settings['highcount'] = (int) $settings['highcount'];
        if ($settings['highcount'] < 1) {
            $result->addError(_('The amount of comments must be a number above 1'));
        }

        // We don't want to save megabyts of CSS, so put a limit to the size
        if (strlen($settings['customcss'] > 1024 * 10)) {
            $result->addError(_('Custom CSS is too large'));
        } // if

        // validate smtp settings or sett default if not used
        $settings['smtp']['use'] = (isset($settings['smtp']['use']['switch'])) ? true : false;
        if ($settings['smtp']['use']) {
            $settings['smtp']['host'] = trim($settings['smtp']['host']);
            $settings['smtp']['user'] = trim($settings['smtp']['user']);
            $settings['smtp']['pass'] = trim($settings['smtp']['pass']);
            $settings['smtp']['port'] = trim($settings['smtp']['port']);
            if (!strlen($settings['smtp']['host'])) {
                $result->addError(_('SMTP Hostname cannot be empty if SMTP is to be used'));
            }
            if (!strlen($settings['smtp']['user'])) {
                $result->addError(_('SMTP Username may not be empty if SMTP is to be used (don\'t use open relays!)'));
            }
            if (!strlen($settings['smtp']['pass'])) {
                $result->addError(_('SMTP Password may not be empty if SMTP is to be used (don\'t use open relays!)'));
            }
            if (is_numeric($settings['smtp']['port'])) {
                $settings['smtp']['port'] += 0;
                if (!is_int($settings['smtp']['port']) || ($settings['smtp']['port'] < 1) || ($settings['smtp']['port'] > 65535)) {
                    $result->addError(_('SMTP Port must be integer between 1 and 65535'));
                }
            } else {
                $result->addError(_('SMTP Port must be numeric'));
            }
        } else {
            $settings['smtp'] = ['use' => false,
                'host'                 => '',
                'user'                 => '',
                'pass'                 => '',
                'port'                 => 587, ];
        } // if

        // Convert other settings (usually checkboxes) to be simply boolean settings
        $settings['deny_robots'] = (isset($settings['deny_robots'])) ? true : false;
        $settings['sendwelcomemail'] = (isset($settings['sendwelcomemail'])) ? true : false;
        $settings['nntp_nzb']['buggy'] = (isset($settings['nntp_nzb']['buggy'])) ? true : false;
        $settings['nntp_hdr']['buggy'] = (isset($settings['nntp_hdr']['buggy'])) ? true : false;
        $settings['nntp_post']['buggy'] = (isset($settings['nntp_post']['buggy'])) ? true : false;
        $settings['retrieve_full'] = (isset($settings['retrieve_full'])) ? true : false;
        $settings['prefetch_image'] = (isset($settings['prefetch_image'])) ? true : false;
        $settings['prefetch_nzb'] = (isset($settings['prefetch_nzb'])) ? true : false;
        $settings['retrieve_comments'] = (isset($settings['retrieve_comments'])) ? true : false;
        $settings['retrieve_full_comments'] = (isset($settings['retrieve_full_comments'])) ? true : false;
        $settings['retrieve_reports'] = (isset($settings['retrieve_reports'])) ? true : false;
        $settings['enable_timing'] = (isset($settings['enable_timing'])) ? true : false;
        $settings['enable_stacktrace'] = (isset($settings['enable_stacktrace'])) ? true : false;
        $settings['prepare_statistics'] = (isset($settings['prepare_statistics'])) ? true : false;
        $settings['external_blacklist'] = (isset($settings['external_blacklist'])) ? true : false;
        $settings['external_whitelist'] = (isset($settings['external_whitelist'])) ? true : false;
        $settings['imageover_subcats'] = (isset($settings['imageover_subcats'])) ? true : false;
        $settings['highlight'] = (isset($settings['highlight'])) ? true : false;

        // Default server settings if they won't be used
        if (!isset($settings['nntp_hdr']['use'])) {
            $settings['nntp_hdr'] = ['host' => '',
                'user'                      => '',
                'pass'                      => '',
                'enc'                       => false,
                'port'                      => 119,
                'buggy'                     => false,
                'verifyname'                => true, ];
        } // if

        if (!isset($settings['nntp_post']['use'])) {
            $settings['nntp_post'] = ['host' => '',
                'user'                       => '',
                'pass'                       => '',
                'enc'                        => false,
                'port'                       => 119,
                'buggy'                      => false,
                'verifyname'                 => true, ];
        } // if

        /*
         * Remove dummy preferences
         */
        unset($settings['nntp_hdr']['use'], $settings['nntp_post']['use']);

        /*
         * We want to pass the updated settings back to the caller because
         * we fixed several stuff.
         */
        $result->addData('settings', $settings);

        return $result;
    }

    // validateSettings

    public function setSettings($settings)
    {
        // If we disable the external blacklist, clear all entries
        if ($settings['external_blacklist'] == false && $this->_settings->get('external_blacklist') == true) {
            $this->_blackWhiteListDao->removeOldList($this->_settings->get('blacklist_url'), 'black');
        } // if

        // If we disable the external whitelist, clear all entries
        if ($settings['external_whitelist'] == false && $this->_settings->get('external_whitelist') == true) {
            $this->_blackWhiteListDao->removeOldList($this->_settings->get('whitelist_url'), 'white');
        } // if

        // clear some stuff we don't need to store
        unset($settings['xsrfid'], $settings['http_referer'], $settings['buttonpressed'], $settings['action'], $settings['submitedit']);

        // Store settings
        foreach ($settings as $key => $value) {
            // and write these updated settings to the database
            $this->_settings->set($key, $value);
        } // foreach
    }

    // setSettings

    /*
     * Is our database version still valid?
     */
    public function schemaValid()
    {
        // Is our database still up to date
        return $this->_settings->get('schemaversion') == SPOTDB_SCHEMA_VERSION;
    }

    // schemaValid

    /*
     * Zijn onze settings versie nog wel geldig?
     */
    public function settingsValid()
    {
        // Is our settings list still valid?
        return $this->_settings->get('settingsversion') == SPOTWEB_SETTINGS_VERSION;
    }

    // settingsValid
} // class Services_Settings_Container
