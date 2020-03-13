<?php

class Dao_Base_Notification implements Dao_Notification
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Notification object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Adds a new notification
     */
    public function addNewNotification($userId, $objectId, $type, $title, $body)
    {
        $this->_conn->modify(
            'INSERT INTO notifications(userid, stamp, objectid, type, title, body, sent)
		                        VALUES(:userid, :stamp, :objectid, :type, :title, :body, :sent)',
            [
                ':userid'   => [$userId, PDO::PARAM_INT],
                ':stamp'    => [time(), PDO::PARAM_INT],
                ':objectid' => [$objectId, PDO::PARAM_STR],
                ':type'     => [$type, PDO::PARAM_STR],
                ':title'    => [$title, PDO::PARAM_STR],
                ':body'     => [$body, PDO::PARAM_STR],
                ':sent'     => [false, PDO::PARAM_BOOL],
            ]
        );
    }

    // addNewNotification

    /*
     * Retrieves unsent notifications for a specific user
     */
    public function getUnsentNotifications($userId)
    {
        return $this->_conn->arrayQuery(
            'SELECT id, userid, objectid, type, title, body FROM notifications WHERE userid = :userid AND NOT SENT',
            [
                ':userid' => [$userId, PDO::PARAM_INT],
            ]
        );
    }

    // getUnsentNotifications

    /*
     * Update a notification
     */
    public function updateNotification($msg)
    {
        $this->_conn->modify(
            'UPDATE notifications SET title = :title, body = :body, sent = :sent WHERE id = :id',
            [
                ':title' => [$msg['title'], PDO::PARAM_STR],
                ':body'  => [$msg['body'], PDO::PARAM_STR],
                ':sent'  => [$msg['sent'], PDO::PARAM_BOOL],
                ':id'    => [$msg['id'], PDO::PARAM_INT],
            ]
        );
    }

    // updateNotification
} // Dao_Base_Notification
