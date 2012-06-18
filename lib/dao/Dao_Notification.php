<?php

interface Dao_Notification {
	
	function addNewNotification($userId, $objectId, $type, $title, $body);
	function getUnsentNotifications($userId);
	function updateNotification($msg);

} # Dao_Notification