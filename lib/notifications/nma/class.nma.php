<?php

class NotifyMyAndroid
{
	var $_version = '0.0.2-php4';
	var $_obj_curl = null;
	var $_return_code;
	var $_remaining;
	var $_resetdate;
	
	var $_use_proxy = false;
	var $_proxy = null;
	var $_proxy_userpwd = null;

	var $_api_key = null;
	var $_dev_key = null;
	var $_api_domain = 'https://www.notifymyandroid.com/publicapi/';
	var $_url_verify = 'verify?apikey=%s';
	var $_url_push = 'notify';
	
	var $_params = array(			// Accessible params [key => maxsize]
		'apikey' 	=> 		48,		// User API Key.
		'developerkey' 	=>		48,		// Provider key.
		'priority' 	=> 		2,		// Range from -2 to 2.
		'application' 	=> 		254,	// Name of the app.
		'event' 	=> 		1000,	// Name of the event.
		'description' 	=> 		10000,	// Description of the event.
	);
	
	function NotifyMyAndroid($apikey=null, $verify=false, $devkey=null, $proxy=null, $userpwd=null)
	{
		$curl_info = curl_version();	// Checks for cURL function and SSL version. Thanks Adrian Rollett!
		if(!function_exists('curl_exec') || empty($curl_info['ssl_version']))
		{
			die($this->getError(10000));
		}
		
		if(isset($proxy))
			$this->_setProxy($proxy, $userpwd);
		
		if(isset($apikey) && $verify)
			$this->verify($apikey, $devkey);
		
		$this->_api_key = $apikey;
	}
	
	function verify($apikey)
	{
		$return = $this->_execute(sprintf($this->_url_verify, $apikey));		
		return $this->_response($return);
	}
	
	function push($params, $is_post=true)
	{	
		if($is_post)
			$post_params = '';
			
		$url = $is_post ? $this->_url_push : $this->_url_push . '?';
		$params = func_get_args();
		
		if(isset($this->_api_key) && !isset($params[0]['apikey']))
			$params[0]['apikey'] = $this->_api_key;
		
		if(isset($this->_dev_key) && !isset($params[0]['developerkey']))
			$params[0]['developerkey'] = $this->_dev_key;
		
		foreach($params[0] as $k => $v)
		{
			$v = str_replace("\\n","\n",$v);	// Fixes line break issue! 
			if(!isset($this->_params[$k]))
			{
				$this->_return_code = 400;
				return false;
			}
			if(strlen($v) > $this->_params[$k])
			{
				$this->_return_code = 10001;
				return false;
			}
			
			if($is_post)
				$post_params .= $k . '=' . urlencode(utf8_encode($v)) . '&';
			else
				$url .= $k . '=' . urlencode(utf8_encode($v)) . '&';
		}
		
		if($is_post)
			$params = substr($post_params, 0, strlen($post_params)-1);
		else
			$url = substr($url, 0, strlen($url)-1);
		
		$return = $this->_execute($url, $is_post ? true : false, $params);
		
		return $this->_response($return);
	}
		
	function getError($code=null)
	{
		$code = (empty($code)) ? $this->_return_code : $code;
		switch($code)
		{
			case 200: 	return 'Request Successful.';	break;
			case 400:	return 'Bad request, the parameters you provided did not validate.';	break;
			case 401: 	return 'The API key given is not valid, and does not correspond to a user.';	break;
			case 402:	return 'Your IP address has exceeded the API limit.';	break;
			case 500:	return 'Internal server error, something failed to execute properly on the server side.';	break;
			case 10000:	return 'cURL library missing vital functions or does not support SSL. cURL w/SSL is required to execute NMAPHP.';	break;
			case 10001:	return 'Parameter value exceeds the maximum byte size.';	break;
			default:	return false;	break;
		}
	}
	
	function getRemaining()
	{
		if(!isset($this->_remaining))
			return false;
		
		return $this->_remaining;
	}
	
	function getResetDate()
	{
		if(!isset($this->_resetdate))
			return false;
			
		return $this->_resetdate;
	}
	
	function _execute($url, $is_post=false, $params=null)
	{
		$this->_obj_curl = curl_init($this->_api_domain . $url);
		curl_setopt($this->_obj_curl, CURLOPT_HEADER, 0);
		curl_setopt($this->_obj_curl, CURLOPT_USERAGENT, "NotifyMyAndroidPHP/" . $this->_version);
		curl_setopt($this->_obj_curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->_obj_curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->_obj_curl, CURLOPT_RETURNTRANSFER, 1);
		
		if($is_post)
		{
			curl_setopt($this->_obj_curl, CURLOPT_POST, 1);
			curl_setopt($this->_obj_curl, CURLOPT_POSTFIELDS, $params);
		}
		
		if($this->_use_proxy)
		{
			curl_setopt($this->_obj_curl, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($this->_obj_curl, CURLOPT_PROXY, $this->_proxy);
			curl_setopt($this->_obj_curl, CURLOPT_PROXYUSERPWD, $this->_proxy_userpwd); 
		}
		
		$return = curl_exec($this->_obj_curl);
		curl_close($this->_obj_curl);
		return $return;
	}
	
	function _response($return)
	{
		if($return===false)
		{
			$this->_return_code = 500;
			return false;
		}
		
		$return = str_replace("\n", " ", $return);
	
		if(preg_match("/code=\"200\"/i", $return))
			$this->_return_code = 200;
		else
		{
			preg_match("/<error code=\"(.*?)\".*>(.*?)<\/error>/i", $return, $out);
			$this->_return_code = $out[1];
		}
		
		switch($this->_return_code)
		{
			case 200: 	return true;	break;
			default:	return false;	break;
		}
		
		unset($response);
	}
	
	function _setProxy($proxy, $userpwd=null)
	{
		if(strlen($proxy) > 0)
		{
			$this->_use_proxy = true;
			$this->_proxy = $proxy;
			$this->_proxy_userpwd = $userpwd;
		}
	}
}

?>
