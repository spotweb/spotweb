<?php
require_once "lib/exceptions/CustomException.php";

class PermissionDeniedException extends CustomException {
	public function __construct($message = null, $code = 0) {
		parent::__construct("Permission denied [" . $message . "] for objectid [" . $code . "]", 5);
	} # ctor

} # class

