<?php

class Dao_Base_Notification implements Dao_Notification {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Notification object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn) {
		$this->_conn = $conn;
	} # ctor
 
	/*
	 * Adds a new notification
	 */
	function addNewNotification($userId, $objectId, $type, $title, $body) {
		$this->_conn->modify("INSERT INTO notifications(userid, stamp, objectid, type, title, body, sent)
		                        VALUES(:userid, :stamp, :objectid, :type, :title, :body, :sent)",
            array(
                ':userid' => array($userId, PDO::PARAM_INT),
                ':stamp' => array(time(), PDO::PARAM_INT),
                ':objectid' => array($objectId, PDO::PARAM_STR),
                ':type' => array($type, PDO::PARAM_STR),
                ':title' => array($title, PDO::PARAM_STR),
                ':body' => array($body, PDO::PARAM_STR),
                ':sent' => array(false, PDO::PARAM_BOOL)
            ));
	} # addNewNotification
	
	/*
	 * Retrieves unsent notifications for a specific user
	 */
	function getUnsentNotifications($userId) {
		return $this->_conn->arrayQuery("SELECT id, userid, objectid, type, title, body FROM notifications WHERE userid = :userid AND NOT SENT",
            array(
                ':userid' => array($userId, PDO::PARAM_INT)
            ));
	} # getUnsentNotifications

	/* 
	 * Update a notification
	 */
	function updateNotification($msg) {
		$this->_conn->modify("UPDATE notifications SET title = :title, body = :body, sent = :sent WHERE id = :id",
            array(
                ':title' => array($msg['title'], PDO::PARAM_STR),
                ':body' => array($msg['body'], PDO::PARAM_STR),
                ':sent' => array($msg['sent'], PDO::PARAM_BOOL),
                ':id' => array($msg['id'], PDO::PARAM_INT)
            ));
	} // updateNotification
	

} # Dao_Base_Notification
