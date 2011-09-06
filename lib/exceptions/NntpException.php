<?php
require_once "lib/exceptions/CustomException.php";
	
class NntpException extends CustomException {
	private $_response = '';
	
	public function __construct($detail = null, $code = 0, $response = '') {
		$this->_detail = $detail;
		$this->_code = $code;
		$this->_response = $response;

		parent::__construct($detail . ' [response: "' . $response . '"]', $code);
	} # ctor

	
} # NntpException