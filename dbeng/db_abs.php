<?php

abstract class db_abs {

	abstract function exec($sql, $params = array());
	abstract function singleQuery($sql, $params = array());
	abstract function arrayQuery($sql, $params = array());
	

}