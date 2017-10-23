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

// $group must be set
if (empty($group)) {
    error('No newsgroup choosen!');
}


/* Prepare breadcrumbs */

$breadcrumbs = array();
$breadcrumbs['Frontpage'] = './index.php?' . query();
$breadcrumbs['groups @ ' . ($host == null ? 'localhost' : $host)] = './groups.php?' . query();
$breadcrumbs['group: ' . $group] = null;


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


// Select group
$summary = $nntp->selectGroup($group);
if (PEAR::isError($summary)) {
    error($summary->getMessage());
}

//
if (!$useRange) {

    // Select article
    switch ($article) {
    case '':
    case 'last':
         $article = $nntp->last();
         break;
    case 'first':
         $article = $nntp->first();
         break;
    }
    $dummy = $nntp->selectArticle($article);
    if (PEAR::isError($dummy)) {
        error($dummy->getMessage());
    }


    // Select next/previous article
    switch ($action) {
    case 'next':
        $dummy = $nntp->selectNextArticle();
         break;
    case 'previous':
        $dummy = $nntp->selectPreviousArticle();
         break;
    }
    if (PEAR::isError($dummy)) {
        error($dummy->getMessage());
    }


    //
    $i = 0;
    $articles = array();

    // Loop until break inside
    while (true) {

        // Break if no more articles
        if ($article === false) {
    	    break;
        }

        // Fetch overview for currently selected article
        $overview = $nntp->getOverview();
        if (PEAR::isError($overview)) {
            error($overview->getMessage());
        }

        //
        $articles[] = $overview;

        // Break if max article reached
        if (++$i >= $max) {
            break;
        }

        // Select next/previous article
        if ($action == 'next') {
            $article = $nntp->selectNextArticle();
        } else {
            $article = $nntp->selectPreviousArticle();
        }
        if (PEAR::isError($article)) {
            error($article->getMessage());
        }

    }

} else {

    // Fetch overview for currently selected article
    switch ($action) {
    case '':
    case 'last':
         $range = ($nntp->last() - $max + 1) .'-'. $nntp->last();
         break;
    case 'previous':
         $range = ($article - $max) .'-'. ($article - 1);
         break;
    case 'next':
         $range = ($article + 1) .'-'. ($article + $max);
         break;
    case 'first':
         $range = $nntp->first() .'-'. ($nntp->first() + $max - 1);
         break;
    default:
        error('bad input!');
    }
    $articles = $nntp->getOverview($range);
    if (PEAR::isError($articles)) {
        error($articles->getMessage());
    }
}


// Disconnect
$nntp->disconnect();


//
if (!$useRange) {
    if ($action != 'next') {
        $articles = array_reverse($articles);
    }
}


/**
 *
 */
function nav()
{ 
    //
    extract($GLOBALS);

    // get the first and the last article number from returned articles
    $first = reset($articles);
    $last = end($articles);

    // Start table
    echo '<table width="100%" border="0" cellpadding="2" cellspacing="3">';
    echo '<tr bgcolor="#cccccc">';

    // Previous # link
    echo '<td align="center" valign="middle">';
    if ($first['Number'] > $nntp->first()) {
        echo '<a href="group.php?', query('group='.urlencode($group) . '&article='.$first['Number'] . '&action=previous'), '">&laquo; Previous</a> ';
    } else {
        echo '&nbsp;';
    }
    echo '</td>';

    // Group info
    echo '<td align="center" valign="middle">';
    echo $group, ' (', $first['Number'], '-', $last['Number'], ' of ', $summary['last'], ' ; ', $summary['count'], ' on server)';
    echo '</td>';

    // Next # link
    echo '<td align="center" valign="middle">';
    if ($last['Number'] < $nntp->last()) {
        echo '<a href="group.php?', query('group='.urlencode($group) . '&article='.$last['Number'] . '&action=next'), '">Next &raquo;</a> ';
    } else {
        echo '&nbsp;';
    }
    echo '</td>';

    // End table
    echo '</tr>';
    echo '</table>';
}

/**
 *
 */
function articles()
{
    // 
    extract($GLOBALS);

    // 
    switch ($format) {

    case '';
    case 'html';
        echo '<table width="100%" border="0" cellpadding="3" cellspacing="4">', "\r\n";
        echo '<tr bgcolor="#cccccc"><th>#</th><th>Subject</th><th>Author</th><th>Date</th></tr>', "\r\n";
    	break;

    case 'rss';
        header('Content-type: text/xml');
        echo '<?xml version="1.0" encoding="utf-8" ?>', "\r\n";
        echo '<rss version="0.93">', "\r\n";
        echo ' <channel>', "\r\n";
        echo '  <title>', $host, ': ', $group, '</title>', "\r\n";
        echo '  <link>http://', $_SERVER['SERVER_NAME'], dirname($_SERVER['SCRIPT_NAME']), '/' , htmlspecialchars(query('group='.$group)), '</link>', "\r\n";
        echo '  <description></description>', "\r\n";
    	break;

    case 'rdf';
    	header('Content-type: text/xml');
    	echo '<?xml version="1.0" encoding="utf-8"?>', "\r\n";
    	echo '<rdf:RDF', "\r\n";
    	echo '        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"', "\r\n";
    	echo '        xmlns="http://my.netscape.com/rdf/simple/0.9/">', "\r\n";
    	echo ' <channel>', "\r\n";
    	echo '  <title>', $_SERVER['HTTP_HOST'], ' : ', $group, '</title>', "\r\n";
        echo '  <link>http://', $_SERVER['SERVER_NAME'], dirname($_SERVER['SCRIPT_NAME']), '/' , htmlspecialchars(query('group='.$group)), '</link>', "\r\n";
    	echo '  <description>', $group, 'Newsgroup at ', $host, '</description>', "\r\n";
    	echo '  <language>en-US</language>', "\r\n";
    	echo ' </channel>', "\r\n";
    	break;
    }

    // Loop through articles
    $i == 0;
    foreach ($articles as $overview) {

    	// 
    	$number = $overview['Number'];
        $subject = $overview['Subject'];
        $from = $overview['From'];
        $date = $overview['Date'];

    	// 
        $link = 'article.php?' . query('group='.urlencode($group) . '&article='.urlencode($number));

        // Decode subject and from header fields, if mime-extension is loaded...
        if (function_exists('imap_mime_header_decode')) {

    	    // Decode $subject
    	    $decoded = imap_mime_header_decode($subject);
    	    $subject = '';
    	    foreach ($decoded as $element) {
    	        $subject .= $element->text;
    	    }

    	    // Decode $from
    	    $decoded = imap_mime_header_decode($from);
    	    $from = '';
    	    foreach ($decoded as $element) {
    	    	$from .= $element->text;
    	    }
	}

    	// Format... (removes comments etc.)
    	//$date = strftime('%c', strtotime(preg_replace('/\([^\)]*\)/', '', $date)));

    	// Output
        switch ($format) {

	case '';
	case 'html';
    	    echo ' <tr class="article ', ($i++ % 2 ? 'even' : 'odd'), '">', "\r\n";
            echo '  <td><a href="', $link, '">', $number, '</a></td>', "\r\n";
            echo '  <td><a href="', $link, '">', $subject, '</a></td>', "\r\n";
            echo '  <td>', $from, '</td>', "\r\n";
            echo '  <td>', str_replace(' ', '&nbsp;', $date), '</td>', "\r\n";
            echo ' </tr>', "\r\n";
            break;

	case 'rss';
            echo '  <item>', "\r\n";
            echo '   <link>http://', $_SERVER['SERVER_NAME'], dirname($_SERVER['SCRIPT_NAME']), '/' . htmlspecialchars($link), '</link>', "\r\n";
            echo '   <title>', htmlspecialchars($subject), '</title>', "\r\n";
            echo '   <description>', '</description>', "\r\n";
            echo '   <pubDate>', $date, '</pubDate>', "\r\n";
            echo '  </item>', "\r\n";
            break;

        case 'rdf':
    	    echo ' <item>', "\r\n";
    	    echo '  <title>', htmlspecialchars($subject), '</title>', "\r\n";
            echo '  <link>http://', $_SERVER['SERVER_NAME'], dirname($_SERVER['SCRIPT_NAME']), '/' . htmlspecialchars($link), '</link>', "\r\n";
    	    echo '  <description>', '</description>', "\r\n";
    	    echo '  <pubDate>', $date822, '</pubDate>', "\r\n";
    	    echo ' </item>', "\r\n";
    	    break;
        }
    }

    // 
    switch ($format) {
    case '';
    case 'html';
        echo '</table>', "\r\n";
    	break;
    case 'rss';
        echo ' </channel>', "\r\n";
        echo '</rss>', "\r\n";
    	break;
    case 'rdf';
        echo '</rdf:RDF>', "\r\n";
    	break;
    }
}


/**********/
/* Output */
/**********/

if ($format == 'html') {
    /**
     * Output header
     */
    include 'header.inc.php';

    //
    $logger->dump();

    //
    nav();
}

//
if (count($articles) < 1) {
    echo 'No articles...';
} else {
    articles();
}

//
if ($format == 'html') {
    //
    nav();


    /**
     * Output footer
     */
    include 'footer.inc.php';
}

?>
