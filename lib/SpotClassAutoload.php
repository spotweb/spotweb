<?php

function __autoload($class_name) {
	$classType = substr($class_name, 0, stripos($class_name, '_'));
	
	switch($classType) {
		case 'SpotPage'		: require_once 'lib/page/' . $class_name . '.php'; break;
		case 'SpotStruct'	: require_once 'lib/dbstruct/' . $class_name . '.php'; break;
		case 'SpotRetriever': require_once 'lib/retriever/' . $class_name . '.php'; break;
		case 'dbeng'		: require_once 'lib/dbeng/' . $class_name . '.php'; break;
		case 'dbfts'		: require_once 'lib/dbeng/' . $class_name . '.php'; break;
		case 'NzbHandler'	: require_once 'lib/nzbhandler/' . $class_name . '.php'; break;
		case 'Notifications': require_once 'lib/notifications/' . $class_name . '.php'; break;
		case 'Gettext'		: require_once 'lib/gettext/' . $class_name . '.php'; break;
		case 'Crypt'		: break; /* Crypt/Random.php uses class_exist to find a random generator, this triggers autoload */
		case 'SpotUbb'		: {
				require_once "lib/ubb/SpotUbb_parser.php";
				require_once "lib/ubb/TagHandler.inc.php";
				break;
		} # ubb
		case 'Services'		: 
		case 'Dao'			: {
			$parts = explode("_", $class_name);

			if (count($parts) == 2) {
				require_once ('lib/' . strtolower($parts[0]) . '/' . $class_name . '.php');
			} else {
				require_once ('lib/' . strtolower($parts[0]) . '/' . $parts[1] . '/' . $class_name . '.php');
			} # else
			break;
		} # dao
		case 'Mobile'		: {
			if ($class_name == 'Mobile_Detect') {
				require_once "Mobile_Detect.php";
			} # if

			break;
		} # 'Mobile'
		case 'SpotTemplateHelper' : {
			$tpl_name = substr($class_name, strlen('SpotTemplateHelper_'));

			require_once "templates/" . strtolower($tpl_name) . "/" . "SpotTemplateHelper_" . ucfirst($tpl_name) . ".php";
		} # SpotTemplateHelper
		case 'Net'			: { 
			$class_name = substr($class_name, 4);
			
			if ($class_name == 'NNTP_Client') {
				require_once "NNTP/Client.php";
			} elseif ($class_name == 'NNTP_Protocol_Client') {
				require_once "NNTP/Protocol/Client.php";
			} # else			
			break;
		} # net
		case 'Math'			: {
			if ($class_name == 'Math_BigInteger') {
				require_once "Math/BigInteger.php";
			} # if
			
			break;
		} # Math
		default				: {
			# Exceptions do not start with the word 'Exception', so we special case that
			$isException = substr($class_name, -1 * strlen('Exception')) == 'Exception';
			if ($isException) {
				require_once "lib/exceptions/" . $class_name . ".php";
				return ;
			} # if

			require_once 'lib/' . $class_name . '.php';
		} # default
	} # switch
} # __autoload
