<?php
error_reporting(2147483647);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

#- main() -#
try {
	SpotTranslation::initialize('en_US');
	
	SpotTiming::enable();
	SpotTiming::start('total');

    /*
     * Initialize the Spotweb base classes
     */
	$bootstrap = new Bootstrap();
	list($settings, $daoFactory, $req) = $bootstrap->boot();

    /*
     * Enable debug logging mechanism if timing is enabled
     */
    if ($settings->get('enable_timing')) {
        SpotDebug::enable(SpotDebug::TRACE, $daoFactory->getDebugLogDao());
    } # if

	# helper functions for passed variables
	$page = $req->getDef('page', 'index');

	# Retrieve the users object of the user which is logged on
	SpotTiming::start('auth');
	$svcUserAuth = new Services_User_Authentication($daoFactory, $settings);
	if ($req->doesExist('apikey')) {
		$currentSession = $svcUserAuth->verifyApi($req->getDef('apikey', ''));
	} else {
		$currentSession = $svcUserAuth->useOrStartSession(false);
	} # if

    /*
     * If three is no user object, we don't have a security system
     * either. Without a security system we cannot boot, so fatal
     */
	if ($currentSession === false) {
		if ($req->doesExist('apikey')) {
			$currentSession = $svcUserAuth->useOrStartSession(true);
			
			throw new PermissionDeniedException(SpotSecurity::spotsec_consume_api, 'invalid API key');
		} else {
			throw new SqlErrorException("Unable to create session");
		} # else
	} # if
	SpotTiming::stop('auth');

	/*
	 * And check if the security groups need updating
	 */
	if (!$currentSession['security']->securityValid()) {
		throw new SecurityNotUpgradedException();
	} # if
	
	# User session has been loaded, let's translate the categories
	if ($currentSession['user']['prefs']['user_language'] != 'en_US') {
		SpotTranslation::initialize($currentSession['user']['prefs']['user_language']);
	} # if
	SpotCategories::startTranslation();


	/*
	 * Let the form handler know what userid we are using so
	 * we can make the CSRF cookie be user-bounded
	 */
	$req->setUserId($currentSession['user']['userid']);

	/*
	 * Only now it is safe to check wether the user is actually allowed
	 * to authenticate with an API key 
	 */
	if ($req->doesExist('apikey')) {
		/*
		 * To use the Spotweb API we need the actual permission
		 */
		$currentSession['security']->fatalPermCheck(SpotSecurity::spotsec_consume_api, '');

		/*
		 * but we also need a specific permission, because else things could
		 * be automated which we simply do not want to be automated
		 */
		$currentSession['security']->fatalPermCheck(SpotSecurity::spotsec_consume_api, $page);
	} # if

	SpotTiming::start('renderpage');
	switch($page) {
		case 'render' : {
                $svcUserFilters = new Services_User_Filters($daoFactory, $settings);
    			$page = new SpotPage_render($daoFactory, $settings, $currentSession, $req->getDef('tplname', ''),
							Array('search' => $req->getDef('search', $svcUserFilters->getIndexFilter($currentSession['user']['userid'])),
								  'data' => $req->getDef('data', array()),
								  'messageid' => $req->getDef('messageid', ''),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'perpage' => $req->getDef('perpage', 10),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', '')));

				$page->render();
				break;
		} # render

		case 'getspot' : {
				if (strpos($_SERVER['HTTP_USER_AGENT'], "SABnzbd+") === 0) {
					$page = new SpotPage_getnzb($daoFactory, $settings, $currentSession,
						Array('messageid' => $req->getDef('messageid', ''),
							'action' => $req->getDef('action', 'display'),
							'username' => $req->getDef('username', ''),
							'apikey' => $req->getDef('apikey', '')));
				} else {
					$page = new SpotPage_getspot($daoFactory, $settings, $currentSession, array('messageid' => $req->getDef('messageid', '')));
				} # else
				$page->render();
				break;
		} # getspot

		case 'getnzb' : {
				$page = new SpotPage_getnzb($daoFactory, $settings, $currentSession,
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display'),
									  'username' => $req->getDef('username', ''),
									  'apikey' => $req->getDef('apikey', '')));
				$page->render();
				break;
		}

		case 'getnzbmobile' : {
				$page = new SpotPage_getnzbmobile($daoFactory, $settings, $currentSession,
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display')));
				$page->render();
				break;
		} # getnzbmobile

		case 'erasedls' : {
				$page = new SpotPage_erasedls($daoFactory, $settings, $currentSession);
				$page->render();
				break;
		} # erasedls

		case 'catsjson' : {
                $svcUserFilters = new Services_User_Filters($daoFactory, $settings);
                $page = new SpotPage_catsjson(
                                        $daoFactory,
                                        $settings,
                                        $currentSession,
                                        Array('search' => $req->getDef('search', $svcUserFilters->getIndexFilter($currentSession['user']['userid'])),
                                              'subcatz' => $req->getDef('subcatz', '*'),
                                              'category' => $req->getDef('category', '*'),
                                              'rendertype' => $req->getDef('rendertype', 'tree'),
                                              'disallowstrongnot' => $req->getDef('disallowstrongnot', '')));
                    $page->render();
                    break;
        } # catsjson

		case 'markallasread' : {
				$page = new SpotPage_markallasread($daoFactory, $settings, $currentSession);
				$page->render();
				break;
		} # markallasread

		case 'getimage' : {
			$page = new SpotPage_getimage($daoFactory, $settings, $currentSession,
								Array('messageid' => $req->getDef('messageid', ''),
									  'image' => $req->getDef('image', Array())));
			$page->render();
			break;
		}
		case 'newznabapi' : {
			$page = new SpotPage_newznabapi($daoFactory, $settings, $currentSession,
					Array('t' => $req->getDef('t', ''),
						  'messageid' => $req->getDef('id', ''),
						  'apikey' => $req->getDef('apikey', ''),
						  'q' => $req->getDef('q', ''),
						  'limit' => $req->getDef('limit', ''),
						  'cat' => $req->getDef('cat', ''),
						  'imdbid' => $req->getDef('imdbid', ''),
						  'artist' => $req->getDef('artist', ''),
						  'rid' => $req->getDef('rid', ''),
						  'tvmazeid' => $req->getDef('tvmazeid', ''),
						  'season' => $req->getDef('season', ''),
						  'ep' => $req->getDef('ep', ''),
						  'o' => $req->getDef('o', ''),
						  'extended' => $req->getDef('extended', ''),
						  'maxage' => $req->getDef('maxage', ''),
						  'offset' => $req->getDef('offset', ''),
						  'del' => $req->getDef('del', ''),
                          'spotcat' => $req->getDef('spotcat', ''),
                          'noalt' => $req->getDef('noalt', ''),
                          'poster' => $req->getDef('poster', '')
					)
			);
			$page->render();
			break;
		} # api

		case 'rss' : {
            $svcUserFilters = new Services_User_Filters($daoFactory, $settings);
			$page = new SpotPage_rss($daoFactory, $settings, $currentSession,
					Array('search' => $req->getDef('search', $svcUserFilters->getIndexFilter($currentSession['user']['userid'])),
						  'page' => $req->getDef('page', 0),
						  'sortby' => $req->getDef('sortby', ''),
						  'sortdir' => $req->getDef('sortdir', ''),
						  'username' => $req->getDef('username', ''),
						  'apikey' => $req->getDef('apikey', ''))
			);
			$page->render();
			break;
		} # rss

		case 'statics' : {
				$page = new SpotPage_statics($daoFactory, $settings, $currentSession,
							Array('type' => $req->getDef('type', '')));
				$page->render();
				break;
		} # statics

		case 'createuser' : {
				$page = new SpotPage_createuser($daoFactory, $settings, $currentSession,
							Array('createuserform' => $req->getForm('createuserform')));
				$page->render();
				break;
		} # createuser

		case 'editsettings' : {
				$page = new SpotPage_editsettings($daoFactory, $settings, $currentSession,
							Array('editsettingsform' => $req->getForm('editsettingsform')));
				$page->render();
				break;
		} # editsettings

		case 'edituserprefs' : {
				$page = new SpotPage_edituserprefs($daoFactory, $settings, $currentSession,
							Array('edituserprefsform' => $req->getForm('edituserprefsform'),
								  'userid' => $req->getDef('userid', ''),
								  'data' => $req->getDef('data', array()),
								  'dialogembedded' => $req->getDef('dialogembedded', 0)));
				$page->render();
				break;
		} # edituserprefs

		case 'editsecgroup' : {
				$page = new SpotPage_editsecgroup($daoFactory, $settings, $currentSession,
							Array('editsecgroupform' => $req->getForm('editsecgroupform'),
							      'groupid' => $req->getDef('groupid', 0)));
				$page->render();
				break;
		} # editsecgroup

		case 'editfilter' : {
				$page = new SpotPage_editfilter($daoFactory, $settings, $currentSession,
							Array('editfilterform' => $req->getForm('editfilterform'),
								  'orderfilterslist' => $req->getDef('orderfilterslist', array()),
								  'search' => $req->getDef('search', array()),
								  'sorton' => $req->getDef('sortby', ''),
								  'sortorder' => $req->getDef('sortdir', ''),
							      'filterid' => $req->getDef('filterid', 0),
							      'data' => $req->getDef('data', array())));
				$page->render();
				break;
		} # editfilter

		case 'edituser' : {
				$page = new SpotPage_edituser($daoFactory, $settings, $currentSession,
							Array('edituserform' => $req->getForm('edituserform'),
								  'userid' => $req->getDef('userid', '')));
				$page->render();
				break;
		} # edituser

		case 'editspot' : {
			$page = new SpotPage_editspot($daoFactory, $settings, $currentSession,
							Array('editspotform' => $req->getForm('editspotform'),
								  'messageid' => $req->getDef('messageid', '')));
			$page->render();
			break;
		} # editspot

		case 'login' : {
				$page = new SpotPage_login($daoFactory, $settings, $currentSession,
							Array('loginform' => $req->getForm('loginform'),
							      'data' => $req->getDef('data', array())));
				$page->render();
				break;
		} # login

		case 'postcomment' : {
				$page = new SpotPage_postcomment($daoFactory, $settings, $currentSession,
							Array('commentform' => $req->getForm('postcommentform'),
								  'inreplyto' => $req->getDef('inreplyto', '')));
				$page->render();
				break;
		} # postcomment

		case 'postspot' : {
				$page = new SpotPage_postspot($daoFactory, $settings, $currentSession,
							Array('spotform' => $req->getForm('newspotform')));
				$page->render();
				break;
		} # postspot
		
		case 'reportpost' : {
				$page = new SpotPage_reportpost($daoFactory, $settings, $currentSession, 
							Array ('reportform' => $req->getForm('postreportform'),
								   'inreplyto' => $req->getDef('inreplyto', '')));
				$page->render();
				break;
		} # reportpost

		case 'versioncheck' : {
				$page = new SpotPage_versioncheck($daoFactory, $settings, $currentSession, array());
				$page->render();
				break;
		} # versioncheck

		case 'blacklistspotter' : {
				$page = new SpotPage_blacklistspotter($daoFactory, $settings, $currentSession, 
							Array ('blform' => $req->getForm('blacklistspotterform')));
				$page->render();
				break;
		} # blacklistspotter
		
		case 'logout' : {
				$page = new SpotPage_logout($daoFactory, $settings, $currentSession);
				$page->render();
				break;
		} # logout

		case 'nzbhandlerapi' : {
			$page = new SpotPage_nzbhandlerapi($daoFactory, $settings, $currentSession,
							Array('nzbhandlerapikey' => $req->getDef('nzbhandlerapikey', ''),
								   'action' => $req->getDef('action', ''),
								   'limit' => $req->getDef('limit', ''),
								   'id' => $req->getDef('id', ''),
								   'category' => $req->getDef('category', ''),
								   'priority' => $req->getDef('priority', ''),
								   'password' => $req->getDef('password', ''),
								   'name' => $req->getDef('name', ''))
						);
			$page->render();
			break;
		} # nzbhandlerapi
		
		case 'twitteroauth' : {
			$page = new SpotPage_twitteroauth($daoFactory, $settings, $currentSession,
					Array('action' => $req->getDef('action', ''),
						  'pin' => $req->getDef('pin', '')));
			$page->render();
			break;
		} # twitteroauth

		case 'statistics' : {
			$page = new SpotPage_statistics($daoFactory, $settings, $currentSession,
					Array('limit' => $req->getDef('limit', '')));
			$page->render();
			break;
		} # statistics

		default : {
				SpotTiming::start('renderpage->case-default');
				if ((empty($_SERVER['HTTP_X_PURPOSE']) ? "" : $_SERVER['HTTP_X_PURPOSE']) == 'preview') {
					$page = new SpotPage_getimage($daoFactory, $settings, $currentSession,
							Array('messageid' => $req->getDef('messageid', ''),
								  'image' => array('type' => 'speeddial')));
				} else {
					$svcUserFilters = new Services_User_Filters($daoFactory, $settings);
					$page = new SpotPage_index($daoFactory, $settings, $currentSession,
							Array('search' => $req->getDef('search', $svcUserFilters->getIndexFilter($currentSession['user']['userid'])),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', ''),
								  'messageid' => $req->getDef('messageid', ''),
								  'action' => $req->getDef('action', ''),
								  'data'	=> $req->getDef('data', array()))
					);
				} # if
				SpotTiming::stop('renderpage->case-default');
				$page->render();
                break;
		} # default
	} # switch
	SpotTiming::stop('renderpage');

	# timing
	SpotTiming::stop('total');

	# enable or disable timer
	if (($settings->get('enable_timing')) && (!in_array($req->getDef('page', ''), array('catsjson', 'statics', 'getnzb', 'getnzbmobile', 'markallasread')))) {
		SpotTiming::display();
	} # if
}
catch(PermissionDeniedException $x) {
	/*
	 * We try to render a permission denied error using the already created
	 * renderer first. We do this, so pages which are supposed to output 
	 * XML, can also output their errors using XML.
	 *
	 * If no page is initiated just yet, we create an basic renderer object
	 * to render an error page
	 */	
	if (! ($page instanceof SpotPage_Abs)) {
		$page = new SpotPage_render($daoFactory, $settings, $currentSession, '', array());
	} # if
	
	$page->permissionDenied($x, $page, $req->getHttpReferer());
} # PermissionDeniedException

catch(InvalidOwnSettingsSettingException $x) {
	echo "There is an error in your ownsettings.php<br><br>" . PHP_EOL;
	echo nl2br($x->getMessage());
} # InvalidOwnSettingsSettingException

catch(OwnsettingsCreatedOutputException $x) {
	echo "ownsettings.php or dbsettings.inc.php created output. Please make sure theese files do not contain a PHP closing tag ( ?> ) and no information before the PHP opening tag ( <?php )<br><br>" . PHP_EOL;
	echo nl2br($x->getMessage()). PHP_EOL;
} # OwnsettingsCreatedOutputException

catch(SchemaNotUpgradedException $x) {
	echo "Database schema has been changed. Please run 'bin/upgrade-db.php' from an console window". PHP_EOL;
} # SchemaNotUpgradedException

catch(SecurityNotUpgradedException $x) {
	echo "Spotweb contains updated security settings. Please run 'bin/upgrade-db.php' from a console window". PHP_EOL;
} # SecurityNotUpgradedException

catch(SettingsNotUpgradedException $x) {
	echo "Spotweb contains updated global settings settings. Please run 'bin/upgrade-db.php' from a console window". PHP_EOL;
} # SecurityNotUpgradedException

catch(DatabaseConnectionException $x) {
	echo "Unable to connect to database:  <br>". PHP_EOL;
	echo nl2br($x->getMessage()) . PHP_EOL . '<br>'. PHP_EOL;
	echo "<br><br>Please make sure your database server is up and running and your connection parameters are set<br>" . PHP_EOL;
} # DatabaseConnectionException

catch(Exception $x) {
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL;
	if ((isset($settings) && is_object($settings) && $settings->get('enable_stacktrace')) || (!isset($settings))) { 
		var_dump($x);
	} # if
	echo $x->getMessage(). PHP_EOL;
	
	error_log('SpotWeb Exception occured: ' . $x->getMessage());
} # catch
