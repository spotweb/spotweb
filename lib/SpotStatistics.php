<?php

class SpotStatistics {
	protected $_db;

	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function getSpotCountPerHour($limit) {
		$rs = $this->_db->getSpotCountPerHour($limit);
		return $rs;
	} # getSpotCountPerHour

	function getSpotCountPerWeekday($limit) {
		$rs = $this->_db->getSpotCountPerWeekday($limit);
		return $rs;
	} # getSpotCountPerWeekday

	function getSpotCountPerMonth($limit) {
		$rs = $this->_db->getSpotCountPerMonth($limit);
		return $rs;
	} # getSpotCountPerMonth

	function getSpotCountPerCategory($limit) {
		$rs = $this->_db->getSpotCountPerCategory($limit);
		return $rs;
	} # getSpotCountPerCategory
	
} # class SpotStatistics
