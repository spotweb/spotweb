<?php

class Dao_Base_Audit implements Dao_Audit
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Audit object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Create an entry in the auditlog
     */
    public function addAuditEntry($userid, $perm, $objectid, $allowed, $ipaddr)
    {
        return $this->_conn->modify(
            'INSERT INTO permaudit(stamp, userid, permissionid, objectid, result, ipaddr) 
										VALUES(:stamp, :userid, :permissionid, :objectid, :result, :ipaddr)',
            [
                ':stamp'    => [time(), PDO::PARAM_INT],
                ':userid'   => [$userid, PDO::PARAM_INT],
                ':perm'     => [$userid, PDO::PARAM_INT],
                ':objectid' => [$objectid, PDO::PARAM_STR],
                ':allowed'  => [$allowed, PDO::PARAM_BOOL],
                ':ipaddr'   => [$ipaddr, PDO::PARAM_STR],
            ]
        );
    }

    // addAuditEntry
} // Dao_Base_Audit
