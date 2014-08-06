<?php
class Pushalot
{
     const PUSHALOT_API_URL = 'https://pushalot.com/api/';
     const PUSHALOT_API_SENDMESSAGE = 'sendmessage';
     const USERAGENT = 'PushalotPHP/0.3';

     var $_curl = null;
     var $_result_code;

     var $_use_proxy = false;
     var $_proxy = null;
     var $_proxy_userpass = null;

     var $_token = null;

     var $_params = array(
          'AuthorizationToken' => 32,
          'Title'              => 250,
          'Body'               => 32768,
          'LinkTitle'          => 100,
          'Link'               => 1000,
          'IsImportant'        => 1,
          'IsSilent'           => 1,
          'Image'              => 250,
          'Source'             => 25,
     );

     function Pushalot($token=null, $proxy=null, $proxy_userpass=null)
     {
          $curl_info = curl_version();
          if(!function_exists('curl_exec') || empty($curl_info['ssl_version']))
          {
               die($this->getError(1000));
          }
          if(isset($proxy)) $this->setProxy($proxy, $proxy_userpass);

          $this->_token = $token;
     }

     public function sendMessage($params)
     {
          $post_str = '';
          if(!array_key_exists('AuthorizationToken', $params))
               $params['AuthorizationToken'] = $this->_token;
          foreach($params as $k => $v)
          {
               if(!isset($this->_params[$k]))
               {
                    $this->_result_code = 400;
                    return false;
               }
               if($this->_params[$k] > 1 && strlen($v) > $this->_params[$k])
               {
                    $this->_result_code = 1001;
                    return false;
               }
               if(is_bool($v)) $v = $v ? 'True' : 'False';
               $post_str .= $k . '=' . urlencode(utf8_encode($v)) . '&';
          }
          $params = substr($post_str, 0, strlen($post_str)-1);

          $return = $this->execute(self::PUSHALOT_API_SENDMESSAGE, $params);

          return $this->response($return);
     }

     public function getError($code=null)
     {
          $code = (empty($code)) ? $this->_result_code : $code;
          switch($code)
          {
               case 200: return 'The message has been sent successfully.'; break;
               case 400: return 'Input data validation failed.'; break;
               case 405: return 'Method POST is required.'; break;
               case 406: return 'Message throttle limit hit.';   break;
               case 410: return 'The AuthorizationToken is no longer valid and no more messages should be ever sent again using that token.';    break;
               case 500: return 'Internal server error.';   break;
               case 503: return 'Our servers are currently overloaded with requests. Try again later.';  break;
               case 1000:return 'cURL library missing functions or has no SSL support.';  break;
               case 1001:return 'A parameter value exceeds the maximum length.';     break;
               default:  return false;  break;
          }
     }

     public function setProxy($proxy, $userpass=null)
     {
          if(strlen($proxy) > 0)
          {
               $this->_use_proxy = true;
               $this->_proxy = $proxy;
               $this->_proxy_userpass = $userpass;
          }else $this->_use_proxy = false;
     }

     private function execute($url,  $params=null)
     {
          $this->_curl = curl_init(self::PUSHALOT_API_URL . $url);
          curl_setopt($this->_curl, CURLOPT_HEADER, 0);
          curl_setopt($this->_curl, CURLOPT_USERAGENT, self::USERAGENT);
          curl_setopt($this->_curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
          curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);

          curl_setopt($this->_curl, CURLOPT_POST, 1);
          curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $params);

          if($this->_use_proxy)
          {
               curl_setopt($this->_curl, CURLOPT_HTTPPROXYTUNNEL, 1);
               curl_setopt($this->_curl, CURLOPT_PROXY, $this->_proxy);
               curl_setopt($this->_curl, CURLOPT_PROXYUSERPWD, $this->_proxy_userpass);
          }

          $return = curl_exec($this->_curl);
          $info = curl_getinfo($this->_curl);
          curl_close($this->_curl);
          $this->_result_code = $info['http_code'];
          return $return;
     }

     private function response($return)
     {
          switch($this->_result_code)
          {
               case 200:      return true;   break;
               default:  return false;  break;
          }
     }
}

?>