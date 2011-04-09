<?php

class SpotReq {
    static private $_merged = array(); 
    
    function initialize() {
		SpotReq::$_merged = array_merge($_POST, $_GET);
    }
    
    function get($varName, $escapeType = 'none') {
		if( is_array($varName) ) {
			return SpotReq::escape(SpotReq::$_merged[$varName[0]][$varName[1]], $escapeType);
		} else {
			return SpotReq::escape(SpotReq::$_merged[$varName], $escapeType);
		}
    }    
    
	function isXsrfValid($action, $secret) {
		if (!isset($_POST['xsrfid'])) {
			return false;
		} # if
		
		# Explode the different values, if we don't agree
		# on the amount of values, exit immediately
		$xsrfVals = explode(":", $_POST['xsrfid']);
		if (count($xsrfVals) != 3) {
			return false;
		} # if
		
		# start validating, a cookie is only valid for 30 minutes
		if ($xsrfCookie[0] < (time() - 1800)) {
			return false;
		} # if
		
		# if action isn't the action we requested
		if ($xsrfCookie[1] != $action) {
			return false;
		} # if
		
		# and check the hash
		if (sha1($xsrfCookie[0] . ':' . $xsrfCookie[1] . $secret) != $xsrfCookie[3]) {
			return false;
		} # if
		
		return true;
	} # isXsrfValid
	
	function generateXsrfCookie($action, $secret) {
		# XSRF cookie contains 3 fields:
		#   1 - Current timestamp in unixtime
		#	2 - action (for example, 'login' or 'postcomment')
		#	3 - sha1 of the preceding 2 strings including ':', but the secret key appended as salt
		$xsrfCookie = time() . ':' . $action;
		$xsrfCookie .= ':' . sha1($xsrfCookie . $secret);
		
		return array('field' => 'xsrfid',
					 'value' => $xsrfCookie);
	} # generateXsrfCookie
   
    function doesExist($varName) {
		if( is_array($varName) ) {
			return isset(SpotReq::$_merged[$varName[0]][$varName[1]]);
		}
		else {
			return isset(SpotReq::$_merged[$varName]);
		}
    } 
 
    function getDef($varName, $defValue, $escapeType = 'none') {
		if( !isset(SpotReq::$_merged[$varName]) ) {
			return $defValue;
		} else {
			return SpotReq::get($varName, $escapeType);
		}
    }

    function getSrvVar($varName, $defValue = '', $escapeType = 'none') {
		if( isset($_SERVER[$varName]) ) {
			return SpotReq::escape($_SERVER[$varName], $escapeType);
		} else {
			return $defValue;
		}
    }
    
    function escape($var, $escapeType) {
		if( is_array($var) ) {
			foreach($var as $key => $value) {
				$var[$key] = SpotReq::escape($value, $escapeType);
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
