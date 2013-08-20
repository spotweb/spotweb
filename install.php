<?php
	error_reporting(2147483647);

	require_once "lib/SpotClassAutoload.php";
	try {
		@include('settings.php');
        @include('dbsettings.inc.php');
	}
	catch(Exception $x) {
		// ignore errors
	} # catch
	set_error_handler("ownWarning",E_WARNING);

	if (file_exists('reallymyownsettings.php'))
	{
		include_once('reallymyownsettings.php');
	}

	/*
	 * We output headers after already sending HTML, make
	 * sure output buffering is turned on.
	 */
	ob_start();
	
	/*
	 * We default to a succeeded install, let it prove
	 * otherwise
	 */
	global $_testInstall_Ok;

	$_testInstall_Ok = true;
	session_start();

    function showTemplate($tplname, $vars) {
        global $settings;
        global $_testInstall_Ok;

        /*
         * Make the variables availbale to the local context
         */
        extract($vars, EXTR_REFS);

        require_once "templates/installer/includes/header.inc.php";
        require_once "templates/installer/" . $tplname;
    } # showTemplate

	function performAndPrintTests() {
		global $settings;
		global $_testInstall_Ok;

        /*
         * Load all the SSL signing code, we need it to create a private key
         */
        require_once "lib/services/Signing/Services_Signing_Base.php";
        require_once "lib/services/Signing/Services_Signing_Php.php";
        require_once "lib/services/Signing/Services_Signing_Openssl.php";
        $spotSigning = Services_Signing_Base::factory();
        $privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']);

        /* We need either one of those 3 extensions, so set the error flag manually */
        if ( (!extension_loaded('openssl')) && (!extension_loaded('gmp')) && (!extension_loaded('bcmath'))) {
            $_testInstall_Ok = false;
        } # if

        /*
         * Try to create the cache directory
         */
        @mkdir('./cache', 0777);

        /*
         * Load the template
         */
        showTemplate("step-001.inc.php", array('privKey' => $privKey));
	} # performAndPrintTests

	function askDbSettings() {
		global $settings;
		global $_testInstall_Ok;

		if (!isset($settings['mydb'])) {
			$form = array('engine' => 'pdo_mysql',
						  'host' => 'localhost',
						  'dbname' => 'spotweb',
						  'user' => 'spotweb',
						  'pass' => 'spotweb',
						  'submit' => '');
		} else {
			$form = $settings['mydb'];
			unset($settings['mydb']);
		} # else

		if (isset($_POST['dbform'])) {
			$form = array_merge($form, $_POST['dbform']);
		} # if

		/*
		 * Did the user press submit? If so, try to
		 * connect to the database
		 */
		$databaseCreated = false;
		if ($form['submit'] === 'Verify database') {
			try {
				$dbCon = dbeng_abs::getDbFactory($form['engine']);
				$dbCon->connect($form['host'],
								$form['user'],
								$form['pass'],
								$form['dbname']);

				$databaseCreated = true;

				/*
				 * Store the given database settings in the 
				 * SESSION object, we need it later to generate
				 * a 'dbsettings.inc.php' file
				 */
				$_SESSION['spotsettings']['db'] = $form;

				/*
				 * and call the next stage in the setup
				 */
				Header("Location: " . $_SERVER['SCRIPT_NAME'] . '?page=3');
			} 
			catch(Exception $x) {
                showTemplate("fatalerror.inc.php", array('x' => $x));
			} # exception
		} # if

		if (!$databaseCreated) {
            showTemplate("step-002.inc.php", array('form' => $form));
		} # else
	} # askDbSettings

	function askNntpSettings() {
		global $settings;
		global $_testInstall_Ok;

        /*
         * Loading the file directly seems to sometimes result
         * in a weird error. GH issue #1861
         */
		$serverList = simplexml_load_string(file_get_contents('usenetservers.xml'));
		if (!isset($settings['mynntp'])) {
			$form = array('name' => 'custom',
						  'host' => '',
						  'user' => '',
						  'pass' => '',
						  'port' => 119,
						  'enc' => false,
						  'submit' => '');
		} else {
			$form = $settings['mynntp'];
			unset($settings['mynntp']);
		} # else

		if (isset($_POST['nntpform'])) {
			$form = array_merge($form, $_POST['nntpform']);
		} # if

		/*
		 * Did the user press submit? If so, try to
		 * connect to the database
		 */
		$nntpVerified = false;
		if (($form['submit'] === 'Verify usenet server') ||
            ($form['submit'] === 'Skip validation')) {
			try {
				/*
				 * Convert the selected NNTP name to an actual
				 * server record.
				 */
				if ($form['name'] == 'custom') {
						$form['buggy'] = false;
						$form['hdr'] = $form;
						$form['nzb'] = $form;
						$form['post'] = $form;
				} else {
					foreach($serverList->usenetservers->server as $provider) {
						if (extension_loaded('openssl') && isset($provider->ssl)) {
							$server = $provider->ssl;
						} else {
							$server = $provider->plain;
						} # if

						if ( (string) $provider['name'] == $form['name'] ) {
							# Header usenet server
							$form['hdr']['host'] = (string) $server->header;
							$form['hdr']['user'] = $form['user'];
							$form['hdr']['pass'] = $form['pass'];
							if ( (string) $server->header['ssl'] == 'yes') {
								$form['hdr']['enc'] = 'ssl';
							} else {
								$form['hdr']['enc'] = false;
							} # else
							$form['hdr']['port'] = (int) $server->header['port'];
							$form['hdr']['buggy'] = (boolean) $server['buggy'];

							# NZB usenet server
							$form['nzb']['host'] = (string) $server->nzb;
							$form['nzb']['user'] = $form['user'];
							$form['nzb']['pass'] = $form['pass'];
							if ( (string) $server->nzb['ssl'] == 'yes') {
								$form['nzb']['enc'] = 'ssl';
							} else {
								$form['nzb']['enc'] = false;
							} # else
							$form['nzb']['port'] = (int) $server->nzb['port'];
							$form['nzb']['buggy'] = (boolean) $server['buggy'];

							# Posting usenet server
							$form['post']['host'] = (string) $server->post;
							$form['post']['user'] = $form['user'];
							$form['post']['pass'] = $form['pass'];
							if ( (string) $server->post['ssl'] == 'yes') {
								$form['post']['enc'] = 'ssl';
							} else {
								$form['post']['enc'] = false;
							} # else
							$form['post']['port'] = (int) $server->post['port'];
							$form['post']['buggy'] = (boolean) $server['buggy'];
						} # if
					} # foreach
				} # else 
				
				/* and try to connect to the usenet server */
                if ($form['submit'] === 'Verify usenet server') {
                    $nntp = new Services_Nntp_Engine($form['hdr']);
				    $nntp->validateServer();
                } # if

				$nntpVerified = true;
				/*
				 * Store the given NNTP settings in the 
				 * SESSION object, we need it later to update
				 * the settings in the database
				 */
				$_SESSION['spotsettings']['nntp'] = $form;
				
				/*
				 * and call the next stage in the setup
				 */
				Header("Location: " . $_SERVER['SCRIPT_NAME'] . '?page=4');
			} 
			catch(Exception $x) {
                showTemplate("fatalerror.inc.php", array('x' => $x));
			} # exception
		} # if
		
		if (!$nntpVerified) {
            showTemplate("step-003.inc.php", array('form' => $form,
                                                    'nntpVerified' > $nntpVerified,
                                                    'serverList' => $serverList));
		} # else
	} # askNntpSettings
	
	function askSpotwebSettings() {
		global $settings;
		global $_testInstall_Ok;

		if (!isset($settings['myadminuser'])) {
			$form = array('systemtype' => 'public',
						  'username' => '', 'newpassword1' => '', 'newpassword2' => '', 'firstname' => '',
						  'lastname' => '', 'mail' => '', 'userid' => -1);
		} else {
			$form = $settings['myadminuser'];
			unset($settings['myadminuser']);
		}

		if (isset($_POST['settingsform'])) {
			$form = array_merge($form, $_POST['settingsform']);
		} # if

		/*
		 * Did the user press submit? If so, try to
		 * connect to the database
		 */
		$userVerified = false;
		if ((isset($form['submit'])) && ($form['submit'] === 'Create system')) {
			try {
				/*
				 * Store the given user settings in the 
				 * SESSION object, we need it later to update
				 * the settings in the database
				 */
				$_SESSION['spotsettings']['adminuser'] = $form;

				/*
				 * Very ugly hack. We create an empty Services_Settings_Base class
				 * so this will satisfy the constructor in the system.
				 * It's ugly, I know.
				 */
				class Services_Settings_Base { } ;

				/*
				 * Override the Service_User_Record class so we can override userEmailExists()
				 * to not require database access.
				*/
				class Services_ValidateUser_Record extends Services_User_Record {

					function validateUserEmailExists($user) {
						$result = new Dto_FormResult();

						if (($user['mail'] == 'john@example.com') || ($user['mail'] == 'spotwebadmin@example.com')) {
							$result->addError(_('Mailaddress is already in use'));
						} # if

						return $result;
					} # validateUserRecord
				}

				/*
				 * And initiate the user system, this allows us to use
				 * validateUserRecord()
				 */
				$dbsettings = $_SESSION['spotsettings']['db'];
                $dbCon = dbeng_abs::getDbFactory($dbsettings['engine']);
                $dbCon->connect($dbsettings['host'],
                    $dbsettings['user'],
                    $dbsettings['pass'],
                    $dbsettings['dbname']);
                $daoFactory = Dao_Factory::getDAOFactory($dbsettings['engine']);
                $daoFactory->setConnection($dbCon);
				$svcUserRecord = new Services_ValidateUser_Record($daoFactory, new Services_Settings_Base());
				$errorList = $svcUserRecord->validateUserRecord($form, false)->getErrors();

				if (!empty($errorList)) {
					throw new Exception($errorList[0]);
				} # if

				/*
				 * and call the next stage in the setup
				 */
				Header("Location: " . $_SERVER['SCRIPT_NAME'] . '?page=99');
			} 
			catch(Exception $x) {
                showTemplate("fatalerror.inc.php", array('x' => $x));
			} # exception
		} # if

		if (!$userVerified) {
            showTemplate("step-004.inc.php", array('form' => $form,
                                                    'userVerified' => $userVerified));
		} # else
	} # askSpotwebSettings

	function createSystem() {
		global $settings;
		global $_testInstall_Ok;

		try {

			/*
			 * The settings system is used to create a lot of output,
			 * we swallow it all
			 */
			ob_start();

			/*
			 * Get the schema version and other constants
			 */
			require_once "lib/Bootstrap.php";
			$bootstrap = new Bootstrap();

			/*
			 * Now create the database
			 */
			$dbsettings = $_SESSION['spotsettings']['db'];
			$dbCon = dbeng_abs::getDbFactory($dbsettings['engine']);
			$dbCon->connect($dbsettings['host'],
							$dbsettings['user'],
							$dbsettings['pass'],
							$dbsettings['dbname']);

			$daoFactory = Dao_Factory::getDAOFactory($dbsettings['engine']);
			$daoFactory->setConnection($dbCon);

			/*
			 * The database must exist before we can get the Service_Settings_Base instance
			 */
			$dbStruct = SpotStruct_abs::factory($dbsettings['engine'], $daoFactory->getConnection());
			$dbStruct->updateSchema();

			$spotSettings = Services_Settings_Base::singleton($daoFactory->getSettingDao(),
															  $daoFactory->getBlackWhiteListDao(),
															  $settings);

			$svcUpgradeBase = new Services_Upgrade_Base($daoFactory, $spotSettings, $dbsettings['engine']);

			/*
			 * Create all the different settings (only the default) ones
			 */
			$svcUpgradeBase->settings();

			/*
			 * Create the users
			 */
			$svcUpgradeBase->users();

			/*
			 * print all the output as HTML comment for debugging
			 */
			$dbCreateOutput = ob_get_contents();
			ob_end_clean();

			/*
			 * Now it is time to do something with
			 * the information the user has given to us
			 */

			/*
			 * Update the NNTP settings in the databas
			 */
			$spotSettings->set('nntp_nzb', $_SESSION['spotsettings']['nntp']['nzb']);
			$spotSettings->set('nntp_hdr', $_SESSION['spotsettings']['nntp']['hdr']);
			$spotSettings->set('nntp_post', $_SESSION['spotsettings']['nntp']['post']);

			/*
			 * Create the given user
			 */
			$svcUserRecord = new Services_User_Record($daoFactory, $spotSettings);
			$spotUser = $_SESSION['spotsettings']['adminuser'];

			/*
			 * and actually add the user
			 */
			$spotUser['userid'] = $svcUserRecord->createUserRecord($spotUser)->getData('userid');

			/*
			 * When the new user was created a random password was assigned, 
			 * so now have to set the supplied password
			 */
			$svcUserRecord->setUserPassword($spotUser);

			# Change the administrators' account password to that of this created user
			$adminUser = $svcUserRecord->getUser(SPOTWEB_ADMIN_USERID);
			$adminUser['newpassword1'] = $spotUser['newpassword1'];
			$svcUserRecord->setUserPassword($adminUser);

			# update the settings with our system type and our admin id
			$spotSettings->set('custom_admin_userid', $spotUser['userid']);
			$spotSettings->set('systemtype', $spotUser['systemtype']);

			# Set the system type
			$svcUpgradeBase->resetSystemType($spotUser['systemtype']);

			/* 
			 * Create the necessary database connection information
			 */
			$dbConnectionString = '';
			switch ($_SESSION['spotsettings']['db']['engine']) {
				case 'pdo_mysql' : {
					$dbConnectionString .= "\$dbsettings['engine'] = 'pdo_mysql';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['host'] = '" . $_SESSION['spotsettings']['db']['host'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['dbname'] = '" . $_SESSION['spotsettings']['db']['dbname'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['user'] = '" . $_SESSION['spotsettings']['db']['user'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['pass'] = '" . $_SESSION['spotsettings']['db']['pass'] . "';" . PHP_EOL;

					break;
				} # mysql

				case 'pdo_pgsql' : {
					$dbConnectionString .= "\$dbsettings['engine'] = 'pdo_pgsql';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['host'] = '" . $_SESSION['spotsettings']['db']['host'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['dbname'] = '" . $_SESSION['spotsettings']['db']['dbname'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['user'] = '" . $_SESSION['spotsettings']['db']['user'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['pass'] = '" . $_SESSION['spotsettings']['db']['pass'] . "';" . PHP_EOL;

					break;
				} # pdo_pgsql 
			} # switch

			# Try to create the dbsettings.inc.php file for the user
			@file_put_contents("dbsettings.inc.php", "<?php" . PHP_EOL . $dbConnectionString);
			$createdDbSettings = file_exists("dbsettings.inc.php");

            showTemplate("step-final.inc.php", array('createdDbSettings' => $createdDbSettings,
                                                     'dbCreateOutput' => $dbCreateOutput,
                                                     'dbConnectionString' => $dbConnectionString));
		}  # try
		catch(Exception $x) {
            showTemplate("fatalerror.inc.php", array('x' => $x));
		} # exception
	} # createSystem
	

	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	} # return_bytes

	function showResult($b, $isRequired, $okMsg="", $nokMsg="") {
		global $_testInstall_Ok;
		
		if ($b) {
			echo "OK";
			if (!empty($okMsg)) {
				echo ' (' . $okMsg . ')';
			} 
		} else {
			echo "NOT OK";
			if (!empty($nokMsg)) {
				echo ' (' . $nokMsg . ')';
			}
			
			if ($isRequired) {
				$_testInstall_Ok = true;
			} # if
		} # else
		
		return null;
	} # showResult

    /*
     * Dummy translate function
     */
    if (!function_exists('_')) {
        function _($s) {
            return $s;
        } # _()
    } # if

	function ownWarning($errno, $errstr) {
        /* don't show errors if they are being suppressed by silent (@) operator */
        if (error_reporting() == 0) {
            return;
        }

        $GLOBALS['iserror'] = true;
        error_log($errstr);
        echo $errstr;
	} # ownWarning

	function testInclude($fname) {
		@include_once($fname);
		foreach (get_included_files() as $filename) {
			if (strpos($filename, $fname, strlen($filename) - strlen($fname)) !== false) {
				return dirname($filename);
			}
		}

        return false;
	} # testInclude

	/*
	 * Only run the wizard when no database settings have been entered yet, to prevent
	 * any information disclosure
	 */
	if ((isset($dbsettings)) && (isset($_GET['page']))) {
        showTemplate("fatalerror.inc.php",
                array('x' => new Exception("Spotweb has already been setup. If you want to run this wizard again, please remove the file 'dbsettings.inc.php'")));
		die();
	} # if

	/*
	 * determine what page of the wizzard we are on, and display that one
	 */
	$pageNumber = (isset($_GET['page']) ? $_GET['page'] : 1);
	
	switch($pageNumber) {
		case 2			: askDbSettings(); break;
		case 3			: askNntpSettings(); break;
		case 4			: askSpotwebSettings(); break;
		case 99			: createSystem(); break;
		
		default			: performAndPrintTests(); break;
	} # switch

	ob_end_flush();
