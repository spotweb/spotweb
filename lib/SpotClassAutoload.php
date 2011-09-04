<?php

function __autoload($class_name) {
	$classType = substr($class_name, 0, stripos($class_name, '_'));
	
	switch($classType) {
		case 'SpotPage'		: require_once 'lib/page/' . $class_name . '.php'; break;
		case 'SpotStruct'	: require_once 'lib/dbstruct/' . $class_name . '.php'; break;
		case 'SpotRetriever': require_once 'lib/retriever/' . $class_name . '.php'; break;
		case 'dbeng'		: require_once 'lib/dbeng/' . $class_name . '.php'; break;
		case 'NzbHandler'	: require_once 'lib/nzbhandler/' . $class_name . '.php'; break;
		case 'Notifications': require_once 'lib/notifications/' . $class_name . '.php'; break;
		case 'Crypt'		: break; /* Crypt/Random.php gebruikt class_exist om een random generator te zoeken, welke autoload triggered */
		case 'SpotUbb'		: {
				require_once "lib/ubb/SpotUbb_parser.php";
				require_once "lib/ubb/TagHandler.inc.php";
				break;
		} # ubb
		default				: {
			# Exceptions beginnen niet met Exception, dus maken we daar een apart gevalletje van
			$isException = substr($class_name, -1 * strlen('Exception')) == 'Exception';
			if ($isException) {
				require_once "lib/exceptions/" . $class_name . ".php";
				return ;
			} # if
			
			# 
			# FIXME
			# Hack om tijdelijk om issue 967 te werken
			#
			if (is_numeric(substr($class_name, 0, 3))) {
				return ;
			} # if

			require_once 'lib/' . $class_name . '.php';
		} # default
	} # switch
} # __autoload
