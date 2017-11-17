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
 * @version    SVN: $Id: Responsecode.php 306619 2010-12-24 12:16:07Z heino $
 * @link       http://pear.php.net/package/Net_NNTP
 * @see        
 * @since      File available since release 1.3.0
 */



// {{{ Constants: Connection

/**
 * 'Server ready - posting allowed' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED', 200);

/**
 * 'Server ready - no posting allowed' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED', 201);


/**
 * 'Closing connection - goodbye!' (RFC977)
 *
 * @access     public
 * @since      ?
 */
//define('NET_NNTP_PROTOCOL_RESPONSECODE_DISCONNECTING_REQUESTED', 205);   ///// goodbye

/**
 * 'Service discontinued' (RFC977)
 *
 * @access     public
 * @since      ?
 */
//define('NET_NNTP_PROTOCOL_RESPONSECODE_DISCONNECTING_FORCED', 400);   ///// unavailable / discontinued


/**
 * 'Slave status noted' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_SLAVE_RECOGNIZED', 202);




// }}}
// {{{ Constants: Common errors



/**
 * 'Command not recognized' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_UNKNOWN_COMMAND', 500);

/**
 * 'Command syntax error' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_SYNTAX_ERROR', 501);

/**
 * 'Access restriction or permission denied' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED', 502);

/**
 * 'Program fault - command not performed' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NOT_SUPPORTED', 503);



// }}}
// {{{ Constants: Group selection



/**
 * 'Group selected' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED', 211);

/**
 * 'No such news group' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_GROUP', 411);



// }}}
// {{{ Constants: Article retrieval



/**
 * 'Article retrieved - head and body follow' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS', 220);

/**
 * 'Article retrieved - head follows' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS', 221);

/**
 * 'Article retrieved - body follows' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS', 222);

/**
 * 'Article retrieved - request text separately' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED', 223);





/**
 * 'No newsgroup has been selected' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED', 412);


/**
 * 'No current article has been selected' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED', 420);

/**
 * 'No next article in this group' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_NEXT_ARTICLE', 421);

/**
 * 'No previous article in this group' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_PREVIOUS_ARTICLE', 422);


/**
 * 'No such article number in this group' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER', 423);

/**
 * 'No such article found' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID', 430);





// }}}
// {{{ Constants: Transferring



/**
 * 'Send article to be transferred' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND', 335);

/**
 * 'Article transferred ok' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS', 235);

/**
 * 'Article not wanted - do not send it' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_UNWANTED', 435);

/**
 * 'Transfer failed - try again later' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE', 436);

/**
 * 'Article rejected - do not try again' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_REJECTED', 437);



// }}}
// {{{ Constants: Posting



/**
 * 'Send article to be posted' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND', 340);

/**
 * 'Article posted ok' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS', 240);

/**
 * 'Posting not allowed' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_PROHIBITED', 440);

/**
 * 'Posting failed' (RFC977)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_FAILURE', 441);




// }}}
// {{{ Constants: Authorization



/**
 * 'Authorization required for this command' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_REQUIRED', 450);

/**
 * 'Continue with authorization sequence' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_CONTINUE', 350);

/**
 * 'Authorization accepted' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_ACCEPTED', 250);

/**
 * 'Authorization rejected' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_REJECTED', 452);




// }}}
// {{{ Constants: Authentication



/**
 * 'Authentication required' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REQUIRED', 480);

/**
 * 'More authentication information required' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_CONTINUE', 381);

/**
 * 'Authentication accepted' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_ACCEPTED', 281);

/**
 * 'Authentication rejected' (RFC2980)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REJECTED', 482);
    

// }}}
// {{{ Constants: Misc



/**
 * 'Help text follows' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS', 100);

/**
 * 'Capabilities list follows' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_CAPABILITIES_FOLLOW', 101);

/**
 * 'Server date and time' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE', 111);

/**
 * 'Information follows' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW', 215);

/**
 * 'Overview information follows' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS', 224);

/**
 * 'Headers follow' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_HEADERS_FOLLOW', 225);

/**
 * 'List of new articles follows' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NEW_ARTICLES_FOLLOW', 230);

/**
 * 'List of new newsgroups follows' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_NEW_GROUPS_FOLLOW', 231);

/**
 * 'The server is in the wrong mode; the indicated capability should be used to change the mode' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_WRONG_MODE', 401);

/**
 * 'Internal fault or problem preventing action being taken' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_INTERNAL_FAULT', 403);

/**
 * 'Command unavailable until suitable privacy has been arranged' (Draft)
 *
 * (the client must negotiate appropriate privacy protection on the connection.
 * This will involve the use of a privacy extension such as [NNTP-TLS].)
 *
 * @access     public
 * @since      ?
 */
//define('NET_NNTP_PROTOCOL_RESPONSECODE_ENCRYPTION_REQUIRED', 483);

/**
 * 'Error in base64-encoding [RFC3548] of an argument' (Draft)
 *
 * @access     public
 */
define('NET_NNTP_PROTOCOL_RESPONSECODE_BASE64_ENCODING_ERROR', 504);

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


