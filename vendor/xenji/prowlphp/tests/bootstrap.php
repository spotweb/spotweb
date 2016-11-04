<?php

set_include_path(implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__) . '/../src', get_include_path())));

spl_autoload_extensions(".php");
spl_autoload_register(function($sClassName){
	require_once str_replace('\\', "/",$sClassName) . ".php";
});
