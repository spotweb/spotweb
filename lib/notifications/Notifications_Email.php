<?php

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../');
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Notifications_Email extends Notifications_abs
{
    private $_dataArray;
    private $_appName;

    public function __construct($appName, array $dataArray)
    {
        $this->_appName = $appName;
        $this->_dataArray = $dataArray;
    }

    // ctor

    public function register()
    {
    }

    // register

    public function sendMessage($type, $title, $body, $sourceUrl, $smtp)
    {
        if (isset($smtp['use']) && ($smtp['use'] === true)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->isHTML(false);
                $mail->SMTPAuth = true;
                $mail->SMTPDebug = 0; //SMTP::DEBUG_OFF; https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
                $mail->SMTPAutoTLS = true;
                $mail->SMTPSecure = 'tls';
                $mail->CharSet = PHPMailer::CHARSET_UTF8;
                $mail->Encoding = PHPMailer::ENCODING_BASE64;
                $mail->Priority = 1;
                $mail->SMTPOptions = ['ssl' => ['verify_peer_name'  => false]];
                $mail->WordWrap = 78;
                $mail->XMailer = null;
                $mail->Host = $smtp['host'];
                $mail->Username = $smtp['user'];
                $mail->Password = $smtp['pass'];
                $mail->Port = $smtp['port'];
                $mail->addAddress($this->_dataArray['receiver']);
                $mail->setFrom($this->_dataArray['sender'], $this->_appName);
                $mail->addReplyTo($this->_dataArray['sender']);
                $mail->Subject = $title;
                $mail->Body = $body;
                $mail->send();
            } catch (Exception $e) {
                echo 'PHPMailer Error : '.$e->getMessage().' check your SMTP settings.\n';
                exit;
            }
        } else {
            $body = wordwrap($body, 78);
            $header = 'From: '.$this->_appName.' <'.$this->_dataArray['sender'].">\r\n";
            mail($this->_dataArray['receiver'], $title, $body, $header);
        }
    }

    // sendMessage
} // Notifications_Email
