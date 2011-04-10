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
require_once "lib/SpotUser.php";
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
require_once "lib/page/SpotPage_atom.php";
require_once "lib/page/SpotPage_statics.php";
require_once "lib/page/SpotPage_render.php";

#- main() -#
try {
	# database object
	$db = new SpotDb($settings['db']);
	$db->connect();

	# Controleer eerst of het schema nog wel geldig is
	if (!$db->schemaValid()) {
		die("Database schema is gewijzigd, draai upgrade-db.php aub" . PHP_EOL);
	} # if
	
	# Haal het userobject op dat 'ingelogged' is
	$spotUser = new SpotUser($db, $settings);
	$currentUser = $spotUser->auth('anonymous', '');
	
	# helper functions for passed variables
	$req = new SpotReq();
	$req->initialize();
	$page = $req->getDef('page', 'index');
		
	switch($page) {
		case 'render' : {
				$page = new SpotPage_render($db, $settings, $currentUser, $req->getDef('tplname', ''),
							Array('search' => $req->getDef('search', $settings['index_filter']),
								  'messageid' => $req->getDef('messageid', ''),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', '')));

				$page->render();
				break;
		} # render
		
		case 'getspot' : {
				$page = new SpotPage_getspot($db, $settings, $currentUser, $req->getDef('messageid', ''));
				$page->render();
				break;
		} # getspot

		case 'getnzb' : {
				$page = new SpotPage_getnzb($db, $settings, $currentUser, 
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display')));
				$page->render();
				break;
		}
		
		case 'getspotmobile' : {
				$page = new SpotPage_getspotmobile($db, $settings, $currentUser, $req->getDef('messageid', ''));
				$page->render();
				break;
		} # getspotmobile

		case 'getnzbmobile' : {
				$page = new SpotPage_getnzbmobile($db, $settings, $currentUser,
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display')));
				$page->render();
				break;
		} # getnzbmobile		

		case 'erasedls' : {
				$page = new SpotPage_erasedls($db, $settings, $currentUser);
				$page->render();
				break;
		} # erasedls
		
		case 'catsjson' : {
				$page = new SpotPage_catsjson($db, $settings, $currentUser);
				$page->render();
				break;
		} # getspot
		
		case 'markallasread' : {
				$page = new SpotPage_markallasread($db, $settings, $currentUser);
				$page->render();
				break;
		} # markallasread

		case 'getimage' : {
			$page = new SpotPage_getimage($db, $settings, $currentUser,
								Array('messageid' => $req->getDef('messageid', ''),
									  'image' => $req->getDef('image', Array())));
			$page->render();
			break;
		}

		case 'selecttemplate' : {
				$page = new SpotPage_selecttemplate($db, $settings, $currentUser, $req);
				$page->render();
				break;
		} # selecttemplate

		case 'atom' : {
			$page = new SpotPage_atom($db, $settings, $currentUser,
					Array('search' => $req->getDef('search', $settings['index_filter']),
						  'page' => $req->getDef('page', 0),
						  'sortby' => $req->getDef('sortby', ''),
						  'sortdir' => $req->getDef('sortdir', ''))
			);
			$page->render();
			break;
		} # atom
		
		case 'statics' : {
				$page = new SpotPage_statics($db, $settings, $currentUser,
							Array('type' => $req->getDef('type', '')));
				$page->render();
				break;
		} # statics

		default : {
				$page = new SpotPage_index($db, $settings, $currentUser,
							Array('search' => $req->getDef('search', $settings['index_filter']),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', ''),
								  'messageid' => $req->getDef('messageid', ''),
								  'action' => $req->getDef('action', ''))
					);
				$page->render();
				break;
		} # default
	} # switch
}
catch(Exception $x) {
	die($x->getMessage());
} # catch
