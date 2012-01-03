<?php

class SpotReq {
    static private $_merged = array(); 
	static private $_xsrfsecret = '';
	static private $_settings = null;
    
    function initialize($settings) {
		self::$_merged = array_merge_recursive($_POST, $_GET);
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
	
	function getForm($formName) {
		if (isset($_POST[$formName])) {
			$form = $_POST[$formName]; 
		} else {
			return array('action' => '', 
						 'http_referer' => $this->getHttpReferer());
		} # else
		

		/* default to an empty form action (eg: not submitted yet) */
		$form['action'] = '';

		/* and try to see if we have any real form action */
		foreach($form as $key => $value) {
			/*
			 * Extract the submit action so we can check
			 * wether the form is actually to be submitted
			 */
			$formSubmitted = (substr($key, 0, strlen('submit')) == 'submit');
			if ($formSubmitted) {
				if ($form[$key]) {

					/* pass the chosen action through if the xsrf check passes */				
					if ($this->isXsrfValid($formName)) {
						$form['action'] = substr($key, strlen('submit'));
					} # if
					
				} # if non-empty value for formsubmit 
			} # if formSubmitted
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
		
		# if the formname ('action' in some sort of way) isn't the action we requested
		if ($xsrfVals[1] != $form) {
			return false;
		} # if
		
		# and check the hash so any of the values above couldn't be faked
		if (sha1($xsrfVals[0] . ':' . $xsrfVals[1] . self::$_xsrfsecret) != $xsrfVals[2]) {
			return false;
		} # if
		
		return true;
	} # isXsrfValid
	
	static function generateXsrfCookie($action) {
		# XSRF cookie contains 3 fields:
		#   1 - Current timestamp in unixtime
		#	2 - formname (for example, 'loginform' or 'postcommentform')
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
