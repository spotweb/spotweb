<?php

class Dao_Mysql_SpotStateList extends Dao_Base_SpotStateList
{
    /*
     * Add a specific state to a specific spot
     */
    public function addToSpotStateList($list, $messageId, $ourUserId)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $stamp = time();
        $this->_conn->modify(
            'INSERT INTO spotstatelist (messageid, ouruserid, '.$list.')
		                      VALUES (:messageid, :ouruserid, :stamp1) ON DUPLICATE KEY UPDATE '.$list.' = :stamp2',
            [
                ':messageid' => [$messageId, PDO::PARAM_STR],
                ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
                ':stamp1'    => [$stamp, PDO::PARAM_INT],
                ':stamp2'    => [$stamp, PDO::PARAM_INT],
            ]
        );

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$list, $messageId, $ourUserId, $stamp]);
    }

    // addToSpotStateList
} // Dao_Mysql_SpotStateList
