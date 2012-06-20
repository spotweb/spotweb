<?php

class Dao_Base_Audit implements Dao_Audit {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Audit object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor
 
	/*
	 * Create an entry in the auditlog
	 */
	function addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr) {
		return $this->_conn->modify("INSERT INTO permaudit(stamp, userid, permissionid, objectid, result, ipaddr) 
										VALUES(%d, %d, %d, '%s', '%s', '%s')",
								Array(time(), (int) $userid, (int) $perm, $objectid, $this->_conn->bool2dt($allowed), $ipaddr));
	} # addAuditEntry

} # Dao_Base_Audit
