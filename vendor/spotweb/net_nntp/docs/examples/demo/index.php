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

// Forward to groups.php if $frontpage not allowed
if (!$frontpage) {
    include 'groups.php';
    die();
}

/**
 *
 */
require_once 'common.inc.php';

/**
 * Output header
 */
include 'header.inc.php';

?>

<form action="groups.php" method="GET">
<table border="0" cellspacing="0" cellpadding="3" id="xxx">
<tr><td valign="top"><b>Host:</b></td><td><input type="text" name="host" value="<?php echo $host==null ? 'news.php.net' : $host; ?>"></td><td>(Defaults to 'localhost' when empty)</td></tr>
<tr><td valign="top"><b>Port:</b></td><td><input type="text" name="port" value="<?php echo $port; ?>"<?php echo ($allowPortOverwrite ? 'x' : ' DISABLED'); ?>></td><td>(Defaults to '119' on non-encrypted connections, and '563' on encrypted connections when empty)</td></tr>
<tr><td valign="top"><b>Windmat:</b></td><td><input type="text" name="wildmat" value="<?php echo $wildmat; ?>"></td><td>(Group wildmat)</td></tr>
<tr><td valign="top"><b>Username:</b></td><td><input type="text" name="user" value="<?php echo $user; ?>"></td><td>(Only used if both username and password is entered)</td></tr>
<tr><td valign="top"><b>Password:</b></td><td><input type="password" name="pass" value="<?php echo $pass; ?>"></td><td>(Only used if both username and password is entered)</td></tr>
<tr><td valign="top" rowspan="4"><b>Encryption:</b></td><td colspan="2"><input type="radio" name="encryption" value="" checked="checked">none</td></tr>
<tr><td style="border-top: 0px"><input type="radio" name="encryption" value="starttls">startTLS</td><td valign="top" style="border-top: 0px">(Starts encryption on an initially unencrypted connection)</td></tr>
<tr><td style="border-top: 0px"><input type="radio" name="encryption" value="tls">TLS</td><td valign="top" style="border-top: 0px">(Requires a NNTPS server)</td></tr>
<tr><td style="border-top: 0px"><input type="radio" name="encryption" value="ssl">SSL</td><td valign="top" style="border-top: 0px">(Requires a NNTPS server)</td></tr>
<tr><td valign="top"><b>Loglevel:</b></td><td><input type="radio" name="loglevel" value="4" checked="checked">warning<br><input type="radio" name="loglevel" value="5" checked="checked">notice<br><input type="radio" name="loglevel" value="6">info<br><input type="radio" name="loglevel" value="7">debug</td><td valign="top">(Application logging level)</td></tr>
<tr><td></td><td colspan="2"><input type="submit" value="View newsgroups"></td></tr>
</table>
</form>

<?php

/**
 * Output footer
 */
include 'footer.inc.php';

?>
