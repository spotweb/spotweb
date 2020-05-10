<?php

class Dao_Mysql_Setting extends Dao_Base_Setting
{
    /*
     * Update setting
     */
    public function updateSetting($name, $value)
    {
        // When necessary, serialize the data
        if ((is_array($value) || is_object($value))) {
            $value = serialize($value);
            $serialized = true;
        } else {
            $serialized = false;
        } // if

        $this->_conn->modify(
            'INSERT INTO settings(name, value, serialized)
		                                VALUES (:name, :value1, :serialized1)
		                                ON DUPLICATE KEY UPDATE value = :value2, serialized = :serialized2',
            [
                ':name'        => [$name, PDO::PARAM_STR],
                ':value1'      => [$value, PDO::PARAM_STR],
                ':serialized1' => [$serialized, PDO::PARAM_BOOL],
                ':value2'      => [$value, PDO::PARAM_STR],
                ':serialized2' => [$serialized, PDO::PARAM_BOOL],
            ]
        );
    }

    // updateSetting
} // class Dao_Mysql_Setting
