<?php

define("NOTIFO_SERVICE_USERNAME", "dicebot");
define("NOTIFO_APISECRET", "xxxxxxxxxxxxxxxxxxxxxxx");

include("Notifo_API.php");
include("Notifo_Webhook.php");

/* verify that the POST data is actually from notifo */
if (Notifo_Webhook::verify_signature($_POST["notifo_signature"], $_POST, NOTIFO_APISECRET)) {

  /* signature passed, run the bot code */
  if (strtolower($_POST["notifo_to_username"]) == "dicebot") {

    /* message is for dicebot */
    dicebot($_POST);
  }

 } else {
  /* bad signature... just ignore */
 }


/* functions */

function arr2obj($array) {
  $object = new stdClass();
  if (is_array($array) && count($array) > 0) {
    foreach ($array as $name=>$value) {
      $name = strtolower(trim($name));
      if (!empty($name)) {
	$object->$name = $value;
      }
    }
  }
  return $object;
}


function dicebot($info) {
  $obj = arr2obj($info);
  
  /* username to send reply to */
  $to_username = $obj->notifo_from_username;
  
  /* compute response here */
  $msg = strtolower($obj->notifo_message); 
  
  $arr = explode(" ", $msg);
  $commands_arr = array("flip", "help", "hi", "hello");
  $command = strtolower($arr[0]);
  
  if (!(in_array($command, $commands_arr))) {
    
    /* invalid command, tell them to send help for info */
    $message = "Send the command 'help' for more info.";
    
  } else if ($command == "flip") {
    
    $coins = array("Heads", "Tails");
    $flip = mt_rand(0, 99);
    if ($flip < 50) {
      $flip = 0;
    } else {
      $flip = 1;
    }
    
    $message = "You flipped a coin: " . $coins[$flip] . ".";
    
  } else {
    /* sent help, hi, or hello */
    $message = "Hello, send the command 'flip' to flip a coin!";
  }
 
  $notifo = new Notifo_API(NOTIFO_SERVICE_USERNAME, NOTIFO_APISECRET);
 
  $notifo->send_message(array("to"=>$to_username, "msg"=>$message));
  
}
?>
