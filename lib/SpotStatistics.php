<?php

class SpotStatistics {
	protected $_db;

	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function getSpotCountPerHour($limit) {
		return $this->_db->getSpotCountPerHour($limit);
	} # getSpotCountPerHour

	function getSpotCountPerWeekday($limit) {
		return $this->_db->getSpotCountPerWeekday($limit);
	} # getSpotCountPerWeekday

	function getSpotCountPerMonth($limit) {
		return $this->_db->getSpotCountPerMonth($limit);
	} # getSpotCountPerMonth

	function getSpotCountPerCategory($limit) {
		return $this->_db->getSpotCountPerCategory($limit);
	} # getSpotCountPerCategory
	
} # class SpotStatistics
