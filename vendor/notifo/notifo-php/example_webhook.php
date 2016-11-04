<?php

include("Notifo_Webhook.php");

/* replace with your actual APISecret (found on notifo.com settings page) */
define("NOTIFO_APISECRET", "myNotifoAPISecret");


if (Notifo_Webhook::verify_signature($_POST["notifo_signature"], $_POST, NOTIFO_APISECRET)) {

  /* the signature is valid! */

  /* 
   * do whatever you want with the data at this point.
   * in this example we dump it to a log file
   */
  
  file_put_contents("/path/to/notifo.log", "at time " . time() . ":\n" . print_r($_POST, TRUE), FILE_APPEND);
  

 } else {
  /* signature verification failed. corrupted or spoofed message!!! */

  /*
   * do some error logging at this point, or just ignore 
   */

 }


?>
