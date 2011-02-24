<?php

abstract class db_abs {

	abstract function rawExec($sql);
	abstract function singleQuery($sql, $params = array());
	abstract function arrayQuery($sql, $params = array());
	abstract static function safe($s);	

}