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

	require_once "lib/SpotClassAutoload.php";
    SpotClassAutoload::register();

	/*
	 * Initialize the Spotweb base classes
	 */
	$bootstrap = new Bootstrap();
	list($settings, $daoFactory, $req) = $bootstrap->boot();

	/*
	 * disable timing, all queries which are ran by retrieve this would make it use
	 * large amounts of memory
	 */
	SpotTiming::disable();

	# Initialize commandline arguments
	SpotCommandline::initialize(array('force', 'debug', 'retro', 'timing'), array('force' => false, 'timing' => false, 'debug' => false, 'retro' => false));

    # Allow for timing to be displayed after retrieval of spots
    $showTiming = SpotCommandline::get('timing');
    if ($showTiming) {
        SpotTiming::enable();
        SpotTiming::enableHtml(false);
        SpotTiming::disableExtra(true);
    } # if

    # Initialize translation to english
	SpotTranslation::initialize('en_US');

	/*
	 * When PHP is running in safe mode, max execution time cannot be set,
	 * which is necessary on slow systems for retrieval and statistics generation
	 */
	if (ini_get('safe_mode')) {
		echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems" . PHP_EOL . PHP_EOL;
	} # if

	/*
	 * When retrieval is run from the webinterface, we want to make
	 * sure this user is actually allowed to run retrieval.
	 */
	$svcUserRecord = new Services_User_Record($daoFactory, $settings);
	$svcUserAuth = new Services_User_Authentication($daoFactory, $settings);
	if (!SpotCommandline::isCommandline()) {
		/*
		 * An API key is required, so request it and try to
		 * create a session with it which we can use to validate
		 * the user with
		 */
		$apiKey = $req->getDef('apikey', '');
		$userSession = $svcUserAuth->verifyApi($apiKey);

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
		$userSession['user'] = $svcUserRecord->getUser(SPOTWEB_ADMIN_USERID);
		$userSession['security'] = new SpotSecurity($daoFactory->getUserDao(),
													$daoFactory->getAuditDao(),
													$settings, 
													$userSession['user'], 
													'');
		$userSession['session'] = array('ipaddr' => '');
	} # if

	/*
	 * We normally check whether we are not running already, because
	 * this would mean it will mess up all sorts of things like
	 * comment calculation, but a user can force our hand
	 */
	$forceMode = SpotCommandline::get('force');

	/*
	 * Do we need to debuglog this session? Generates loads of
	 * output
	 */
	$debugLog = SpotCommandline::get('debug');
    if ($debugLog) {
        SpotDebug::enable(SpotDebug::TRACE, $daoFactory->getDebugLogDao());
    } else {
        SpotDebug::disable();
    } # if

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
        echo "Removing Spot information which is beyond retention period,";

		$spotDao = $daoFactory->getSpotDao();
        $cacheDao = $daoFactory->getCacheDao();
        $commentDao = $daoFactory->getCommentDao();

		switch ($settings->get('retentiontype')) {
			case 'everything'		: {
				$spotDao->deleteSpotsRetention($settings->get('retention'));
                $cacheDao->expireCache($settings->get('retention'));
			} # case everything

			case 'fullonly'			: {
				$cacheDao->expireCache($settings->get('retention'));
				$commentDao->expireCommentsFull($settings->get('retention'));
				$spotDao->expireSpotsFull($settings->get('retention'));
			} # case fullonly
		} # switch

        echo ", done" . PHP_EOL;
	} # if

	$newSpotCount = 0;
	$newCommentCount = 0;
	$newReportCount = 0;
	$retriever = null;

	## Spots
	/*
	 * Actually retrieve spots from the server
	 */
	$retriever = new Services_Retriever_Spots($daoFactory, 
											  $settings,
											  $forceMode,
											  $retroMode);
	$newSpotCount = $retriever->perform();

    # Show the cumulative timings of the spotsretrieval
    if ($showTiming) {
        SpotTiming::displayCumul();
        SpotTiming::clear();
    } # if

    ## Creating filter counts
	if ($newSpotCount > 0) {
		$svcPrv_cacheSpotCount = new Services_Actions_CacheNewSpotCount($daoFactory->getUserFilterCountDao(),
																		  $daoFactory->getUserFilterDao(),
																		  $daoFactory->getSpotDao(),
																		  new Services_Search_QueryParser($daoFactory->getConnection()));
		echo 'Calculating how many spots are new';
		$notifyNewArray = $svcPrv_cacheSpotCount->cacheNewSpotCount();
		echo ', done.' . PHP_EOL;

        # Show the cumulative timings of the caching of these spots
        if ($showTiming) {
            SpotTiming::displayCumul();
            SpotTiming::clear();
        } # if
	} # if


    /*
     * Should we retrieve comments?
     */
	if ($settings->get('retrieve_comments')) {
		$retriever = new Services_Retriever_Comments($daoFactory,
													 $settings,
													 $forceMode,
													 $retroMode);
		$newCommentCount = $retriever->perform();

        # Show the cumulative timings of the caching of these comments
        if ($showTiming) {
            SpotTiming::displayCumul();
            SpotTiming::clear();
        } # if
    } # if


	/*
	 * Retrieval of reports
	 */
	if ($settings->get('retrieve_reports') && !$retroMode) {
		$retriever = new Services_Retriever_Reports($daoFactory,
												    $settings,
												    $forceMode,
                                                    $retroMode);
		$newReportCount = $retriever->perform();

        # Show the cumulative timings of the caching of these reports
        if ($showTiming) {
            SpotTiming::displayCumul();
            SpotTiming::clear();
        } # if
	} # if
	
	/*
	 * SpotStateList cleanup
	 */
	$daoFactory->getSpotStateListDao()->cleanSpotStateList();

	## External blacklist
    if ($settings->get('external_blacklist')) {
        $svcBwListRetriever = new Services_BWList_Retriever($daoFactory->getBlackWhiteListDao(), $daoFactory->getCacheDao());
        $bwResult = $svcBwListRetriever->retrieveBlackList($settings->get('blacklist_url'));
        if ($bwResult === false) {
            echo "Blacklist not modified, no need to update" . PHP_EOL;
        } else {
            echo "Finished updating blacklist. Added " . $bwResult['added'] . ", removed " . $bwResult['removed'] . ", skipped " . $bwResult['skipped'] . " of " . $bwResult['total'] . " lines." . PHP_EOL;
        } # else
    } # if

	## External whitelist
    if ($settings->get('external_whitelist')) {
        $bwResult = $svcBwListRetriever->retrieveWhiteList($settings->get('whitelist_url'));
        if ($bwResult === false) {
            echo "Whitelist not modified, no need to update" . PHP_EOL;
        } else {
            echo "Finished updating whitelist. Added " . $bwResult['added'] . ", removed " . $bwResult['removed'] . ", skipped " . $bwResult['skipped'] . " of " . $bwResult['total'] . " lines." . PHP_EOL;
        } # else
    } # if

    ## Remove expired debuglogs
    echo "Expiring debuglog entries, if any, ";
    $daoFactory->getDebugLogDao()->expire();
    echo "done. " . PHP_EOL;

	## Statistics
	if ($settings->get('prepare_statistics') && $newSpotCount > 0) {
		if (extension_loaded('gd') || extension_loaded('gd2')) {
			$settings_nntp_hdr = $settings->get('nntp_hdr');
			$svcPrv_Stats = new Services_Providers_Statistics($daoFactory->getSpotDao(),
															  $daoFactory->getCachedao(),
												 			  $daoFactory->getUsenetStateDao()->getLastUpdate(Dao_UsenetState::State_Spots));

			echo "Starting to create statistics " . PHP_EOL;
			$svcPrv_Stats->createAllStatistics();
			echo "Finished creating statistics " . PHP_EOL;
			echo PHP_EOL;
		} else {
			echo "GD extension not loaded, not creating statistics" . PHP_EOL;
		} # else
	} # if

	# Verstuur notificaties
	$spotsNotifications = new SpotNotifications($daoFactory, $settings, $userSession);
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
