<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/6/16
 * Time: 3:07 PM.
 */
class SpotInstall
{
    public static function showTemplate($tplname, $vars)
    {
        /**
         * Make the variables availbale to the local context.
         */
        extract($vars, EXTR_REFS);

        require_once __DIR__.'/../templates/installer/includes/header.inc.php';
        require_once __DIR__.'/../templates/installer/'.$tplname;
    }

    public static function performAndPrintTests()
    {
        global $settings;
        global $_testInstall_Ok;

        /**
         * Load all the SSL signing code, we need it to create a private key.
         */
        $spotSigning = Services_Signing_Base::factory();
        $privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']);

        // We need either one of those 3 extensions, so set the error flag manually.
        if ((!extension_loaded('openssl')) && (!extension_loaded('gmp')) && (!extension_loaded('bcmath'))) {
            $_testInstall_Ok = false;
        }

        /**
         * Try to create the cache directory.
         */
        if (!file_exists(__DIR__.'/../cache')) {
            mkdir(__DIR__.'/../cache', 0777);
        }

        /**
         * Load the template.
         */
        static::showTemplate('step-001.inc.php', ['privKey' => $privKey]);
    }

    public static function askDbSettings()
    {
        global $settings;

        if (!isset($settings['mydb'])) {
            $form = [
                'engine'  => 'pdo_mysql',
                'rootpwd' => '',
                'host'    => 'localhost',
                'port'    => '3306',
                'dbname'  => 'spotweb',
                'schema'  => 'public',
                'user'    => 'spotweb',
                'pass'    => 'spotweb',
                'submit'  => '',
            ];
        } else {
            $form = $settings['mydb'];
            unset($settings['mydb']);
        }

        if (isset($_POST['dbform'])) {
            $form = array_merge($form, $_POST['dbform']);
        }

        /**
         * Did the user press submit? If so, try to
         * connect to the database.
         */
        $databaseCreated = false;
        if ($form['submit'] === 'Verify database') {
            if (($form['engine'] == 'pdo_mysql') and (empty($form['port']))) {
                $form['port'] = '3306';
            }

            if (($form['engine'] == 'pdo_pgsql') and (empty($form['port']))) {
                $form['port'] = '5432';
            }

            try {
                $dbCon = dbeng_abs::getDbFactory($form['engine']);

                if (($form['engine'] == 'pdo_mysql' or $form['engine'] == 'pdo_pgsql') and (!empty($form['rootpwd']))) {
                    $dbCon->connectRoot($form['host'], $form['rootpwd'], $form['port']);
                    $dbCon->createDb($form['dbname'], $form['user'], $form['pass']);
                }

                $dbCon->connect(
                    $form['host'],
                    $form['user'],
                    $form['pass'],
                    $form['dbname'],
                    $form['port'],
                    $form['schema']
                );

                $databaseCreated = true;

                /**
                 * Store the given database settings in the
                 * SESSION object, we need it later to generate
                 * a 'dbsettings.inc.php' file.
                 */
                $_SESSION['spotsettings']['db'] = $form;

                // Call the next stage in the setup.
                static::gotoPage(3);
            } catch (Exception $x) {
                static::showTemplate('fatalerror.inc.php', ['x' => $x]);
            }
        }

        if (!$databaseCreated) {
            static::showTemplate('step-002.inc.php', ['form' => $form]);
        }
    }

    public static function askNntpSettings()
    {
        global $settings;

        // Loading the file directly seems to sometimes result in a weird error. GH issue #1861
        $serverList = simplexml_load_string(file_get_contents(__DIR__.'/../usenetservers.xml'));
        if (!isset($settings['mynntp'])) {
            $form = [
                'name'   => 'custom',
                'host'   => '',
                'user'   => '',
                'pass'   => '',
                'port'   => 119,
                'enc'    => false,
                'submit' => '', 'ssl' => '', 'namefield' => 'custom', 'verifyname'=> 'on',
            ];
        } else {
            $form = $settings['mynntp'];
            unset($settings['mynntp']);
        }

        if (isset($_POST['nntpform'])) {
            //unset($form['verifyname']);
            //$form = array_merge($form, $_POST['nntpform']);
            $form = $_POST['nntpform'];
        }

        /**
         * Did the user press submit? If so, try to
         * connect to the database.
         */
        $nntpVerified = false;
        if (($form['submit'] === 'Verify usenet server') ||
            ($form['submit'] === 'Skip validation')
        ) {
            try {
                /**
                 * Convert the selected NNTP name to an actual
                 * server record.
                 */
                if ($form['name'] == 'custom') {
                    $form['buggy'] = false;
                    $form['hdr'] = $form;
                    $form['nzb'] = $form;
                    $form['post'] = $form;
                } else {
                    foreach ($serverList->usenetservers->server as $provider) {
                        if (extension_loaded('openssl') && isset($provider->ssl)) {
                            $server = $provider->ssl;
                        } else {
                            $server = $provider->plain;
                        }

                        if ((string) $provider['name'] == $form['name']) {
                            // Header usenet server
                            $form['hdr']['host'] = (string) $server->header;
                            $form['hdr']['user'] = $form['user'];
                            $form['hdr']['pass'] = $form['pass'];
                            if ((string) $server->header['ssl'] == 'yes') {
                                $form['hdr']['enc'] = 'ssl';
                            } else {
                                $form['hdr']['enc'] = false;
                            }
                            $form['hdr']['port'] = (int) $server->header['port'];
                            $form['hdr']['buggy'] = (bool) $server['buggy'];
                            $form['hdr']['verifyname'] = isset($form['verifyname']);

                            // NZB usenet server
                            $form['nzb']['host'] = (string) $server->nzb;
                            $form['nzb']['user'] = $form['user'];
                            $form['nzb']['pass'] = $form['pass'];
                            if ((string) $server->nzb['ssl'] == 'yes') {
                                $form['nzb']['enc'] = 'ssl';
                            } else {
                                $form['nzb']['enc'] = false;
                            }
                            $form['nzb']['port'] = (int) $server->nzb['port'];
                            $form['nzb']['buggy'] = (bool) $server['buggy'];
                            $form['nzb']['verifyname'] = isset($form['verifyname']);

                            // Posting usenet server
                            $form['post']['host'] = (string) $server->post;
                            $form['post']['user'] = $form['user'];
                            $form['post']['pass'] = $form['pass'];
                            if ((string) $server->post['ssl'] == 'yes') {
                                $form['post']['enc'] = 'ssl';
                            } else {
                                $form['post']['enc'] = false;
                            }
                            $form['post']['port'] = (int) $server->post['port'];
                            $form['post']['buggy'] = (bool) $server['buggy'];
                            $form['post']['verifyname'] = isset($form['verifyname']);
                        }
                    }
                }

                // Try to connect to the usenet server.
                if ($form['submit'] === 'Verify usenet server') {
                    $nntp = new Services_Nntp_Engine($form['hdr']);
                    $nntp->validateServer();
                }

                $nntpVerified = true;
                /**
                 * Store the given NNTP settings in the
                 * SESSION object, we need it later to update
                 * the settings in the database.
                 */
                $_SESSION['spotsettings']['nntp'] = $form;

                /**
                 * Call the next stage in the setup.
                 */
                static::gotoPage(4);
            } catch (Exception $x) {
                static::showTemplate('fatalerror.inc.php', ['x' => $x]);
            }
        }

        if (!$nntpVerified) {
            static::showTemplate('step-003.inc.php', [
                'form'       => $form,
                'nntpVerified' > $nntpVerified,
                'serverList' => $serverList,
            ]);
        }
    }

    public static function askSpotwebSettings()
    {
        global $settings;

        if (!isset($settings['myadminuser'])) {
            $form = [
                'systemtype'   => 'public',
                'username'     => '',
                'newpassword1' => '',
                'newpassword2' => '',
                'firstname'    => '',
                'lastname'     => '',
                'mail'         => '',
                'userid'       => -1,
            ];
        } else {
            $form = $settings['myadminuser'];
            unset($settings['myadminuser']);
        }

        if (isset($_POST['settingsform'])) {
            $form = array_merge($form, $_POST['settingsform']);
        }

        /**
         * Did the user press submit? If so, try to
         * connect to the database.
         */
        if ((isset($form['submit'])) && ($form['submit'] === 'Create system')) {
            try {
                /**
                 * Store the given user settings in the
                 * SESSION object, we need it later to update
                 * the settings in the database.
                 */
                $_SESSION['spotsettings']['adminuser'] = $form;

                /**
                 * Get the schema version and other constants.
                 */
                $bootstrap = new Bootstrap();

                /**
                 * And initiate the user system, this allows us to use
                 * validateUserRecord().
                 */
                $dbsettings = $_SESSION['spotsettings']['db'];
                $dbCon = dbeng_abs::getDbFactory($dbsettings['engine']);
                $dbCon->connect(
                    $dbsettings['host'],
                    $dbsettings['user'],
                    $dbsettings['pass'],
                    $dbsettings['dbname'],
                    $dbsettings['port'],
                    $dbsettings['schema']
                );
                $daoFactory = Dao_Factory::getDAOFactory($dbsettings['engine']);
                $daoFactory->setConnection($dbCon);
                $svcUserRecord = new ServicesValidateUserRecord(
                    $daoFactory,
                    $bootstrap->getSettings($daoFactory, false)
                );
                $errorList = $svcUserRecord->validateUserRecord($form, false)->getErrors();

                if (!empty($errorList)) {
                    throw new Exception($errorList[0]);
                }

                /**
                 * Call the next stage in the setup.
                 */
                static::gotoPage(99);
            } catch (Exception $x) {
                static::showTemplate('fatalerror.inc.php', ['x' => $x]);
            }
        }

        static::showTemplate('step-004.inc.php', [
            'form'         => $form,
        ]);
    }

    public static function createSystem()
    {
        try {
            /**
             * The settings system is used to create a lot of output,
             * we swallow it all.
             */
            ob_start();

            /**
             * Get the schema version and other constants.
             */
            $bootstrap = new Bootstrap();
            $schema = 'public';

            /**
             * Now create the database.
             */
            $dbsettings = $_SESSION['spotsettings']['db'];
            $dbCon = dbeng_abs::getDbFactory($dbsettings['engine']);
            $dbCon->connect(
                $dbsettings['host'],
                $dbsettings['user'],
                $dbsettings['pass'],
                $dbsettings['dbname'],
                $dbsettings['port'],
                $dbsettings['schema']
            );

            $daoFactory = Dao_Factory::getDAOFactory($dbsettings['engine']);
            $daoFactory->setConnection($dbCon);

            /**
             * The database must exist before we can get the Service_Settings_Base instance.
             */
            $dbStruct = SpotStruct_abs::factory($dbsettings['engine'], $daoFactory->getConnection());
            $dbStruct->updateSchema();

            $spotSettings = $bootstrap->getSettings($daoFactory, false);
            $svcUpgradeBase = new Services_Upgrade_Base($daoFactory, $spotSettings, $dbsettings['engine']);

            /**
             * Create all the different settings (only the default) ones.
             */
            $svcUpgradeBase->settings();

            /**
             * Create the users.
             */
            $svcUpgradeBase->users();

            /**
             * print all the output as HTML comment for debugging.
             */
            $dbCreateOutput = ob_get_contents();
            ob_end_clean();

            /**
             * Now it is time to do something with
             * the information the user has given to us.
             */

            /**
             * Update the NNTP settings in the databas.
             */
            $spotSettings->set('nntp_nzb', $_SESSION['spotsettings']['nntp']['nzb']);
            $spotSettings->set('nntp_hdr', $_SESSION['spotsettings']['nntp']['hdr']);
            $spotSettings->set('nntp_post', $_SESSION['spotsettings']['nntp']['post']);

            /**
             * Create the given user.
             */
            $svcUserRecord = new Services_User_Record($daoFactory, $spotSettings);
            $spotUser = $_SESSION['spotsettings']['adminuser'];

            /**
             * and actually add the user.
             */
            $spotUser['userid'] = $svcUserRecord->createUserRecord($spotUser)->getData('userid');

            /**
             * When the new user was created a random password was assigned,
             * so now have to set the supplied password.
             */
            $svcUserRecord->setUserPassword($spotUser);

            // Change the administrators' account password to that of this created user.
            $adminUser = $svcUserRecord->getUser(SPOTWEB_ADMIN_USERID);
            $adminUser['newpassword1'] = $spotUser['newpassword1'];
            $svcUserRecord->setUserPassword($adminUser);

            // Update the settings with our system type and our admin id.
            $spotSettings->set('custom_admin_userid', $spotUser['userid']);
            $spotSettings->set('systemtype', $spotUser['systemtype']);

            // Set the system type.
            $svcUpgradeBase->resetSystemType($spotUser['systemtype']);

            /**
             * Create the necessary database connection information (dbsettings.inc.php).
             */
            $dbConnectionString = '';
            switch ($_SESSION['spotsettings']['db']['engine']) {
                case 'pdo_mysql':
                    $dbConnectionString = static::createDbSettingsFile('pdo_mysql');
                    break;
                case 'pdo_pgsql':
                    $dbConnectionString = static::createDbSettingsFile('pdo_pgsql');
                    break;
                case 'pdo_sqlite':
                    $dbConnectionString = static::createDbSettingsFile('pdo_sqlite');
                    break;
            }

            static::showTemplate(
                'step-final.inc.php',
                [
                    'createdDbSettings'  => file_exists(__DIR__.'/../dbsettings.inc.php'),
                    'dbCreateOutput'     => $dbCreateOutput,
                    'dbConnectionString' => $dbConnectionString,
                ]
            );
        } catch (Exception $x) {
            static::showTemplate('fatalerror.inc.php', ['x' => $x]);
        }
    }

    /**
     * Gets the database connection string.
     *
     * @param string $engine
     *
     * @return string
     */
    public static function createDbSettingsFile($engine)
    {
        $dbSettings = $_SESSION['spotsettings']['db'];
        switch ($_SESSION['spotsettings']['db']['engine']) {
    case 'pdo_pgsql':
        $settings = sprintf(
            '<?php%1$s%1$s'
            .'$dbsettings[\'engine\'] = \'%2$s\';%1$s'
            .'$dbsettings[\'host\'] = \'%3$s\';%1$s'
            .'$dbsettings[\'dbname\'] = \'%4$s\';%1$s'
            .'$dbsettings[\'user\'] = \'%5$s\';%1$s'
            .'$dbsettings[\'pass\'] = \'%6$s\';%1$s'
            .'$dbsettings[\'port\'] = \'%7$s\';%1$s'
            .'$dbsettings[\'schema\'] = \'%8$s\';%1$s',
            PHP_EOL,
            $engine,
            $dbSettings['host'],
            $dbSettings['dbname'],
            $dbSettings['user'],
            $dbSettings['pass'],
            $dbSettings['port'],
            $dbSettings['schema']
        );
            break;
    case 'pdo_mysql':
    case 'pdo_sqlite':
            $settings = sprintf(
                '<?php%1$s%1$s'
            .'$dbsettings[\'engine\'] = \'%2$s\';%1$s'
            .'$dbsettings[\'host\'] = \'%3$s\';%1$s'
            .'$dbsettings[\'dbname\'] = \'%4$s\';%1$s'
            .'$dbsettings[\'user\'] = \'%5$s\';%1$s'
            .'$dbsettings[\'pass\'] = \'%6$s\';%1$s'
            .'$dbsettings[\'port\'] = \'%7$s\';%1$s'
            .'$dbsettings[\'schema\'] = \'\';',
                PHP_EOL,
                $engine,
                $dbSettings['host'],
                $dbSettings['dbname'],
                $dbSettings['user'],
                $dbSettings['pass'],
                $dbSettings['port']
            );
            break;
    }

        if (is_writable(__DIR__.'/../')) {
            file_put_contents(
                __DIR__.'/../dbsettings.inc.php',
                $settings
            );
        }

        return $settings;
    }

    public static function returnBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $val *= 1024 * 1024;
                break;
            case 'k':
                $val *= 1024;
                break;
        }

        return $val;
    }

    public static function showResult($b, $isRequired, $okMsg = '', $nokMsg = '')
    {
        global $_testInstall_Ok;

        if ($b) {
            echo 'OK';
            if (!empty($okMsg)) {
                echo ' ('.$okMsg.')';
            }
        } else {
            echo 'NOT OK';
            if (!empty($nokMsg)) {
                echo ' ('.$nokMsg.')';
            }

            if ($isRequired) {
                $_testInstall_Ok = true;
            }
        }

        return null;
    }

    /**
     * Callback for set_error_handler.
     *
     * @param int    $number
     * @param string $message
     * @param string $file
     * @param string $line
     * @param array  $context
     *
     * @return bool False, when the default error handler should take over and true when we handled it ourself.
     */
    public static function ownWarning($number, $message, $file, $line, array $context)
    {
        // don't show errors if they are being suppressed by silent (@) operator.
        if (error_reporting() === 0) {
            return false;
        }

        $GLOBALS['iserror'] = true;
        error_log($message);
        echo $message;

        return true;
    }

    public static function testInclude($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        require_once $filename;

        return dirname(realpath($filename));
    }

    /**
     * Goes to the one of the installation pages.
     *
     * @param int $number
     */
    public static function gotoPage($number)
    {
        header(
            sprintf(
                'Location: %s?page=%u',
                $_SERVER['SCRIPT_NAME'],
                $number
            )
        );
    }
}
