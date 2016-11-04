<?php

class Notifo_Webhook {

  /*
   * function Notifo_Webhook::compute_signature
   * @param: $arr - associative array of "notifo_*" POST values
   * @param: $apisecret - your Notifo APISecret (found on notifo.com Settings page)
   *
   * @return: the computed notifo signature value based on the inputs
   */
  public function compute_signature($arr, $apisecret) {
   
    /* if the input $arr is an object, first turn it into an array */
    if (is_object($arr)) {
      $tmp = array();
      foreach ($arr as $k=>$v) {
	$tmp[$k] = $v;
      }
      $arr = $tmp;
    }

    /* order the array alphabetically by key */
    ksort($arr);

    $base = "";
   
    foreach ($arr as $key => $val) {
      if ($key == "notifo_signature") {
	/* exclude notifo_signature value from the computation */
	continue;
      }
      if (stripos($key, "notifo_") != 0) {
	/* skip unwanted keys */
	continue;
      }
      $base .= $val;
    }

    $base .= $apisecret;
   
    /* urlencode the string according to rfc3986 */
    $base_str = str_replace("%7E","~",rawurlencode($base));

    /* compute the signature as the sha1 hash of the $base_str */
    $signature = sha1($base_str);

    return $signature;
  }

  /*
   * function Notifo_Webhook::verify_signature
   * @param: $signature - the "notifo_signature" POST value
   * @param: $arr - associative array of "notifo_*" POST values
   * @param: $apisecret - your Notifo APISecret (found on notifo.com Settings page)
   *
   * @return: TRUE if the signature is valid, FALSE otherwise
   */
  public function verify_signature($signature, $arr, $apisecret) {

    $sig_check = Notifo_Webhook::compute_signature($arr, $apisecret);

    if ($signature == $sig_check) {
      return TRUE;
    } else {
      return FALSE;
    }
    
    return FALSE;

  }



} /* end class Notifo_Webhook */
