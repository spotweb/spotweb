<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 * 
 * 
 * PHP versions 4 and 5
 *
 * <pre>
 * +-----------------------------------------------------------------------+
 * |                                                                       |
 * | W3C® SOFTWARE NOTICE AND LICENSE                                      |
 * | http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231   |
 * |                                                                       |
 * | This work (and included software, documentation such as READMEs,      |
 * | or other related items) is being provided by the copyright holders    |
 * | under the following license. By obtaining, using and/or copying       |
 * | this work, you (the licensee) agree that you have read, understood,   |
 * | and will comply with the following terms and conditions.              |
 * |                                                                       |
 * | Permission to copy, modify, and distribute this software and its      |
 * | documentation, with or without modification, for any purpose and      |
 * | without fee or royalty is hereby granted, provided that you include   |
 * | the following on ALL copies of the software and documentation or      |
 * | portions thereof, including modifications:                            |
 * |                                                                       |
 * | 1. The full text of this NOTICE in a location viewable to users       |
 * |    of the redistributed or derivative work.                           |
 * |                                                                       |
 * | 2. Any pre-existing intellectual property disclaimers, notices,       |
 * |    or terms and conditions. If none exist, the W3C Software Short     |
 * |    Notice should be included (hypertext is preferred, text is         |
 * |    permitted) within the body of any redistributed or derivative      |
 * |    code.                                                              |
 * |                                                                       |
 * | 3. Notice of any changes or modifications to the files, including     |
 * |    the date changes were made. (We recommend you provide URIs to      |
 * |    the location from which the code is derived.)                      |
 * |                                                                       |
 * | THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT    |
 * | HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,    |
 * | INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR        |
 * | FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE    |
 * | OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,           |
 * | COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                               |
 * |                                                                       |
 * | COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT,        |
 * | SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE        |
 * | SOFTWARE OR DOCUMENTATION.                                            |
 * |                                                                       |
 * | The name and trademarks of copyright holders may NOT be used in       |
 * | advertising or publicity pertaining to the software without           |
 * | specific, written prior permission. Title to copyright in this        |
 * | software and any associated documentation will at all times           |
 * | remain with copyright holders.                                        |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * </pre>
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C® SOFTWARE NOTICE AND LICENSE
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/Net_NNTP
 * @see        
 * @since      File available since release 1.3.0
 */


/*****************/
/* Setup logging */
/*****************/

//
require_once "Log.php";

/**
 *
 */
class Logger extends Log
{
    var $_events = array();

    function __construct($name = '', $ident = '', $conf = null,
                 $level = PEAR_LOG_NOTICE)
    {
        $this->_id = md5(microtime());
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);
    }

    function Logger($name = '', $ident = '', $conf = null,
                 $level = PEAR_LOG_NOTICE)
    {
        $this->__construct($name, $ident, $conf, $level);
    }

    function log($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->_priority;
        }

        /* Abort early if the priority is above the maximum logging level. */
        if (!$this->_isMasked($priority)) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

	/*  */
        $this->_events[] = array('priority' => $priority, 'message' => $message);

        /* Notify observers about this log message. */
        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }

    function dump()
    {
    	if (count($this->_events) == 0) {
    	    return;
    	}
    	
        echo '<div class="debug">', "\r\n";
    	echo '<p><b><u>Log:</u></b></p>';
        foreach ($this->_events as $event) {
	    $priority = Log::priorityToString($event['priority']);
	    
    	    echo '<p class="', $priority , '">';
    	    echo '<b>' . ucfirst($priority) . '</b>: ';
    	    echo nl2br(htmlspecialchars($event['message']));
    	    echo '</p>', "\r\n";
    	}
        echo '</div>', "\r\n";
    }

    function grabPearErrors()
    {
    	require_once "PEAR.php";

	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array(&$this, 'errorHandler'));
    }

    function errorHandler($error)
    {
        global $logger;
	
    	if (!isset($logger)) {
    	    return;
    	}

		$message = '';
		
        foreach ($error->backtrace as $X) {
            if (substr($X['class'], 0, 4) == 'PEAR') {
    	        continue;
            }

            $message .= get_class($error) . ': "' . $error->getMessage() . '"';

            if ($code = $error->getCode()) {
                $message .= ' (' . $error->getCode(). ')';
            }

            $message .= ' thrown by ';

            if (isset($X['class'])) {
                $message .= $X['class'] . '::';
            }

            $message .= $X['function'] . '(';
	
            for ($args = $X['args'], $i = 0; isset($args[$i]); ) {
                $arg = $args[$i];

                switch (true) {
            	    case is_null($arg):   $message .= 'null'; break;
            	    case is_string($arg): $message .= "'" . $arg . "'"; break;
            	    case is_int($arg):    $message .= (int) $arg; break;
            	    case is_bool($arg):   $message .= $arg ? 'true' : 'false'; break;
            	    default:              $message .= $arg;
            	}

            	if (!isset($args[++$i])) {
            	    break;
            	}
    
            	$message .= ', ';
            }
    	
            $message .= ')';
            break;	
        }

        $logger->log($message, PEAR_LOG_NOTICE);
    }

}
					       
// Register connection input parameters
if ($allowOverwrite) {
    $loglevel = isset($_GET['loglevel']) && !empty($_GET['loglevel']) ? $_GET['loglevel'] : $loglevel;
}

//
$logger = new Logger(null, null, null, $loglevel);
$logger->grabPearErrors();




/********************/
/* Init NNTP client */
/********************/

//
require_once 'Net/NNTP/Client.php';

//
$nntp = new Net_NNTP_Client();

// Use logger object as logger in NNTP client
$nntp->setLogger($logger);



/***************************************************************************************/
/* Credit: Thanks to Brendan Coles <bcoles@gmail.com> (http://itsecuritysolutions.org) */
/*         for pointing out the need of url input validation to prevent cross-site     */
/*         scripting (XXS). The demo was originally never intended as more than an     */
/*         offline documentation example, but evolved and now requires security        */
/*         considerations...                                                           */
/***************************************************************************************/

//
$bodyID = $noext = preg_replace('/(.+)\..*$/', '$1', basename($_SERVER['PHP_SELF']));


/****************************************/
/* Register connection input parameters */
/****************************************/
if ($allowOverwrite) {

    if (isset($_GET['host']) && !empty($_GET['host']) && !is_array($_GET['host'])) {
        // Validate input
        if ($validateInput && !preg_match($hostValidationRegExp, $_GET['host'], $matches)) {
            error("Error: Invalid host '".htmlentities($_GET['host'])."' !");
        }
        // Actually set internal variable...
        $host = $_GET['host'];
    }

    if ($allowPortOverwrite) {
        if (isset($_GET['port']) && !empty($_GET['port']) && !is_array($_GET['port'])) {
            // TODO: add validation...
            $port = $_GET['port'];
        }
    }

    if (isset($_GET['encryption']) && !empty($_GET['encryption']) && !is_array($_GET['encryption'])) {
        // TODO: add validation...
        $encryption = $_GET['encryption'];
    }

    if (isset($_GET['user']) && !empty($_GET['user']) && !is_array($_GET['user'])) {
        // TODO: add validation...
        $user = $_GET['user'];
    }

    if (isset($_GET['pass']) && !empty($_GET['pass']) && !is_array($_GET['pass'])) {
        // TODO: add validation...
        $pass = $_GET['pass'];
    }

    // Not really a connection parameter, but it still here ;-)
    if (isset($_GET['wildmat']) && !empty($_GET['wildmat']) && !is_array($_GET['wildmat'])) {
        // TODO: add validation...
        $wildmat = $_GET['wildmat'];
    }
}


/***********************************/
/* Register other input parameters */
/***********************************/

$group = null;
if (isset($_GET['group']) && !empty($_GET['group']) && !is_array($_GET['group'])) {
    // Validate input
    if ($validateInput && !preg_match($groupValidationRegExp, $_GET['group'], $matches)) {
        error("Error: Invalid group '".htmlentities($_GET['group'])."' !");
    }
    // Actually set internal variable...
    $group = $_GET['group'];
}

$action = null;
if (isset($_GET['action']) && !empty($_GET['action']) && !is_array($_GET['action'])) {
    // TODO: add validation...
    $action = strtolower($_GET['action']);
}

$article = null;
if (isset($_GET['article']) && !empty($_GET['article']) && !is_array($_GET['article'])) {
    // Validate input
    if ($validateInput && !preg_match($articleValidationRegExp, $_GET['article'], $matches)) {
        error("Error: Invalid article '".htmlentities($_GET['article'])."' !");
    }
    // Actually set internal variable...
    $article = $_GET['article'];
}

$format = 'html';
if (isset($_GET['format']) && !empty($_GET['format']) && !is_array($_GET['format'])) {
    // TODO: add validation...
    $format = $_GET['format'];
}



/********************/
/*                  */
/********************/

$starttls = ($encryption == 'starttls');
if ($starttls) {
    $encryption = null;
}



/*******************/
/* Misc. functions */
/*******************/

function error($text)
{
    //
    extract($GLOBALS);
	
    include 'header.inc.php';
    echo '<h2 class="error">', $text, '</h2>';
    $logger->dump();
    include 'footer.inc.php';
    die();
}

function query($query = null)
{
    //
    extract($GLOBALS);

    if (!$allowOverwrite || !$frontpage) {
	return $query;
    }

    $full = 'host=' . urlencode($host) . 
            '&encryption=' . urlencode($encryption) . 
            ($allowPortOverwrite ? '&port=' . urlencode($port) : '') . 
            '&user=' . urlencode($user) . 
            '&pass=' . urlencode($pass) . 
            '&wildmat=' . urlencode($wildmat) . 
            '&loglevel=' . urlencode($loglevel);

    if (!empty($query)) {
        $full .= '&' . $query;
    }

    return $full;
}

?>
