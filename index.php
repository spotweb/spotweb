<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet
session_start();

require_once "settings.php";
require_once "lib/SpotCookie.php";
require_once "lib/SpotDb.php";
require_once "lib/SpotReq.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotsOverview.php";
require_once "lib/SpotCategories.php";
require_once "lib/SpotNntp.php";
require_once "lib/SpotCookie.php";
require_once "lib/page/SpotPage_index.php";
require_once "lib/page/SpotPage_getnzb.php";
require_once "lib/page/SpotPage_getnzbmobile.php";
require_once "lib/page/SpotPage_getspot.php";
require_once "lib/page/SpotPage_catsjson.php";
require_once "lib/page/SpotPage_erasedls.php";
require_once "lib/page/SpotPage_getimage.php";
require_once "lib/page/SpotPage_getspotmobile.php";
require_once "lib/page/SpotPage_markallasread.php";
require_once "lib/page/SpotPage_getimage.php";
require_once "lib/page/SpotPage_selecttemplate.php";
#- main() -#
try {
	# database object
	$db = new SpotDb($settings['db']);
	$db->connect();
	
	# helper functions for passed variables
	$req = new SpotReq();
	$req->initialize();

	$page = $req->getDef('page', 'index');
	if (array_search($page, array('index', 'catsjson', 'getnzb', 'getnzbmobile','getspotmobile','getspot', 'erasedls', 'markallasread', 'getimage', 'selecttemplate')) === false) {
		$page = 'index';
	} # if

	switch($page) {
		case 'getspot' : {
				$page = new SpotPage_getspot($db, $settings, $settings['prefs'], $req->getDef('messageid', ''));
				$page->render();
				break;
		} # getspot

		case 'getnzb' : {
				$page = new SpotPage_getnzb($db, $settings, $settings['prefs'], 
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display')));
				$page->render();
				break;
		}
		
		case 'getspotmobile' : {
				$page = new SpotPage_getspotmobile($db, $settings, $settings['prefs'], $req->getDef('messageid', ''));
				$page->render();
				break;
		} # getspotmobile

		case 'getnzbmobile' : {
				$page = new SpotPage_getnzbmobile($db, $settings, $settings['prefs'], 
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display')));
				$page->render();
				break;
		} # getnzbmobile		

		case 'erasedls' : {
				$page = new SpotPage_erasedls($db, $settings, $settings['prefs']);
				$page->render();
				break;
		} # erasedls
		
		case 'catsjson' : {
				$page = new SpotPage_catsjson($db, $settings, $settings['prefs']);
				$page->render();
				break;
		} # getspot
		
		case 'markallasread' : {
				$page = new SpotPage_markallasread($db, $settings, $settings['prefs']);
				$page->render();
				break;
		} # markallasread

		case 'getimage' : {
			$page = new SpotPage_getimage($db, $settings, $settings['prefs'], 
								Array('messageid' => $req->getDef('messageid', ''),
									  'image' => $req->getDef('image', Array())));
			$page->render();
			break;
		}

		case 'selecttemplate' : {
				$page = new SpotPage_selecttemplate($db, $settings, $settings['prefs'], $req);
				$page->render();
				break;
		} # selecttemplate
		
		case 'index' : {
				$page = new SpotPage_index($db, $settings, $settings['prefs'], 
							Array('search' => $req->getDef('search', $settings['index_filter']),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', ''))
					);
				$page->render();
				break;
		} # getspot
	} # switch
}
catch(Exception $x) {
	die($x->getMessage());
} # catch
