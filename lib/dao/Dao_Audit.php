<?php

interface Dao_Audit
{
    public function addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr);
} // Dao_Audit
