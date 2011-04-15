<?php
require_once('lib/nzbhandler/NzbHandler.php');

class NzbgetNzbHandler extends NzbHandler
{
	private $_host = null;
	private $_port = null;
	private $_timeout = null;
	private $_username = null;
	private $_password = null;

	function __construct($settings)
	{
		$this->setName("NZBGet");
		$this->setNameShort("D/L");
		
		$nzbhandling = $settings->get('nzbhandling');
		$nzbget = $nzbhandling['nzbget'];
		$this->_host = $nzbget['host'];
		$this->_port = $nzbget['port'];
		$this->_timeout = $nzbget['timeout'];;
		$this->_username = $nzbget['username'];
		$this->_password = $nzbget['password'];
	} # __construct

	public function processNzb($fullspot, $filename, $category, $nzb, $mimetype)
	{
		# mimetype parameter are not used by NZBGet
		
		$args = array($filename, $category, false, base64_encode($nzb));
		return $this->sendRequest('append', $args);
	} # processNzb

	private function sendRequest($method, $params)
	{
		# create the message body first since we need to provide the body length
		# in the message header
		$body = json_encode(array(
			'version' => '1.1', 
			'method' => $method, 
			'params' => $params));

		$header = "POST /jsonrpc HTTP/1.0\r\n" .
			"User-Agent: SpotWeb\r\n" .
			"Host: " . $this->_host . "\r\n" .
			"Authorization: Basic " . base64_encode($this->_username . ":" . $this->_password) . "\r\n" .
			"Content-Length: " . strlen($body) . "\r\n" .
			"\r\n";

		# create the full message from the header and body
		$message = $header . $body;

		# open connection to NZBGet
		$connection = @fsockopen(
			$this->_host,
			$this->_port,
			$errNo,
			$errMsg,
			$this->_timeout);

		if (!$connection)
		{
			error_log("ERROR: NZBGet connect error: $errMsg ($errNo)");
			throw new Exception("ERROR: NZBGet connect error: $errMsg ($errNo)");
		}

		if (!fputs($connection, $message, strlen($message)))
		{
			error_log("ERROR: Cannot write to NZBGet socket");
			throw new Exception("ERROR: Cannot write to NZBGet socket");
		}

		$result = "";
		while($data = fread($connection, 32768))
		{
			$result .= $data;
		}
		fclose($connection);

		if (!$result)
		{
			error_log("ERROR: NZBGet closed the connection");
			throw new Exception("ERROR: NZBGet closed the connection");
		}

		$index = strpos($result, "\r\n\r\n");
		if ($index)
		{
			$result = substr($result, $index + 4);
		}

		$response = json_decode($result, true);
		if (is_array($response) && isset($response['error']) && isset($response['error']['code']))
		{
			error_log("NZBGet RPC: Method '" . $method . "', " . $response['error']['message'] . " (" . $response['error']['code'] . ")");
			throw new Exception("NZBGet RPC: Method '" . $method . "', " . $response['error']['message'] . " (" . $response['error']['code'] . ")");
		}
		else if (is_array($response) && isset($response['result']))
		{
			return $response['result'];
		}
		else
		{
			error_log("ERROR: Could not decode json-data for NZBGet method '" . $method ."'");
			throw new Exception("ERROR: Could not decode json-data for NZBGet method '" . $method ."'");
		}
	} # sendRequest

}
?>