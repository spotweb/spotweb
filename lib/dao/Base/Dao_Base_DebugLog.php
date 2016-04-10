<?php

class Dao_Base_DebugLog implements Dao_DebugLog {
    protected $_conn;

    /*
     * constructs a new Dao_Base_Cache object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn) {
        $this->_conn = $conn;
    } # ctor

    /**
     * @param $lvl
     * @param $msg
     */
    function add($lvl, $microtime, $msg) {
        $this->_conn->modify("INSERT INTO debuglog(stamp, microtime, level, message) VALUES(:stamp, :microtime, :level, :message)",
            array(
                ':stamp' => array(time(), PDO::PARAM_INT),
                ':level' => array($lvl, PDO::PARAM_INT),
                ':microtime' => array($microtime, PDO::PARAM_STR),
                ':message' => array($msg, PDO::PARAM_STR),
            ));
    } # add()

    /**
     *
     */
    function expire() {
        $this->_conn->modify("DELETE FROM debuglog WHERE stamp < :expiretime",
            array(
                ':expiretime' => array(time() - (24 * 60 * 60), PDO::PARAM_INT),
            ));
    } # expire()

} # Dao_Base_DebugLog