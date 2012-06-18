<?php

class Dao_Base_Notification implements Dao_Notification {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Notification object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor
 
	/*
	 * Adds a new notification
	 */
	function addNewNotification($userId, $objectId, $type, $title, $body) {
		$this->_conn->modify("INSERT INTO notifications(userid,stamp,objectid,type,title,body,sent) VALUES(%d, %d, '%s', '%s', '%s', '%s', '%s')",
					Array($userId, (int) time(), $objectId, $type, $title, $body, $this->_conn->bool2dt(false)));
	} # addNewNotification
	
	/*
	 * Retrieves unsent notifications for a specific user
	 */
	function getUnsentNotifications($userId) {
		return $this->_conn->arrayQuery("SELECT id,userid,objectid,type,title,body FROM notifications WHERE userid = %d AND NOT SENT;",
					Array($userId));
	} # getUnsentNotifications

	/* 
	 * Update a notification
	 */
	function updateNotification($msg) {
		$this->_conn->modify("UPDATE notifications SET title = '%s', body = '%s', sent = '%s' WHERE id = %d",
					Array($msg['title'], $msg['body'], $this->_conn->bool2dt($msg['sent']), $msg['id']));
	} // updateNotification
	

} # Dao_Base_Notification
