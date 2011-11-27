<?php
class SpotAudit {
	private $_db;
	private $_user;
	private $_settings;
	private $_ipaddr;

	function __construct(SpotDb $db, SpotSettings $settings, array $user, $ipaddr) {
		$this->_db = $db;
		$this->_user = $user;
		$this->_settings = $settings;
		$this->_ipaddr = $ipaddr;
	} # ctor
	
	function audit($perm, $objectid, $allowed) {
		$this->_db->addAuditEntry($this->_user['userid'], $perm, $objectid, $allowed, $this->_ipaddr);
	} # audit
	
} # class SpotAudit
