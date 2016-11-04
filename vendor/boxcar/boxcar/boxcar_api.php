<?php
/**
 * Boxcar client api for providers.
 * 
 * History:
 * 
 *		29-Nov-10
 *			First version, well second version as I mv'd the sample
 *			file over the client. So this is a re-write. Doh!
 * 
 * @author Russell Smith <russell.smith@ukd1.co.uk>
 * @copyright UKD1 Limited 2010
 * @license licence.txt ISC license
 * @see http://boxcar.io/help/api/providers
 * @see https://github.com/ukd1/Boxcar
 */
class boxcar_api {
	
	/**
	 * The useragent to send though
	 */
	const USERAGENT = 'UKD1_Boxcar_Client';
	
	/**
	 * The endpoint for service.
	 */
	const ENDPOINT = 'http://boxcar.io/devices/providers/';
	
	/**
	 * Timeout for the API requests in seconds
	 */
	const TIMEOUT = 5;
	
	/**
	 * Stores the api key
	 *
	 * @var string
	 */
	private $api_key;
	
	/**
	 * Stores the api secret
	 * 
	 * @var string
	 */
	private $secret;
	
	/**
	 * A default icon url
	 * 
	 * @var string
	 */
	private $default_icon_url;
	
	/**
	 * Make a new instance of the API client
	 * 
	 * @param string $api_key your api key
	 * @param string $secret your api secret
	 * @param string $default_icon_url url to a 57x57 icon to use with a message
	 */
	public function __construct ($api_key, $secret, $default_icon_url = null) {
		$this->api_key = $api_key;
		$this->secret = $secret;
		$this->default_icon_url = $default_icon_url;
	}
	
	/**
	 * Get a new instance of the API client
	 * 
	 * @param string $api_key your api key
	 * @param string $secret your api secret
	 * @param string $default_icon_url url to a 57x57 icon to use with a message
	 */
	public static function factory ($api_key, $secret, $default_icon = null) {
		return new self($api_key, $secret, $default_icon);
	}
	
	/**
	 * Invite an existing user to add your provider
	 * 
	 * @param string $email the email address to invite
	 * @return bool 
	 */
	public function invite ($email) {
		$result = $this->http_post('notifications/subscribe', array('email' => $email));
		
		if ($result['http_code'] === 404) {
			throw new boxcar_exception('User not found', $result['http_code']);
		} else {
			return $this->default_response_handler($result);
		}
	}
	
	/**
	 * Send a notification
	 *
	 * @param string $emailThe users MD5'd e-mail address
	 * @param string $name the name of the sender
	 * @param string $message the message body
	 * @param string $id an optional unique id, will stop the same message getting sent twice
	 * @param string $payload Optional; The payload to be passed in as part of the redirection URL.
	 *                        Keep this as short as possible. If your redirection URL contains "::user::" in it,
	 *                        this will replace it in the URL. An example payload would be the users username, to
	 *                        take them to the appropriate page when redirecting
	 * @param string $source_url Optional; This is a URL that may be used for future devices. It will replace the redirect payload.
	 * @param string $icon  Optional; This is the URL of the icon that will be shown to the user. Standard size is 57x57.
	 */
	public function notify ($email, $name, $message, $id = null, $payload = null, $source_url = null, $icon = null) {
		return $this->do_notify('notifications', $email, $name, $message, $id, $payload, $source_url, $icon);
	}
	
	/**
	 * Send a notification to all users of your provider
	 *
	 * @param string $name the name of the sender
	 * @param string $message the message body
	 * @param string $id an optional unique id, will stop the same message getting sent twice
	 * @param string $payload Optional; The payload to be passed in as part of the redirection URL.
	 *                        Keep this as short as possible. If your redirection URL contains "::user::" in it,
	 *                        this will replace it in the URL. An example payload would be the users username, to
	 *                        take them to the appropriate page when redirecting
	 * @param string $source_url Optional; This is a URL that may be used for future devices. It will replace the redirect payload.
	 * @param string $icon  Optional; This is the URL of the icon that will be shown to the user. Standard size is 57x57.
	 */
	public function broadcast ($name, $message, $id = null, $payload = null, $source_url = null, $icon = null) {
		return $this->do_notify('notifications/broadcast', null, $name, $message, $id, $payload, $source_url, $icon);
	}
	
	
	/**
	 * Internal function for actually sending the notifications
	 *
	 * @param string $name the name of the sender
	 * @param string $message the message body
	 * @param string $id an optional unique id, will stop the same message getting sent twice
	 * @param string $payload Optional; The payload to be passed in as part of the redirection URL.
	 *                        Keep this as short as possible. If your redirection URL contains "::user::" in it,
	 *                        this will replace it in the URL. An example payload would be the users username, to
	 *                        take them to the appropriate page when redirecting
	 * @param string $source_url Optional; This is a URL that may be used for future devices. It will replace the redirect payload.
	 * @param string $icon Optional; This is the URL of the icon that will be shown to the user. Standard size is 57x57.
	 */
	private function do_notify($task, $email, $name, $message, $id = null, $payload = null, $source_url = null, $icon = null) {
		// if the icon was not set for this message, check for the default icon and use that if set
		if (is_null($icon) && !is_null($this->default_icon_url)) {
			$icon = $this->default_icon_url;
		}
			
		$notification = array(
			'token'                                 => $this->api_key,
			'secret'                                => $this->secret,
			'email'                                 => !is_null($email) ? $email : null,
			'notification[from_screen_name]'        => $name,
			'notification[message]'                 => $message,
			'notification[from_remote_service_id]'  => $id,
			'notification[redirect_payload]'        => $payload,
			'notification[source_url]'              => $source_url,
			'notification[icon_url]'                => $icon,
			);
		
		// unset the null ones...
		foreach ($notification as $key => $value) {
			if (is_null($notification[$key])) {
				unset($notification[$key]);
			}
		}
	
		$result = $this->http_post($task, $notification);
		
		return $this->default_response_handler($result);
	}
	
	/**
	 * Correctly handle the error / success states from the boxcar servers
	 * 
	 * @see http://boxcar.io/help/api/providers
	 * @param array $result
	 * @return string 
	 */
	private function default_response_handler ($result) {
		// work out what to do based on http code
		switch ($result['http_code']) {
			case 200:
				// return true, currently there are no responses returning anything...
				return true;
				break;
			
			// HTTP status code of 400, it is because you failed to send the proper parameters
			case 400:
				throw new boxcar_exception('Incorrect parameters passed', $result['http_code']);
				break;
				
			// For request failures, you will receive either HTTP status 403 or 401.
			
			// HTTP status code 401's, it is because you are passing in either an invalid token,
			// or the user has not added your service. Also, if you try and send the same notification
			// id twice.
			case 401:
				throw new boxcar_exception('Request failed (Probably your fault)', $result['http_code']);
				break;
			
			case 403:
				throw new boxcar_exception('Request failed (General)', $result['http_code']);
				break;
			
			// Unkown code
			default:
				throw new boxcar_exception('Unknown response', $result['http_code']);
				break;
		}
	}
	
	/**
	 * HTTP POST a specific task with the supplied data
	 *
	 * @param string $task
	 * @param array $data
	 * @return array
	 */
	private function http_post ($task, $data) {
		$url = self::ENDPOINT . $this->api_key . '/' . $task . '/';

		$post_fields = http_build_query($data);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

		$result = curl_exec ($ch);

		$tmp = curl_getinfo($ch);
		$tmp['result'] = $result;
		curl_close ($ch);
		
		return $tmp;
	}
}

/**
 * Boxcar exception
 */
class boxcar_exception extends Exception {}
