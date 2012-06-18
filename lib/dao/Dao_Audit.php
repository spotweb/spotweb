<?php

interface Dao_Audit {

	function addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr);
	
} # Dao_Audit