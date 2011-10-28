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
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
						$remote_addr = $ip;
					} # if
				} # foreach
			} # if
		} # foreach
		$remote_addr = (isset($remote_addr)) ? $remote_addr : "unknown";

		$this->_db->addAuditEntry($this->_user['userid'], $perm, $objectid, $allowed, $remote_addr);
	} # audit
	
} # class SpotAudit
