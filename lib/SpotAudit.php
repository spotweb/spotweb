<?php
class SpotAudit {
	private $_auditDao;
	private $_user;
	private $_settings;
	private $_ipaddr;

	function __construct(Dao_Audit $auditDao, Services_Settings_Base $settings, array $user, $ipaddr) {
		$this->_auditDao = $auditDao;
		$this->_user = $user;
		$this->_settings = $settings;
		$this->_ipaddr = $ipaddr;
	} # ctor
	
	function audit($perm, $objectid, $allowed) {
		$this->_auditDao->addAuditEntry($this->_user['userid'], $perm, $objectid, $allowed, $this->_ipaddr);
	} # audit
	
} # class SpotAudit
