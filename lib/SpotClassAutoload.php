<?php

function __autoload($class_name) {
	$classType = substr($class_name, 0, stripos($class_name, '_'));
	
	switch($classType) {
		case 'SpotPage'		: require 'lib/page/' . $class_name . '.php'; break;
		case 'SpotStruct'	: require 'lib/dbstruct/' . $class_name . '.php'; break;
		case 'SpotRetriever': require 'lib/retriever/' . $class_name . '.php'; break;
		case 'dbeng'		: require 'lib/dbeng/' . $class_name . '.php'; break;
		case 'dbfts'		: require 'lib/dbeng/' . $class_name . '.php'; break;
		case 'Notifications': require 'lib/notifications/' . $class_name . '.php'; break;
		case 'Gettext'		: require 'lib/gettext/' . $class_name . '.php'; break;
		case 'SpotUbb'		: {
				require "vendor/ubb/SpotUbb_parser.php";
				require "vendor/ubb/TagHandler.inc.php";
				break;
		} # ubb
		case 'Services'		: 
		case 'Dto'			: 
		case 'Dao'			: {
			$parts = explode("_", $class_name);

			if (count($parts) == 2) {
				require ('lib/' . strtolower($parts[0]) . '/' . $class_name . '.php');
			} else {
				require ('lib/' . strtolower($parts[0]) . '/' . $parts[1] . '/' . $class_name . '.php');
			} # else
			break;
		} # dao
		case 'Mobile'		: {
			if ($class_name == 'Mobile_Detect') {
				require "vendor/Mobile_Detect/Mobile_Detect.php";
			} # if

			break;
		} # 'Mobile'
		case 'SpotTemplateHelper' : {
			$tpl_name = substr($class_name, strlen('SpotTemplateHelper_'));

			require "templates/" . strtolower($tpl_name) . "/" . "SpotTemplateHelper_" . ucfirst($tpl_name) . ".php";
            break;
		} # SpotTemplateHelper
		case 'Net'			: { 
			$class_name = substr($class_name, 4);
			
			if ($class_name == 'NNTP_Client') {
				require "NNTP/Client.php";
			} elseif ($class_name == 'NNTP_Protocol_Client') {
				require "NNTP/Protocol/Client.php";
			} # else			
			break;
		} # net
        case 'Crypt'		: {
            require 'vendor/phpseclib/Crypt/Hash.php';
            require 'vendor/phpseclib/Crypt/Random.php';
            require 'vendor/phpseclib/Crypt/RSA.php';

            break;
        }
        case 'Math'			: {
            if ($class_name == 'Math_BigInteger') {
                require "vendor/phpseclib/Math/BigInteger.php";
            } # if

            break;
        } # Math
        case 'parse_model'		: {
            require "vendor/fts_parser2/parse_model.php";
            break;
        } # 'Mobile'
		default				: {
			# Exceptions do not start with the word 'Exception', so we special case that
			$isException = substr($class_name, -1 * strlen('Exception')) == 'Exception';
			if ($isException) {
				require "lib/exceptions/" . $class_name . ".php";
				return ;
			} # if

            if ($class_name == 'parse_model') {
                require "vendor/fts_parser2/parse_model.php";
                return ;
            } # if

			if ($class_name == 'Registry') {
				require "vendor/Lim_Registry/Registry.php";
				return ;
			} # if

			require 'lib/' . $class_name . '.php';
		} # default
	} # switch
} # __autoload
