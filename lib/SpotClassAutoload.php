<?php

function __autoload($class_name) {
	$classType = substr($class_name, 0, stripos($class_name, '_'));
	switch($classType) {
		case 'SpotPage'		: require_once 'lib/page/' . $class_name . '.php'; break;
		case 'SpotStruct'	: require_once 'lib/dbstruct/' . $class_name . '.php'; break;
		case 'SpotRetriever': require_once 'lib/retriever/' . $class_name . '.php'; break;
		case 'dbeng'		: require_once 'lib/dbeng/' . $class_name . '.php'; break;
		case 'NzbHandler'	: require_once 'lib/nzbhandler/' . $class_name . '.php'; break;
		default				: require_once 'lib/' . $class_name . '.php';
	} # switch
} # __autoload
