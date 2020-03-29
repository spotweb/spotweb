<?php

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

    public function sendMessage($type, $title, $body, $sourceUrl)
    {
        $body = wordwrap($body, 70);
        if (isset($this->_settings->smtp['use'] || $this->_settings->smtp['use'])) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->isHTML(false);
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $mail->Priority = 1;
            $mail->do_verp = true;
            $mail->SingleTo = true;
            $mail->dsn = 'NEVER';
            $mail->WordWrap = 78;
            $mail->XMailer = null;
            $mail->Hostname = $this->_settings->smtp['host'];
            $mail->$mail->Helo = 
            $mail->Host = 
            $mail->SMTPAuth = true;
            $mail->Username = $this->_settings->smtp['user'];
            $mail->Password = $this->_settings->smtp['pass'];
            $mail->addAddress($this->_dataArray['receiver']);
            $mail->setFrom($this->_dataArray['sender'], $this->_appName);
            $mail->addReplyTo($this->_dataArray['sender']);
            $mail->Subject = $title;
            $mail->Body = $body;
            $mail->send()
        } else {
            $header = 'From: '.$this->_appName.' <'.$this->_dataArray['sender'].">\r\n";
            mail($this->_dataArray['receiver'], $title, $body, $header);
        }
    }

    // sendMessage
} // Notifications_Email
