<?php
error_reporting(2147483647);

try {
	/*
	 * If we are run from another directory, try to change the current
	 * working directory to a directory the script is in
	 */
	if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
		chdir(dirname(__FILE__));
	} # if

	require_once "lib/SpotTranslation.php";
	require_once "lib/SpotClassAutoload.php";
	require_once "settings.php";
	require_once "lib/SpotTiming.php";
	require_once "lib/exceptions/ParseSpotXmlException.php";
	require_once "lib/exceptions/NntpException.php";

	/*
	 * disable timing, all queries which are ran by retrieve this would make it use
	 * large amounts of memory
	 */
	SpotTiming::disable();

	# Initialize commandline arguments
	SpotCommandline::initialize(array('force', 'debug', 'retro'), array('force' => false, 'debug' => false, 'retro' => false));

	# Initialize translation to english 
	SpotTranslation::initialize('en_US');

	/*
	 * When PHP is running in safe mode, max execution time cannot be set,
	 * which is necessary on slow systems for retrieval and statistics generation
	 */
	if (ini_get('safe_mode') ) {
		echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems" . PHP_EOL . PHP_EOL;
	} # if

	$db = new SpotDb($settings['db']);
	$db->connect();

	# Create the settings object, needed for all other code
	$settings = SpotSettings::singleton($db, $settings);

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

	$req = new SpotReq();
	$req->initialize($settings);

	/*
	 * When retrieval is run from the webinterface, we want to make
	 * sure this user is actually allowed to run retrieval.
	 */
	$spotUserSystem = new SpotUserSystem($db, $settings);
	if (!SpotCommandline::isCommandline()) {
		/*
		 * An API key is required, so request it and try to
		 * create a session with it which we can use to validate
		 * the user with
		 */
		$apiKey = $req->getDef('apikey', '');
		$userSession = $spotUserSystem->verifyApi($apiKey);

		/*
		 * If the session failed or the the user doesn't have access
		 * to retrieve spots, let the user know
		 */
		if (($userSession == false) || (!$userSession['security']->allowed(SpotSecurity::spotsec_retrieve_spots, ''))) { 
			throw new PermissionDeniedException(SpotSecurity::spotsec_retrieve_spots, '');
		} # if
		
		# Add the user's ip addres, we need it for sending notifications
		$userSession['session'] = array('ipaddr' => '');
	} else {
		$userSession['user'] = $db->getUser(SPOTWEB_ADMIN_USERID);
		$userSession['security'] = new SpotSecurity($db, $settings, $userSession['user'], '');
		$userSession['session'] = array('ipaddr' => '');
	} # if

	/*
	 * Retrieve the NNTP header settings weo can validate those
	 */
	$settings_nntp_hdr = $settings->get('nntp_hdr');
	$settings_nntp_bin = $settings->get('nntp_nzb');
	if (empty($settings_nntp_hdr['host'])) {
		throw new MissingNntpConfigurationException();
	} # if
	
	/*
	 * We normally check whether we are not running already, because
	 * this would mean it will mess up all sorts of things like
	 * comment calculation, but a user can force our hand
	 */
	if (SpotCommandline::get('force')) {
		$db->setRetrieverRunning($settings_nntp_hdr['host'], false);
	} # if

	/*
	 * Do we need to debuglog this session? Generates loads of
	 * output
	 */
	$debugLog = SpotCommandline::get('debug');

	/*
	 * Retro mode will allow os to start from the beginning and retrieve
	 * all spots starting from scratch
	 */
	$retroMode = SpotCommandline::get('retro');

	/*
	 * Retention cleanup. Basically when we ask for Spotweb to only
	 * keep spots for 'xx' days (eg: 30 days), we either have to delete
	 * everyting older than 'xx' days, or delete all 'full' resources
	 * older than the specified time period.
	 *
	 * The full resources are everything beyond the bare minimum to 
	 * display the spots, so we delete nzb's, images, comments, etc.
	 */
	if (($settings->get('retention') > 0) && (!$retroMode)) {
		switch ($settings->get('retentiontype')) {
			case 'everything'		: {
				$db->deleteSpotsRetention($settings->get('retention'));
			} # case everything

			case 'fullonly'			: {
				$db->expireCache($settings->get('retention'));
				$db->expireCommentsFull($settings->get('retention'));
				$db->expireSpotsFull($settings->get('retention'));
			} # case fullonly
		} # switch
	} # if

	$newSpotCount = 0;
	$newCommentCount = 0;
	$newReportCount = 0;
	$retriever = null;

	## Spots
	/*
	 * Actually retrieve spots from the server
	 */
	$retriever = new SpotRetriever_Spots($settings_nntp_hdr, 
										 $settings_nntp_bin, 
										 $db, 
										 $settings,										 
										 $debugLog,
										 $retroMode);
	$newSpotCount = $retriever->perform();

	## Creating filter counts
	if ($newSpotCount > 0) {
		$spotsOverview = new SpotsOverview($db, $settings);
		echo 'Calculating how many spots are new';
		$notifyNewArray = $spotsOverview->cacheNewSpotCount();
		echo ', done.' . PHP_EOL;
	} # if

	/*
	 * Should we retrieve comments?
	 */
	if ($settings->get('retrieve_comments')) {
		$retriever = new SpotRetriever_Comments($settings_nntp_hdr, 
										 		$settings_nntp_bin, 
												$db,
												$settings,
												$debugLog,
												$retroMode);
		$newCommentCount = $retriever->perform();
	} # if

	/*
	 * Retrieval of reports
	 */
	if ($settings->get('retrieve_reports') && !$retroMode) {
		$retriever = new SpotRetriever_Reports($settings_nntp_hdr, 
											   $settings_nntp_bin,
											   $db,
											   $settings,
											   $debugLog);
		$newReportCount = $retriever->perform();
	} # if
	
	/*
	 * SpotStateList cleanup
	 */
	$db->cleanSpotStateList();

	if (!$retroMode) {
		$db->expireCache(30);
	} # if


	## External blacklist
	$settings_external_blacklist = $settings->get('external_blacklist');
	if ($settings_external_blacklist) {
		try {
			$spotsOverview = new SpotsOverview($db, $settings);
			# haal de blacklist op
			list($http_code, $blacklist) = $spotsOverview->getFromWeb($settings->get('blacklist_url'), false, 30*60);

			if ($http_code == 304) {
				echo "Blacklist not modified, no need to update" . PHP_EOL;
			} elseif (strpos($blacklist,">")) {
				echo "Error, blacklist does not have expected layout!" . PHP_EOL;
			} else {
				# update de blacklist
				$blacklistarray = explode(chr(10),$blacklist);
				
				# Perform a very small snaity check on the blacklist
				if ((count($blacklistarray) > 5) && (strlen($blacklistarray[0]) < 10)) {
					$updateblacklist = $db->updateExternallist($blacklistarray, SpotDb::spotterlist_Black);
					echo "Finished updating blacklist. Added " . $updateblacklist['added'] . ", removed " . $updateblacklist['removed'] . ", skipped " . $updateblacklist['skipped'] . " of " . count($blacklistarray) . " lines." . PHP_EOL;
				} else {
					echo "Blacklist is probably corrupt, skipping" . PHP_EOL;
				} # else				
			}
		} catch(Exception $x) {
			echo "Fatal error occured while updating blacklist:" . PHP_EOL;
			echo "  " . $x->getMessage() . PHP_EOL;
			echo PHP_EOL . PHP_EOL;
			echo $x->getTraceAsString();
			echo PHP_EOL . PHP_EOL;
		}
	} # if

	## External whitelist
	$settings_external_whitelist = $settings->get('external_whitelist');
	if ($settings_external_whitelist) {
		try {
			$spotsOverview = new SpotsOverview($db, $settings);
			# haal de whitelist op
			list($http_code, $whitelist) = $spotsOverview->getFromWeb($settings->get('whitelist_url'), false, 30*60);

			if ($http_code == 304) {
				echo "Whitelist not modified, no need to update" . PHP_EOL;
			} elseif (strpos($whitelist,">")) {
				echo "Error, whitelist does not have expected layout!" . PHP_EOL;
			} else {
				# update de whitelist
				$whitelistarray = explode(chr(10),$whitelist);
				
				# Perform a very small snaity check on the whitelist
				if ((count($whitelistarray) > 5) && (strlen($whitelistarray[0]) < 10)) {
					$updatewhitelist = $db->updateExternallist($whitelistarray, SpotDb::spotterlist_White);
					echo "Finished updating whitelist. Added " . $updatewhitelist['added'] . ", removed " . $updatewhitelist['removed'] . ", skipped " . $updatewhitelist['skipped'] . " of " . count($whitelistarray) . " lines." . PHP_EOL;
				} else {
					echo "Whitelist is probably corrupt, skipping" . PHP_EOL;
				} # else				
			}
		} catch(Exception $x) {
			echo "Fatal error occured while updating whitelist:" . PHP_EOL;
			echo "  " . $x->getMessage() . PHP_EOL;
			echo PHP_EOL . PHP_EOL;
			echo $x->getTraceAsString();
			echo PHP_EOL . PHP_EOL;
		}
	} # if

	## Statistics
	if ($settings->get('prepare_statistics') && $newSpotCount > 0) {
		$svcPrv_Stats = new Services_Providers_Statistics($db->_spotDao,
														  $db->_cacheDao,
											 			  $db->_nntpConfigDao->getLastUpdate($settings_nntp_hdr['host']));

		echo "Starting to create statistics " . PHP_EOL;
		foreach ($svcPrv_Stats->getValidStatisticsLimits() as $limitValue => $limitName) {
			# Reset timelimit
			set_time_limit(60);

			foreach ($svcPrv_Stats->getValidStatisticsGraphs() as $graphValue => $graphName) {
				$svcPrv_Stats->renderStatImage($graphValue, $limitValue, $settings_nntp_hdr['host']);
			} # foreach graph

			echo "Finished creating statistics " . $limitName . PHP_EOL;
		} # foreach limit

		echo PHP_EOL;
	} # if

	# Verstuur notificaties
	$spotsNotifications = new SpotNotifications($db, $settings, $userSession);
	if (!empty($notifyNewArray)) {
		foreach($notifyNewArray as $userId => $newSpotInfo) {
			foreach($newSpotInfo as $filterInfo) {
				if (($filterInfo['newcount'] > 0) && ($filterInfo['enablenotify'])) {
					$spotsNotifications->sendNewSpotsForFilter($userId, $filterInfo['title'], $filterInfo['newcount']);
				} # if
			} # foreach
		} # foreach
	} # if
	$spotsNotifications->sendRetrieverFinished($newSpotCount, $newCommentCount, $newReportCount);

	if ($req->getDef('output', '') == 'xml') {
		echo "</xml>";
	} # if
}

catch(RetrieverRunningException $x) {
       echo PHP_EOL . PHP_EOL;
       echo "retriever.php is already running, pass '--force' to ignore this warning." . PHP_EOL;
}

catch(NntpException $x) {
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while connecting to the newsserver:" . PHP_EOL;
	echo "  (" . $x->getCode() . ") " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
}

catch(DatabaseConnectionException $x) {
	echo "Unable to connect to database: " . $x->getMessage() . PHP_EOL;
} # catch

catch(InvalidOwnSettingsSettingException $x) {
	echo "There is an error in your ownsettings.php" . PHP_EOL . PHP_EOL;
	echo $x->getMessage() . PHP_EOL;
} # InvalidOwnSettingsSetting

catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving reports:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch
