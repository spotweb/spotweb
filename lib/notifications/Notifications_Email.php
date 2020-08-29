<?php
use PHPMailer\PHPMailer\Exception; //Not used
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP; //Not used

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

class Notifications_Email extends Notifications_abs
{
    private $_dataArray;
    private $_appName;

    public function __construct($appName, array $dataArray)
    {
        parent::__construct();
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

        if (isset($smtp["use"]) && ($smtp["use"] === true)) {
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
            $mail->dsn = 'NEVER';
            $mail->WordWrap = 78;
            $mail->XMailer = null;
            $mail->Host = $smtp["host"];
            $mail->Username = $smtp["user"];
            $mail->Password = $smtp["pass"];
			$mail->Port = $smtp["port"];
            $mail->addAddress($this->_dataArray['receiver']);
            $mail->setFrom($this->_dataArray['sender'], $this->_appName);
            $mail->addReplyTo($this->_dataArray['sender']);
            $mail->Subject = $title;
            $mail->Body = $body;
            $mail->send();
        } else {
            $body = wordwrap($body, 78);
            $header = 'From: '.$this->_appName.' <'.$this->_dataArray['sender'].">\r\n";
            mail($this->_dataArray['receiver'], $title, $body, $header);
        }
    }

    // sendMessage
} // Notifications_Email