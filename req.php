<?php

class Req {
    static private $_merged = array(); 
    
    function initialize() {
		Req::$_merged = $_REQUEST;
    }
    
    function get($varName, $escapeType = 'html') {
		if( is_array($varName) ) {
			return Req::escape(Req::$_merged[$varName[0]][$varName[1]], $escapeType);
		} else {
			return Req::escape(Req::$_merged[$varName], $escapeType);
		}
    }    
    
   
    function doesExist($varName) {
		if( is_array($varName) ) {
			return isset(Req::$_merged[$varName[0]][$varName[1]]);
		}
		else {
			return isset(Req::$_merged[$varName]);
		}
    } 
 
    function getDef($varName, $defValue, $escapeType = 'html') {
		if( !isset(Req::$_merged[$varName]) ) {
			return $defValue;
		} else {
			return Req::get($varName, $escapeType);
		}
    }

    function getSrvVar($varName, $defValue = '', $escapeType = 'html') {
		if( isset($_SERVER[$varName]) ) {
			return Req::escape($_SERVER[$varName], $escapeType);
		} else {
			return $defValue;
		}
    }
    
    function escape($var, $escapeType) {
		if( is_array($var) ) {
			foreach($var as $key => $value) {
				$var[$key] = Req::escape($value, $escapeType);
			}
    
			return $var;
		} else {
    	    // and start escaping
			switch( $escapeType ) {
				case 'html'  : return htmlspecialchars($var);
							   break;
				
				default : die('Unknown escape type: ' . $escapeType);
			} # switch
		} #else
    }
}

?>
