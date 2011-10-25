<?php
class SpotAudit {
	private $_db;
	private $_user;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings, array $user) {
		$this->_db = $db;
		$this->_user = $user;
		$this->_settings = $settings;
	} # ctor
	
	function audit($perm, $objectid, $allowed) {
		if (getenv("HTTP_CLIENT_IP")) {
			$remote_addr = getenv("HTTP_CLIENT_IP");
		} elseif(getenv("HTTP_X_FORWARDED_FOR")) {
			$remote_addr = getenv("HTTP_X_FORWARDED_FOR");
		} elseif(getenv("REMOTE_ADDR")) {
			$remote_addr = getenv("REMOTE_ADDR");
		} else {
			$remote_addr = "unknown";
		}

		$this->_db->addAuditEntry($this->_user['userid'], $perm, $objectid, $allowed, $remote_addr);
	} # audit
	
} # class SpotAudit
