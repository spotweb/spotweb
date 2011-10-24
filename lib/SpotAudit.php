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
		$this->_db->addAuditEntry($this->_user['userid'], $perm, $objectid, $allowed, $_REQUEST['REMOTE_ADDR']);
	} # audit
	
} # class SpotAudit
