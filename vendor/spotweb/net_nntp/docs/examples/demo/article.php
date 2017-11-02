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


/**
 *
 */
require_once 'config.inc.php';

/**
 *
 */
require_once 'common.inc.php';


/* Validate input */
/*

// Must have either $messageNum or $messageID
if (is_null($messageNum) and is_null($messageID)) {
    error('Error: Nither message number nor message-id provided!');
}

// Only $messageNum OR $messageID
if (!is_null($messageNum) and !is_null($messageId)) {
    error('Error: Both message-id _AND_ message number provided!');
}

// $messageNum requires $group
if (is_null($messageNum) and !is_null($group)) {
    error('Error: Message number requires group!');
}
*/

//
$messageID  = $group === null ? $article : null;
#$article    = $group !== null ? $article : null;

/* Prepare breadcrumbs */

$breadcrumbs = array();
$breadcrumbs['Frontpage'] =  './index.php?' . query();
$breadcrumbs['groups @ '  . ($host == null ? 'localhost' : $host)] = './groups.php?' . query();
if ($messageID !== null) {
    $breadcrumbs['Article: '.htmlentities($messageID)]   = null;
} else {
    $breadcrumbs['group: '.$group] =  './group.php?' . @query("group=$group&from=$from&next=$next");
    $breadcrumbs['Article: #'.$article] = null;
}


// Connect
$posting = $nntp->connect($host, $encryption, $port);
if (PEAR::isError($posting)) {
    error('Unable to connect to NNTP server: ' . $posting->getMessage());
}


// Start TLS encryption
if ($starttls) {
    $R = $nntp->cmdStartTLS();
    if (PEAR::isError($R)) {
        error('Unable to connect to NNTP server: ' . $R->getMessage());
    }
}

// Authenticate
if (!is_null($user) && !is_null($pass)) {
    $authenticated = $nntp->authenticate($user, $pass);
    if (PEAR::isError($authenticated)) {
        error('Unable to authenticate: ' . $authenticated->getMessage());
    }
}

// If asked for a article in a group, select group then article
if ($messageID === null) {

    // Select group
    $summary = $nntp->selectGroup($group);
    if (PEAR::isError($summary)) {
        error($summary->getMessage());
    }

    // Select article
    $article = $nntp->selectArticle($article);
    if (PEAR::isError($article)) {
        error($article->getMessage());
    }

    if ($article === false) {
        error('The article is not avalible on the server!');
    }

    // Fetch overview
    $overview = $nntp->getOverview();
    if (PEAR::isError($overview)) {
        $logger->warning('Error fetching overview (Server response: ' . $overview->getMessage() . ')');

    	// 
        $overview = false;
    }

    // Fetch 'Newsgroups' header field
    $groups = $nntp->getHeaderField('Newsgroups');
    if (PEAR::isError($groups)) {
        $logger->warning('Error fetching \'Newsgroups\' header field (Server response: ' . $groups->getMessage() . ')');

   	// 
        $groups = false;
    }
}


// Fetch header
$header = $nntp->getHeader($messageID);
if (PEAR::isError($header)) {
    error('Error fetching header (Server response: ', $header->getMessage(), ')');
}
if ($header === false) {
    error('The article is not avalible on the server!');
}



// Fetch body
$body = $nntp->getBody($messageID);
if (PEAR::isError($body)) {
    error('Error fetching body (Server response: ', $body->getMessage(), ')');
}


// Close connection
$nntp->disconnect();



/* ... */

/**
 *
 */
function x($header, $fieldname, $index = 0)
{
    //
    $fieldname = strtolower($fieldname);

    //
    for ($i = 0, $j = 0 ; $i < count($header) ; $i++) {

    	//
    	$line = $header[$i];
	
    	//
    	@list($tag, $value) = explode(": ", $line, 2);
    	if (strtolower($tag) != $fieldname) {
    	    continue;
    	}

    	// Skip if $index not reached
    	if ($j++ < $index) {
    	    continue;
	}

    	// Append folded lines...
	while (($next = $header[++$i]) && ($next[0] == ' ' || $next[0] == "\t")) {
	    $value .= ' ' . ltrim($next, " \t");
	}

	// Set $group
	return $value;
    }
}


//
if (!empty($overview)) {
    $subject    = $overview['Subject'];
    $from       = $overview['From'];
    $date       = $overview['Date'];
    $references = $overview['References'];

} else {
    $subject    = x($header, 'Subject');
    $from       = x($header, 'From');
    $date       = x($header, 'Date');
    $references = x($header, 'References');

    if (empty($references)) {
        $references = x($header, 'In-reply-to');
    }
}


//
if (empty($groups)) {
    $logger->info('Received an empty \'Newsgroups\' header field - parsing header as backup...');
    $groups = x($header, 'Newsgroups');
}


//
$references = empty($references) ? null : preg_split("/[ \t]/", $references);




/**
 *
 */
function outputHead()
{
    //
    extract($GLOBALS);

    echo '<table id="article-head" border="0" cellpadding="2" cellspacing="2" width="100%">' . "\r\n";

    // Subject
    echo ' <tr>' . "\r\n";
    echo '  <td class="label">Subject:</td>' . "\r\n";
    echo '  <td class="value" colspan="3"><b>' . htmlspecialchars($subject) . '</b></td>', "\r\n";
    echo ' </tr>', "\r\n";
    // From
    echo ' <tr>', "\r\n";
    echo '  <td class="label">From:</td>' . "\r\n";
    echo '  <td class="value">' . htmlspecialchars($from) . '</td>', "\r\n";
    // Date
    echo '  <td class="label">Date:</td>' . "\r\n";
    echo '  <td class="value">' . htmlspecialchars($date) . '</td>', "\r\n";
    echo ' </tr>', "\r\n";
    echo ' <tr>', "\r\n";

    // References
    echo '  <td class="label">References:</td>' . "\r\n";
    echo '  <td class="value">';
    switch (true) {
    case is_array($references):
        foreach ($references as $reference) {
    	echo '   <a href="article.php?', query('article='.urlencode($reference)), '">#', ++$i, '</a>', "\r\n";
        }
        break;
    case is_string($references) && !empty($references):
        echo '   <a href="article.php?', query('article='.urlencode($reference)), '">#', ++$i, '</a>', "\r\n";
        break;
    }
    echo '  </td>', "\r\n";


    // Groups
    echo '  <td class="label">Groups:</td>' . "\r\n";
    echo '  <td class="value">', "\r\n";
    foreach (explode(',', $groups) as $group) {
        echo '<a href="./group.php?'. query('group='.urlencode($group)), '">', $group, '</a> ';
    }
    echo '  </td>', "\r\n";
    echo ' </tr>', "\r\n";

    //
    echo '</table>', "\r\n";
}

/**
 *
 */
function outputHeader()
{
    //
    extract($GLOBALS);

    echo '<blockquote id="article-header">', "\r\n";
    echo ' <pre>', "\r\n";
    echo preg_replace("/\r\n(\t| )+/", ' ', implode("\r\n", $header));
    echo ' </pre>', "\r\n";
    echo '</blockquote>', "\r\n";
}

/**
 *
 */
function outputBody()
{
    //
    extract($GLOBALS);

    echo '<blockquote id="article-body">', "\r\n";
    echo ' <pre>', "\r\n";
    foreach ($body as $line) {

		$insig = 0;
		
        /* Code from news.php.net begins here */ 
    
        // this is some amazingly simplistic code to color quotes/signatures
        // differently, and turn links into real links. it actually appears
        // to work fairly well, but could easily be made more sophistimicated.
        $line = htmlentities($line, ENT_NOQUOTES, 'utf-8');
        $line = preg_replace("/((mailto|http|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$)/", "<a href=\"\\1\">\\1</a>\\3", $line);
        if (!$insig && $line == "-- \r\n") {
        	echo '<span class="signature">';
        	$insig = 1;
        }
        if ($insig && $line == "\r\n") {
        	echo '</span>';
        	$insig = 0;
        }
        if (!$insig && substr($line, 0, 4) == '&gt;') {
        	echo '<span class="quote">', $line, '</span>';
        } else {
        	echo $line;
        }
        /* Code from news.php.net ends here */ 

        echo "\r\n";
    }
    echo ' </pre>', "\r\n";
    echo '</blockquote>', "\r\n";
}


/**********/
/* Output */
/**********/

/**
 * Output header
 */
include 'header.inc.php';


$logger->dump();
outputHead();
outputBody();

/**
 * Output footer
 */
include 'footer.inc.php';

?>
