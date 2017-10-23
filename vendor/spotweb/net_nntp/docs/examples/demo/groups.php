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


/* Prepare breadcrumbs */

$breadcrumbs = array();
$breadcrumbs['Frontpage'] = './index.php?' . query();
$breadcrumbs['Groups @ ' . ($host == null ? 'localhost' : $host)] = null;

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

// Fetch list of groups
$groups = $nntp->getGroups($wildmat);
if (PEAR::isError($groups)) {
    error('Fetching list of groups failed: ' . $groups->getMessage());
}

// Fetch known (to the server) group descriptions
$descriptions = $nntp->getDescriptions($wildmat);
if (PEAR::isError($descriptions)) {
    $logger->notice('Fetching group descriptions failes: ' . $descriptions->getMessage());

    //
    $descriptions = array();
}

// Close connection
$nntp->disconnect();


/**
 *
 */
function groups()
{
    //
    extract($GLOBALS);
	
    //
    echo '<table border="0" cellpadding="3" cellspacing="4">', "\r\n";
    echo '<tr><th>Group</th><th>Articles</th><th>Description</th><th>Posting</th></tr>', "\r\n";

    // Loop through groups
    $i = 0;
    foreach ($groups as $group) {

        $link = 'group.php?' . query('group='.urlencode($group['group']));

        $messageCount = $group['last'] - $group['first'] + 1;

        $description = empty($descriptions[$group['group']]) ? '' : $descriptions[$group['group']];

	switch ($group['posting']) {
        case 'y': $posting = 'yes'; break;
        case 'n': $posting = 'no'; break;
        case 'm': $posting = 'moderated'; break;
        default: $posting = 'unknown';
	}

	echo ' <tr class="group ', ($i++ % 2 ? 'even' : 'odd'), ' posting-', $posting, '">', "\r\n";
	echo '  <td align="left" class="name"><a href="', $link, '">', $group['group'], '</a></td>', "\r\n";
	echo '  <td align="center" class="count">', $messageCount, '</td>', "\r\n";
	echo '  <td align="left" class="description">', $description, '</td>', "\r\n";
	echo '  <td align="center" class="posting">', $posting, '</td>', "\r\n";
	echo ' </tr>', "\r\n";
    }

    //
    echo '</table>', "\r\n";
}


/**********/
/* Output */
/**********/

/**
 * Output header
 */
include 'header.inc.php';


//
$logger->dump();

// 
groups();

/**
 * Output footer
 */
include 'footer.inc.php';

?>
