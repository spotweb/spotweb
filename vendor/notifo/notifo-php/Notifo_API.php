<?php

class Notifo_API {
  
  const API_ROOT = 'https://api.notifo.com/';
  const API_VER = 'v1';

  protected $apiUsername;
  protected $apiSecret;

  /**
   * class constructor
   */
  function __construct($apiUsername, $apiSecret) {
    $this->apiUsername = $apiUsername;
    $this->apiSecret = $apiSecret;
  }

  function set_apiusername($val) {
    $this->apiUsername = $val;
  }

  function set_apisecret($val) {
    $this->apiSecret = $val;
  }

  /**
   * function: sendNotification
   * @param: $params - an associative array of parameters to send to the Notifo API.
   * These can be any of the following:
   * to, msg, label, title, uri
   * See https://api.notifo.com/ for more information
   */
  function sendNotification($params) {
    $validFields = array('to', 'msg', 'label', 'title', 'uri');
    $params = array_intersect_key($params, array_flip($validFields));
    return $this->sendRequest('send_notification', 'POST', $params);
  } /* end function sendNotification */

  function sendMessage($params) {
    $validFields = array('to','msg');
    $params = array_intersect_key($params, array_flip($validFields));
    return $this->sendRequest('send_message', 'POST', $params);
  }

  /**
   * function: subscribeUser
   * @param: $username - the username to subscribe to your Notifo service
   * See https://api.notifo.com/ for more information
   */
  function subscribeUser($username) {
    return $this->sendRequest('subscribe_user', 'POST', array('username' => $username));
  } /* end function subscribeUser */


  /**
   * helper function to send the requests
   * @param $method - name of remote method to call
   * @param $type - HTTP method (GET, POST, etc)
   * @param $data - array with arguments for remote method
   */
  function sendRequest($method, $type, $data) {

    $url = self::API_ROOT.self::API_VER.'/'.$method;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($type == "POST") {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($ch, CURLOPT_USERPWD, $this->apiUsername.':'.$this->apiSecret);
    curl_setopt($ch, CURLOPT_HEADER, false);

    /*
     * if you are on a shared host or do not have access to install
     * the root CA certificates on your server, uncomment the next
     * two lines or the curl_exec call may fail with null
     */
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    $result = curl_exec($ch);
    $result = json_decode($result, true);
    return $result;
  } /* end function sendRequest */

// for backwards compatibility
  function send_notification($params) { return json_encode($this->sendNotification($params)); }
  function send_message($params) { return json_encode($this->sendMessage($params)); }
  function subscribe_user($username) { return json_encode($this->subscribeUser($username)); }
  function send_request($url, $type, $data) { return json_encode($this->sendRequest($method, $type, $data)); }

} /* end class Notifo_API */

?>
