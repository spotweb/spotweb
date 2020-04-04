<?php

interface Dao_Notification
{
    public function addNewNotification($userId, $objectId, $type, $title, $body);

    public function getUnsentNotifications($userId);

    public function updateNotification($msg);
} // Dao_Notification
