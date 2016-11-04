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
 * | W3C� SOFTWARE NOTICE AND LICENSE                                      |
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
 * @copyright  2002-2011 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C� SOFTWARE NOTICE AND LICENSE
 * @version    SVN: $Id: Client.php 306619 2010-12-24 12:16:07Z heino $
 * @link       http://pear.php.net/package/Net_NNTP
 * @see        
 *
 * @filesource
 */


// {{{ Net_NNTP_Client

/**
 * Implementation of the client side of NNTP (Network News Transfer Protocol)
 *
 * The Net_NNTP_Client class is a frontend class to the Net_NNTP_Protocol_Client class.
 *
 * @category   Net
 * @package    Net_NNTP
 * @version    package: 1.5.0RC1 (beta) 
 * @version    api: 0.9.0 (alpha)
 * @access     public
 * @see        Net_NNTP_Protocol_Client
 */
class Net_NNTP_Client extends Net_NNTP_Protocol_Client
{
    // {{{ properties

    /**
     * Information summary about the currently selected group.
     *
     * @var array
     * @access private
     */
    var $_selectedGroupSummary = null;

    /**
     * 
     *
     * @var array
     * @access private
     * @since 1.3.0
     */
    var $_overviewFormatCache = null;
	
	/**
	 * 
	 * 
	 * @var array
	 * @access private
	 */
	var $_supportXzver = false; # disable xzver support for now, causes too many incompatibilies (spotweb github issue #1133)

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/constructor.php}
     *
     * @access public
     */
    function __construct()
    {
    	parent::__construct(); //Net_NNTP_Protocol_Client();
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to a server.
     *
     * xxx
     * 
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/connect.php}
     *
     * @param string	$host	(optional) The hostname og IP-address of the NNTP-server to connect to, defaults to localhost.
     * @param mixed	$encryption	(optional) false|'tls'|'ssl', defaults to false.
     * @param int	$port	(optional) The port number to connect to, defaults to 119 or 563 dependng on $encryption.
     * @param int	$timeout	(optional) 
     *
     * @return mixed <br>
     *  - (bool)	True when posting allowed, otherwise false
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::disconnect()
     * @see Net_NNTP_Client::authenticate()
     */
    function connect($host = null, $encryption = null, $port = null, $timeout = null)
    {
    	// v1.0.x API
    	if (is_int($encryption)) {
	    trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: connect() !', E_USER_NOTICE);
    	    $port = $encryption;
	    $encryption = null;
    	}

    	return parent::connect($host, $encryption, $port, $timeout);
    }

    // }}}
    // {{{ disconnect()

    /**
     * Disconnect from server.
     *
     * @return mixed <br>
     *  - (bool)	
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::connect()
     */
    function disconnect()
    {
        return parent::disconnect();
    }

    // }}}
    // {{{ quit()

    /**
     * Deprecated alias for disconnect().
     *
     * @access public
     * @deprecated 
     * @ignore
     */
    function quit()
    {
        return $this->disconnect();
    }

    // }}}
    // {{{ authenticate()

    /**
     * Authenticate.
     *
     * xxx
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/authenticate.php}
     *
     * @param string	$user	The username
     * @param string	$pass	The password
     *
     * @return mixed <br>
     *  - (bool)	True on successful authentification, otherwise false
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::connect()
     */
    function authenticate($user, $pass)
    {
        // Username is a must...
        if ($user == null) {
            $this->throwError('No username supplied', null);
        }

        return $this->cmdAuthinfo($user, $pass);
    }

    // }}}
    // {{{ selectGroup()

    /**
     * Selects a group.
     * 
     * Moves the servers 'currently selected group' pointer to the group 
     * a new group, and returns summary information about it.
     *
     * <b>Non-standard!</b><br>
     * When using the second parameter, 
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/selectGroup.php}
     *
     * @param string	$group	Name of the group to select
     * @param mixed	$articles	(optional) experimental! When true the article numbers is returned in 'articles'
     *
     * @return mixed <br>
     *  - (array)	Summary about the selected group 
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getGroups()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::count()
     */
    function selectGroup($group, $articles = false)
    {
	// Select group (even if $articles is set, since many servers does not select groups when the listgroup command is run)
    	$summary = $this->cmdGroup($group);

    	// Store group info in the object
    	$this->_selectedGroupSummary = $summary;

	// 
    	if ($articles !== false) {
    	    $summary2 = $this->cmdListgroup($group, ($articles === true ? null : $articles));

	    // Make sure the summary array is correct...
    	    if ($summary2['group'] == $group) {
    	    	$summary = $summary2;

	    // ... even if server does not include summary in status reponce.
    	    } else {
    	    	$summary['articles'] = $summary2['articles'];
    	    }
    	}
	
    	return $summary;
    }

    // }}}
    // {{{ selectPreviousArticle()

    /**
     * Select the previous article.
     *
     * Select the previous article in current group.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/selectPreviousArticle.php}
     *
     * @param int	$_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number, if $ret=0 (default)
     *  - (string)	Message-id, if $ret=1
     *  - (array)	Both article number and message-id, if $ret=-1
     *  - (bool)	False if no prevoius article exists
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::selectArticle()
     * @see Net_NNTP_Client::selectNextArticle()
     */
    function selectPreviousArticle($_ret = 0)
    {
		try {
			$response = $this->cmdLast();
		} catch(Exception $x) {
			return false;
		} # catch

    	switch ($_ret) {
    	    case -1:
    	    	return array('Number' => (int) $response[0], 'Message-ID' =>  (string) $response[1]);
    	    	break;
    	    case 0:
    	        return (int) $response[0];
    	    	break;
    	    case 1:
    	        return (string) $response[1];
    	    	break;
    	    default:
		//error(); // ...
	}
    }

    // }}}
    // {{{ selectNextArticle()

    /**
     * Select the next article.
     *
     * Select the next article in current group.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/selectNextArticle.php}
     *
     * @param int	$_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number, if $ret=0 (default)
     *  - (string)	Message-id, if $ret=1
     *  - (array)	Both article number and message-id, if $ret=-1
     *  - (bool)	False if no further articles exist
     *  - (object)	Pear_Error on unexpected failure
     * @access public
     * @see Net_NNTP_Client::selectArticle()
     * @see Net_NNTP_Client::selectPreviousArticle()
     */
    function selectNextArticle($_ret = 0)
    {
        $response = $this->cmdNext();

    	switch ($_ret) {
    	    case -1:
    	    	return array('Number' => (int) $response[0], 'Message-ID' =>  (string) $response[1]);
    	    	break;
    	    case 0:
    	        return (int) $response[0];
    	    	break;
    	    case 1:
    	        return (string) $response[1];
    	    	break;
    	    default:
		//error(); // ...
	}
    }

    // }}}
    // {{{ selectArticle()

    /**
     * Selects an article by article message-number.
     *
     * xxx
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/selectArticle.php}
     *
     * @param mixed	$article	The message-number (on the server) of
     *                                  the article to select as current article.
     * @param int	$_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number
     *  - (bool)	False if article doesn't exists
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::selectNextArticle()
     * @see Net_NNTP_Client::selectPreviousArticle()
     */
    function selectArticle($article = null, $_ret = 0)
    {
        $response = $this->cmdStat($article);

    	switch ($_ret) {
    	    case -1:
    	    	return array('Number' => (int) $response[0], 'Message-ID' =>  (string) $response[1]);
    	    	break;
    	    case 0:
    	        return (int) $response[0];
    	    	break;
    	    case 1:
    	        return (string) $response[1];
    	    	break;
    	    default:
		//error(); // ...
	}
    }

    // }}}
    // {{{ getArticle()

    /**
     * Fetch article into transfer object.
     *
     * Select an article based on the arguments, and return the entire
     * article (raw data).
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getArticle.php}
     *
     * @param mixed	$article	(optional) Either the message-id or the
     *                                  message-number on the server of the
     *                                  article to fetch.
     * @param bool	$implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (array)	Complete article (when $implode is false)
     *  - (string)	Complete article (when $implode is true)
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getHeader()
     * @see Net_NNTP_Client::getBody()
     */
    function getArticle($article = null, $implode = false)
    {
    	// v1.1.x API
    	if (is_string($implode)) {
    	    trigger_error('You are using deprecated API v1.1 in Net_NNTP_Client: getHeader() !', E_USER_NOTICE);
		     
    	    $class = $implode;
    	    $implode = false;

    	    if (!class_exists($class)) {
    	        $this->throwError("Class '$class' does not exist!");
	    }
    	}

        $data = $this->cmdArticle($article);

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	// v1.1.x API
    	if (isset($class)) {
    	    return $obj = new $class($data);
    	}

    	//
    	return $data;
    }

    // }}}
    // {{{ getHeader()

    /**
     * Fetch article header.
     *
     * Select an article based on the arguments, and return the article
     * header (raw data).
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getHeader.php}
     *
     * @param mixed	$article	(optional) Either message-id or message
     *                                  number of the article to fetch.
     * @param bool	$implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (bool)	False if article does not exist
     *  - (array)	Header fields (when $implode is false)
     *  - (string)	Header fields (when $implode is true)
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getArticle()
     * @see Net_NNTP_Client::getBody()
     */
    function getHeader($article = null, $implode = false)
    {
    	// v1.1.x API
    	if (is_string($implode)) {
    	    trigger_error('You are using deprecated API v1.1 in Net_NNTP_Client: getHeader() !', E_USER_NOTICE);
		     
    	    $class = $implode;
    	    $implode = false;

    	    if (!class_exists($class)) {
    	        $this->throwError("Class '$class' does not exist!");
	    }
    	}

        $data = $this->cmdHead($article);

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	// v1.1.x API
    	if (isset($class)) {
    	    return $obj = new $class($data);
    	}

    	//
    	return $data;
    }

    // }}}
    // {{{ getBody()

    /**
     * Fetch article body.
     *
     * Select an article based on the arguments, and return the article
     * body (raw data).
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getBody.php}
     *
     * @param mixed	$article	(optional) Either the message-id or the
     *                                  message-number on the server of the
     *                                  article to fetch.
     * @param bool	$implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (array)	Message body (when $implode is false)
     *  - (string)	Message body (when $implode is true)
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getHeader()
     * @see Net_NNTP_Client::getArticle()
     */
    function getBody($article = null, $implode = false)
    {
    	// v1.1.x API
    	if (is_string($implode)) {
    	    trigger_error('You are using deprecated API v1.1 in Net_NNTP_Client: getHeader() !', E_USER_NOTICE);
		     
    	    $class = $implode;
    	    $implode = false;

    	    if (!class_exists($class)) {
    	        $this->throwError("Class '$class' does not exist!");
	    }
    	}

        $data = $this->cmdBody($article);
		
    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	// v1.1.x API
    	if (isset($class)) {
    	    return $obj = new $class($data);
    	}

    	//
    	return $data;
    }

    // }}}
    // {{{ post()

    /**
     * Post a raw article to a number of groups.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/post.php}
     *
     * @param mixed	$article	<br>
     *  - (string) Complete article in a ready to send format (lines terminated by LFCR etc.)
     *  - (array) First key is the article header, second key is article body - any further keys are ignored !!!
     *  - (mixed) Something 'callable' (which must return otherwise acceptable data as replacement)
     *
     * @return mixed <br>
     *  - (string)	Server response
     *  - (object)	Pear_Error on failure
     * @access public
     * @ignore
     */
    function post($article)
    {
    	// API v1.0
    	if (func_num_args() >= 4) {

    	    // 
    	    trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: post() !', E_USER_NOTICE);

    	    //
    	    $groups = func_get_arg(0);
    	    $subject = func_get_arg(1);
    	    $body = func_get_arg(2);
    	    $from = func_get_arg(3);
    	    $additional = func_get_arg(4);

    	    return $this->mail($groups, $subject, $body, "From: $from\r\n" . $additional);
    	}

    	// Only accept $article if array or string
    	if (!is_array($article) && !is_string($article)) {
    	    $this->throwError('Ups', null, 0);
    	}

    	// Check if server will receive an article
    	$post = $this->cmdPost();

    	// Get article data from callback function
    	if (is_callable($article)) {
    	    $article = call_user_func($article);
    	}

    	// Actually send the article
    	return $this->cmdPost2($article);
    }

    // }}}
    // {{{ mail()

    /**
     * Post an article to a number of groups - using same parameters as PHP's mail() function.
     *
     * Among the aditional headers you might think of adding could be:
     * "From: <author-email-address>", which should contain the e-mail address
     * of the author of the article.
     * Or "Organization: <org>" which contain the name of the organization
     * the post originates from.
     * Or "NNTP-Posting-Host: <ip-of-author>", which should contain the IP-address
     * of the author of the post, so the message can be traced back to him.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/mail.php}
     *
     * @param string	$groups	The groups to post to.
     * @param string	$subject	The subject of the article.
     * @param string	$body	The body of the article.
     * @param string	$additional	(optional) Additional header fields to send.
     *
     * @return mixed <br>
     *  - (string)	Server response
     *  - (object)	Pear_Error on failure
     * @access public
     */
    function mail($groups, $subject, $body, $additional = null)
    {
    	// Check if server will receive an article
    	$post = $this->cmdPost();

        // Construct header
        $header  = "Newsgroups: $groups\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "X-poster: PEAR::Net_NNTP v1.5.0RC1 (beta)\r\n";
    	if ($additional !== null) {
    	    $header .= $additional;
    	}
        $header .= "\r\n";

    	// Actually send the article
    	return $this->cmdPost2(array($header, $body));
    }

    // }}}
    // {{{ getDate()

    /**
     * Get the server's internal date
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getDate.php}
     *
     * @param int	$format	(optional) Determines the format of returned date:
     *                           - 0: return string
     *                           - 1: return integer/timestamp
     *                           - 2: return an array('y'=>year, 'm'=>month,'d'=>day)
     *
     * @return mixed <br>
     *  - (mixed)	
     *  - (object)	Pear_Error on failure
     * @access public
     */
    function getDate($format = 1)
    {
        $date = $this->cmdDate();

    	switch ($format) {
    	    case 0:
    	        return $date;
    	        break;
    	    case 1:
    		return strtotime(substr($date, 0, 8).' '.substr($date, 8, 2).':'.substr($date, 10, 2).':'.substr($date, 12, 2));
    	        break;
    	    case 2:
    	        return array('y' => substr($date, 0, 4),
    	                     'm' => substr($date, 4, 2),
    	                     'd' => substr($date, 6, 2));
    	        break;
    	    default:
		error();
    	}
    }

    // }}}
    // {{{ getNewGroups()

    /**
     * Get new groups since a date.
     *
     * Returns a list of groups created on the server since the specified date
     * and time.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getNewGroups.php}
     *
     * @param mixed	$time	<br>
     *  - (integer)	A timestamp
     *  - (string)	Somthing parseable by strtotime() like '-1 week'
     * @param string	$distributions	(optional) 
     *
     * @return mixed <br>
     *  - (array)	
     *  - (object)	Pear_Error on failure
     * @access public
     */
    function getNewGroups($time, $distributions = null)
    {
    	switch (true) {
    	    case is_integer($time):
    	    	break;
    	    case is_string($time):
    	    	$time = strtotime($time);
    	    	if ($time === false || ($time === -1 && version_compare(phpversion(), '5.1.0', '<'))) {
    	    	    $this->throwError('$time could not be converted into a timestamp!', null, 0);
    	    	}
    	    	break;
    	    default:
    	    	trigger_error('$time must be either a string or an integer/timestamp!', E_USER_ERROR);
    	}

    	return $this->cmdNewgroups($time, $distributions);
    }

    // }}}
    // {{{ getNewArticles()

    /**
     * Get new articles since a date.
     *
     * Returns a list of message-ids of new articles (since the specified date
     * and time) in the groups whose names match the wildmat
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getNewArticles.php}
     *
     * @param mixed	$time	<br>
     *  - (integer)	A timestamp
     *  - (string)	Somthing parseable by strtotime() like '-1 week'
     * @param string	$groups	(optional) 
     * @param string	$distributions	(optional) 
     *
     * @return mixed <br>
     *  - (array)	
     *  - (object)	Pear_Error on failure
     * @access public
     * @since 1.3.0
     */
    function getNewArticles($time, $groups = '*', $distribution = null)
    {
    	switch (true) {
    	    case is_integer($time):
    	    	break;
    	    case is_string($time):
    	    	$time = strtotime($time);
    	    	if ($time === false || ($time === -1 && version_compare(php_version(), '5.1.0', '<'))) {
    	    	    $this->throwError('$time could not be converted into a timestamp!', null, 0);
		}
    	    	break;
    	    default:
    	    	trigger_error('$time must be either a string or an integer/timestamp!', E_USER_ERROR);
    	}

    	return $this->cmdNewnews($time, $groups, $distribution);
    }

    // }}}
    // {{{ getGroups()

    /**
     * Fetch valid groups.
     *
     * Returns a list of valid groups (that the client is permitted to select)
     * and associated information.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getGroups.php}
     *
     * @return mixed <br>
     *  - (array)	Nested array with information about every valid group
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getDescriptions()
     * @see Net_NNTP_Client::selectGroup()
     */
    function getGroups($wildmat = null)
    {
    	$backup = false;

    	// Get groups
		try {
			$groups = $this->cmdListActive($wildmat);
		} catch(NntpException $x) {
    	    switch ($x->getCode()) {
    	    	case 500:
    	    	case 501:
    	    	    $backup = true;
		    break;
    		default:
    	    	    throw $x;
    	    }
		} # catch

    	// 
    	if ($backup == true) {

    	    // 
    	    if (!is_null($wildmat)) {
    	    	$this->throwError("The server does not support the 'LIST ACTIVE' command, and the 'LIST' command does not support the wildmat parameter!", null, null);
    	    }
	    
    	    // 
			$groups2 = $this->cmdList();
	}

    	return $groups;
    }

    // }}}
    // {{{ getDescriptions()

    /**
     * Fetch all known group descriptions.
     *
     * Fetches a list of known group descriptions - including groups which
     * the client is not permitted to select.
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getDescriptions.php}
     *
     * @param mixed	$wildmat	(optional) 
     *
     * @return mixed <br>
     *  - (array)	Associated array with descriptions of known groups
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getGroups()
     */
    function getDescriptions($wildmat = null)
    {
    	if (is_array($wildmat)) {
	    $wildmat = implode(',', $wildmat);
    	}

    	// Get group descriptions
    	$descriptions = $this->cmdListNewsgroups($wildmat);

    	// TODO: add xgtitle as backup
	
    	return $descriptions;
    }

    // }}}
    // {{{ getOverview()

    /**
     * Fetch an overview of article(s) in the currently selected group.
     *
     * Returns the contents of all the fields in the database for a number
     * of articles specified by either article-numnber range, a message-id,
     * or nothing (indicating currently selected article).
     *
     * The first 8 fields per article is always as follows:
     *   - 'Number' - '0' or the article number of the currently selected group.
     *   - 'Subject' - header content.
     *   - 'From' - header content.
     *   - 'Date' - header content.
     *   - 'Message-ID' - header content.
     *   - 'References' - header content.
     *   - ':bytes' - metadata item.
     *   - ':lines' - metadata item.
     *
     * The server may send more fields form it's database...
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getOverview.php}
     *
     * @param mixed	$range	(optional)
     *                          - '<message number>'
     *                          - '<message number>-<message number>'
     *                          - '<message number>-'
     *                          - '<message-id>'
     * @param boolean	$_names	(optional) experimental parameter! Use field names as array kays
     * @param boolean	$_forceNames	(optional) experimental parameter! 
     *
     * @return mixed <br>
     *  - (array)	Nested array of article overview data
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getHeaderField()
     * @see Net_NNTP_Client::getOverviewFormat()
     */
    function getOverview($range = null, $_names = true, $_forceNames = true)
    {
    	// API v1.0
    	switch (true) {
	    // API v1.3
	    case func_num_args() != 2:
	    case is_bool(func_get_arg(1)):
	    case !is_int(func_get_arg(1)) || (is_string(func_get_arg(1)) && $this->isDigit(func_get_arg(1))):
	    case !is_int(func_get_arg(0)) || (is_string(func_get_arg(0)) && $this->isDigit(func_get_arg(0))):
		break;

	    default:
    	    	// 
    	        trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: getOverview() !', E_USER_NOTICE);

    	        // Fetch overview via API v1.3
    	        $overview = $this->getOverview(func_get_arg(0) . '-' . func_get_arg(1), true, false);

    	        // Create and return API v1.0 compliant array
    	        $articles = array();
    	        foreach ($overview as $article) {

    	    	    // Rename 'Number' field into 'number'
    	    	    $article = array_merge(array('number' => array_shift($article)), $article);
		
    	    	    // Use 'Message-ID' field as key
    	            $articles[$article['Message-ID']] = $article;
    	        }
    	        return $articles;
    	}

		// Does the server support XZVER
		if (is_null($this->_supportXzver)) {
			try {
				$this->_supportXzver = (array_search('XZVER', $this->cmdCapabilities()) !== false);
			} catch(Exception $x) {
				$this->_supportXzver = false;
			} # try
		} # if
		
    	// Fetch overview from server
		if ($this->_supportXzver) {
			try {
				$overview = $this->cmdXZver($range);
			} catch(Exception $x) {
				# If cmdXZver throws an fatal error, escalate it
				if (!$this->_isConnected()) {
					throw $x;
				} # if
				
				$overview = $this->cmdXOver($range);
				$this->_supportXzver = false;
			} # catch
		} else {
			$overview = $this->cmdXOver($range);
		} # else

    	// Use field names from overview format as keys?
    	if ($_names) {

    	    // Already cached?
    	    if (is_null($this->_overviewFormatCache)) {
    	    	// Fetch overview format
    	        $format = $this->getOverviewFormat($_forceNames, true);

    	    	// Prepend 'Number' field
    	    	$format = array_merge(array('Number' => false), $format);

    	    	// Cache format
    	        $this->_overviewFormatCache = $format;

    	    // 
    	    } else {
    	        $format = $this->_overviewFormatCache;
    	    }

    	    // Loop through all articles
            foreach ($overview as $key => $article) {

    	        // Copy $format
    	        $f = $format;

    	        // Field counter
    	        $i = 0;
		
		// Loop through forld names in format
    	        foreach ($f as $tag => $full) {

    	    	    //
    	            $f[$tag] = $article[$i++];

    	            // If prefixed by field name, remove it
    	            if ($full === true) {
	                $f[$tag] = ltrim( substr($f[$tag], strpos($f[$tag], ':') + 1), " \t");
    	            }
    	        }

    	        // Replace article 
	        $overview[$key] = $f;
    	    }
    	}

    	//
    	switch (true) {

    	    // Expect one article
    	    case is_null($range);
    	    case is_int($range);
            case is_string($range) && $this->isDigit($range):
    	    case is_string($range) && substr($range, 0, 1) == '<' && substr($range, -1, 1) == '>':
    	        if (count($overview) == 0) {
    	    	    return false;
    	    	} else {
    	    	    return reset($overview);
    	    	}
    	    	break;

    	    // Expect multiple articles
    	    default:
    	    	return $overview;
    	}
    }

    // }}}
    // {{{ getOverviewFormat()

    /**
     * Fetch names of fields in overview database
     *
     * Returns a description of the fields in the database for which it is consistent.
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getOveriewFormat.php}
     *
     * @return mixed <br>
     *  - (array)	Overview field names
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getOverview()
     */
    function getOverviewFormat($_forceNames = true, $_full = false)
    {
        $format = $this->cmdListOverviewFmt();

    	// Force name of first seven fields
    	if ($_forceNames) {
    	    array_splice($format, 0, 7);
    	    $format = array_merge(array('Subject'    => false,
    	                                'From'       => false,
    	                                'Date'       => false,
    	                                'Message-ID' => false,
    	    	                        'References' => false,
    	                                ':bytes'     => false,
    	                                ':lines'     => false), $format);
    	}

    	if ($_full) {
    	    return $format;
    	} else {
    	    return array_keys($format);
    	}
    }

    // }}}
    // {{{ getHeaderField()

    /**
     * Fetch content of a header field from message(s).
     *
     * Retreives the content of specific header field from a number of messages.
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getHeaderField.php}
     *
     * @param string	$field	The name of the header field to retreive
     * @param mixed	$range	(optional)
     *                            '<message number>'
     *                            '<message number>-<message number>'
     *                            '<message number>-'
     *                            '<message-id>'
     *
     * @return mixed <br>
     *  - (array)	Nested array of 
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getOverview()
     * @see Net_NNTP_Client::getReferences()
     */
    function getHeaderField($field, $range = null)
    {
    	$fields = $this->cmdXHdr($field, $range);

    	//
    	switch (true) {

    	    // Expect one article
    	    case is_null($range);
    	    case is_int($range);
            case is_string($range) && $this->isDigit($range):
    	    case is_string($range) && substr($range, 0, 1) == '<' && substr($range, -1, 1) == '>':

    	        if (count($fields) == 0) {
    	    	    return false;
    	    	} else {
    	    	    return reset($fields);
    	    	}
    	    	break;

    	    // Expect multiple articles
    	    default:
    	    	return $fields;
    	}
    }

    // }}}







    // {{{ getGroupArticles()

    /**
     *
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getGroupArticles.php}
     *
     * @param mixed	$range	(optional) Experimental!
     *
     * @return mixed <br>
     *  - (array)	
     *  - (object)	Pear_Error on failure
     * @access public
     * @since 1.3.0
     */
    function getGroupArticles($range = null)
    {
        $summary = $this->cmdListgroup();

    	// Update summary cache if group was also 'selected'
    	if ($summary['group'] !== null) {
    	    $this->_selectedGroupSummary = $summary;
    	}
	
    	//
    	return $summary['articles'];
    }

    // }}}
    // {{{ getReferences()

    /**
     * Fetch reference header field of message(s).
     *
     * Retrieves the content of the references header field of messages via
     * either the XHDR ord the XROVER command.
     *
     * Identical to getHeaderField('References').
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/getReferences.php}
     *
     * @param mixed	$range	(optional)
     *                            '<message number>'
     *                            '<message number>-<message number>'
     *                            '<message number>-'
     *                            '<message-id>'
     *
     * @return mixed <br>
     *  - (array)	Nested array of references
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::getHeaderField()
     */
    function getReferences($range = null)
    {
    	$backup = false;

		try {
			$references = $this->cmdXHdr('References', $range);
		} 
		catch(NntpException $x) {
    	    switch ($x->getCode()) {
    	    	case 500:
    	    	case 501:
    	    	    $backup = true;
		    break;
    		default:
    	    	    throw $x;
    	    }
    	}

    	if (true && (is_array($references) && count($references) == 0)) {
    	    $backup = true;
    	}

    	if ($backup == true) {
			$references2 = $this->cmdXROver($range);
   	    	$references = $references2;
	}

    	if (is_array($references)) {
    	    foreach ($references as $key => $val) {
    	        $references[$key] = preg_split("/ +/", trim($val), -1, PREG_SPLIT_NO_EMPTY);
    	    }
	}

    	//
    	switch (true) {

    	    // Expect one article
    	    case is_null($range);
    	    case is_int($range);
    	    case is_string($range) && $this->isDigit($range):
    	    case is_string($range) && substr($range, 0, 1) == '<' && substr($range, -1, 1) == '>':
    	        if (count($references) == 0) {
    	    	    return false;
    	    	} else {
    	    	    return reset($references);
    	    	}
    	    	break;

    	    // Expect multiple articles
    	    default:
    	    	return $references;
    	}
    }

    // }}}





    // {{{ count()

    /**
     * Number of articles in currently selected group
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/count.php}
     *
     * @return mixed <br>
     *  - (string)	the number of article in group
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
     */
    function count()
    {
        return $this->_selectedGroupSummary['count'];
    }

    // }}}
    // {{{ last()

    /**
     * Maximum article number in currently selected group
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/last.php}
     *
     * @return mixed <br>
     *  - (string)	the last article's number
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
     */
    function last()
    {
    	return $this->_selectedGroupSummary['last'];
    }

    // }}}
    // {{{ first()

    /**
     * Minimum article number in currently selected group
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/first.php}
     *
     * @return mixed <br>
     *  - (string)	the first article's number
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
     */
    function first()
    {
    	return $this->_selectedGroupSummary['first'];
    }

    // }}}
    // {{{ group()

    /**
     * Currently selected group
     *
     * <b>Usage example:</b>
     * {@example docs/examples/phpdoc/group.php}
     *
     * @return mixed <br>
     *  - (string)	group name
     *  - (object)	Pear_Error on failure
     * @access public
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
     */
    function group()
    {
    	return $this->_selectedGroupSummary['group'];
    }

    // }}}







    // {{{ isConnected()

    /**
     * Test whether a connection is currently open or closed.
     *
     * @return bool	True if connected, otherwise false
     * @access public
     * @see Net_NNTP_Client::connect()
     * @see Net_NNTP_Client::quit()
     * @deprecated	since v1.3.0 due to use of protected method: Net_NNTP_Protocol_Client::isConnected()
     * @ignore
     */
    function isConnected()
    {
	trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: isConnected() !', E_USER_NOTICE);
        return parent::_isConnected();
    }

    // }}}
    // {{{ getArticleRaw()

    /**
     * Deprecated alias for getArticle()
     *
     * @deprecated
     * @ignore
     */
    function getArticleRaw($article, $implode = false)
    {
    	trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: getArticleRaw() !', E_USER_NOTICE);
    	return $this->getArticle($article, $implode);
    }

    // }}}
    // {{{ getHeaderRaw()

    /**
     * Deprecated alias for getHeader()
     *
     * @deprecated
     * @ignore
     */
    function getHeaderRaw($article = null, $implode = false)
    {
    	trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: getHeaderRaw() !', E_USER_NOTICE);
    	return $this->getHeader($article, $implode);
    }

    // }}}
    // {{{ getBodyRaw()

    /**
     * Deprecated alias for getBody()
     *
     * @deprecated
     * @ignore
     */
    function getBodyRaw($article = null, $implode = false)
    {
    	trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: getBodyRaw() !', E_USER_NOTICE);
        return $this->getBody($article, $implode);
    }

    // }}}
    // {{{ getNewNews()

    /**
     * Deprecated alias for getNewArticles()
     *
     * @deprecated
     * @ignore
     */
    function getNewNews($time, $groups = '*', $distribution = null)
    {
    	trigger_error('You are using deprecated API v1.1 in Net_NNTP_Client: getNewNews() !', E_USER_NOTICE);
    	return $this->getNewArticles($time, $groups, $distribution);
    }

    // }}}
    // {{{ getReferencesOverview()

    /**
     * Deprecated alias for getReferences()
     *
     * @deprecated
     * @ignore
     */
    function getReferencesOverview($first, $last)
    {
	trigger_error('You are using deprecated API v1.0 in Net_NNTP_Client: getReferencesOverview() !', E_USER_NOTICE);
    	return $this->getReferences($first . '-' . $last);
    }

    // }}}

    private function isDigit($s) {
        for($i = 0; $i < strlen($s); $i++) {
            if (strpos('0123456789', $s[$i]) === false) {
                return false;
            } # if
        } # for

        return true;
    } # isDigit
}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


