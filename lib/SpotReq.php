<?php

class SpotReq {
    static private $_merged = array(); 
	static private $_xsrfsecret = '';
	static private $_settings = null;
    
    function initialize($settings) {
		self::$_merged = array_merge($_POST, $_GET);
		self::$_xsrfsecret = $settings->get('xsrfsecret');
		self::$_settings = $settings;
    }
    
    function get($varName, $escapeType = 'none') {
		if( is_array($varName) ) {
			return self::escape(self::$_merged[$varName[0]][$varName[1]], $escapeType);
		} else {
			return self::escape(self::$_merged[$varName], $escapeType);
		}
    }    
	
	function getForm($formName, $submitNames) {
		if (isset($_POST[$formName])) {
			$form = $_POST[$formName]; 
		} else {
			return array('http_referer' => $this->getHttpReferer());
		} # else
		
		foreach($submitNames as $submitName) {
			if (isset($form[$submitName])) {
				if ($form[$submitName]) {
				
					/* Als er een ongeldige XSRF value is gegeven, moeten we 
					   alle submit buttons verwijderen */
					if (!$this->isXsrfValid($formName)) {
						foreach($submitNames as $tmpName) {
							unset($form[$tmpName]);
						} # foreach
					} # if
					
				} # if
			} # if
		} # foreach
		
		# Vul altijd een referer in als die nog niet ingevuld is
		if (!isset($form['http_referer'])) {
			$form['http_referer'] = $this->getHttpReferer();
		} # if
		
		return $form;
	} # getForm
	
	function getHttpReferer() {
		if (isset($_SERVER['HTTP_REFERER'])) {
			return $_SERVER['HTTP_REFERER'];
		} else {
			return self::$_settings->get('spotweburl');
		} # else
	} # getHttpReferer
	
    
	function cleanup($var) {
		if (is_array($var)) {
			foreach($var as &$value) {
				$value = $this->cleanup($value);
			} # foreach
		} else {
			$var = trim($var);
		} # else
		
		return $var;
	} # cleanup }
	
	static function isXsrfValid($form) {
		if (!isset($_POST[$form]['xsrfid'])) {
			return false;
		} # if
		
		# Explode the different values, if we don't agree
		# on the amount of values, exit immediately
		$xsrfVals = explode(":", $_POST[$form]['xsrfid']);
		
		if (count($xsrfVals) != 3) {
			return false;
		} # if
		
		# start validating, an XSRF cookie is only valid for 30 minutes
		if ( (time() - 1800) > $xsrfVals[0]) {
			return false;
		} # if
		
		# if action isn't the action we requested
		if ($xsrfVals[1] != $form) {
			return false;
		} # if
		
		# and check the hash
		if (sha1($xsrfVals[0] . ':' . $xsrfVals[1] . self::$_xsrfsecret) != $xsrfVals[2]) {
			return false;
		} # if
		
		return true;
	} # isXsrfValid
	
	static function generateXsrfCookie($action) {
		# XSRF cookie contains 3 fields:
		#   1 - Current timestamp in unixtime
		#	2 - action (for example, 'login' or 'postcomment')
		#	3 - sha1 of the preceding 2 strings including ':', but the secret key appended as salt
		$xsrfCookie = time() . ':' . $action;
		$xsrfCookie .= ':' . sha1($xsrfCookie . self::$_xsrfsecret);

		return $xsrfCookie;
	} # generateXsrfCookie
   
    function doesExist($varName) {
		if( is_array($varName) ) {
			return isset(self::$_merged[$varName[0]][$varName[1]]);
		}
		else {
			return isset(self::$_merged[$varName]);
		}
    } 
 
    function getDef($varName, $defValue, $escapeType = 'none') {
		if( !isset(self::$_merged[$varName]) ) {
			return $defValue;
		} else {
			return self::get($varName, $escapeType);
		}
    }

    function getSrvVar($varName, $defValue = '', $escapeType = 'none') {
		if( isset($_SERVER[$varName]) ) {
			return self::escape($_SERVER[$varName], $escapeType);
		} else {
			return $defValue;
		}
    }
    
    function escape($var, $escapeType) {
		if( is_array($var) ) {
			foreach($var as $key => $value) {
				$var[$key] = self::escape($value, $escapeType);
			}
    
			return $var;
		} else {
    	    // and start escaping
			switch( $escapeType ) {
				case 'html'  : return htmlspecialchars($var);
							   break;
				
				case 'none'	 : return $var;
							   break;
				
				default : die('Unknown escape type: ' . $escapeType);
			} # switch
		} #else
    }
}
