<?php

class Dao_Mysql_User extends Dao_Base_User
{
    protected function denyColumn()
    {
        return '`deny`';
    }
}
