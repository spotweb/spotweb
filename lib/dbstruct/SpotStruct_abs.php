<?php

abstract class SpotStruct_abs {
	protected $_dbcon;
	
	public function __construct($dbCon) {
		$this->_dbcon = $dbCon;
	} # __construct
	
	abstract function createDatabase();
} # class