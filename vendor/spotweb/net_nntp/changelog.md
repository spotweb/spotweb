Release v1.5.2
----------------
- Fix bug #20941 (PR #2 @ GitHub): "Clear openssl error messages before and after fgets calls" (thanks @zrtq) (@heino)
- Fix bug #21236: "Line starting with dot is not dot stuffed" (thanks @mesa57 / Jan Franken) (@heino)
- Change to absolute file locations in require-once() (@heino)

(Released: 2017-08-30)


Release v1.5.2-RC1
----------------
- Fix bug #20941 (PR #2 @ GitHub): "Clear openssl error messages before and after fgets calls" (thanks @zrtq) (@heino)
- Fix bug #21236: "Line starting with dot is not dot stuffed" (thanks @mesa57 / Jan Franken) (@heino)
- Change to absolute file locations in require-once() (@heino)

(Released: 2017-08-21)


Release v1.5.1
--------------
- Fix a few serious typos (@heino)
- Better handling of timeouts (@heino)
- Add pause between each line to avoid CPU overload (@heino)
- Fix calls to nonexistent error() (@heino)
- Minor fixes in demo (@heino)

(Released: 2017-08-14)


Release v1.5.1-RC1
----------------
- Fix a few serious typos (@heino)
- Better handling of timeouts (@heino)
- Add pause between each line to avoid CPU overload (@heino)
- Fix calls to nonexistent error() (@heino)
- Minor fixes in demo (@heino)

(Released: 2017-07-25)


Release v1.5.0
--------------
- Fix noisy version_compare warning (bug #19753) (@janpascal)

(Released: 2011-10-05)


Release v1.5.0-RC2
------------------------
- Fix limited buffer for large replies from server (bug# 18875) (@heino)
- Fix NNTP injection vulnerability (reported by Brendan Coles, itsecuritysolutions.org) (@heino)
- Fix XXS vulnerability in demo (reported by Brendan Coles, itsecuritysolutions.org) (@heino)
- Added support for STARTTLS encryption (@heino)
- Use PHP's streams instead of Net_NNTP (to allow easy TLS encryption and future on demand data compressed) (@heino)
- Improved logging with notices for most commands (@heino)
- Added warning about feof() defect in PHP 5.2.11 (bug #49706) (@heino)
- Fix usage of deprecated split() (bug #17417 and #17783) (@heino)
- Fix for large groups on 32 bit systems: Article numbers are no longer cast into integers, but passed on directly from the server as strings (bug #17689). This _could_ possibly have implications and should considered a possible backward compatibility breakage, but is thought to be acceptable, since PHP is expected to cast into integers/floats as needed... (@heino)

(Released: 2011-10-05)


Release: v1.5.0-RC1
-------------------------
- Fix NNTP injection vulnerability (reported by Brendan Coles, itsecuritysolutions.org) (@heino)
- Fix XXS vulnerability in demo (reported by Brendan Coles, itsecuritysolutions.org) (@heino)
- Fix usage of deprecated split() (bug #17417 and #17783) (@heino)
- Fix for large groups on 32 bit systems: Article numbers are no longer cast into integers, but passed on directly from the server as strings (bug #17689). This _could_ possibly have implications and should considered a possible backward compatibility breakage, but is thought to be acceptable, since PHP is expected to cast into integers/floats as needed... (@heino)

(Released: 2011-08-15)


Release v1.5.0-alpha1
------------------------
- Added support for STARTTLS encryption (@heino)
- Use PHP's streams instead of Net_NNTP (to allow easy TLS encryption and future on demand data compressed) (@heino)
- Improved logging with notices for most commands (@heino)
- Added warning about feof() defect in PHP 5.2.11 (bug #49706) (@heino)

(Released: 2009-10-04)


Release v1.4.0
--------------
**Finally released as stable - after two years as release candidate...**

Changes in Net_NNTP_Client:
- fix bug #6833: mail() does not work (@heino)
- fix bug #6845: notices in getOverview() (@heino)
- Loading deprecated classes Net_NNTP_Message and Net_NNTP_Header now triggers warnings ! (@heino)

(Released: 2008-06-17)


Release v1.4.0-RC1
------------------------
Changes in Net_NNTP_Client:
- fix bug #6833: mail() does not work (@heino)
- fix bug #6845: notices in getOverview() (@heino)
- Loading deprecated classes Net_NNTP_Message and Net_NNTP_Header now triggers warnings ! (@heino)

(Released: 2006-06-17)


Release v1.3.3-beta
---------------------
- Changes in Net_NNTP_Protocol_Client:
    - fix bug #6618: notices in cmdListNewsgroups() (@heino)

(Released: 2006-02-06)


Release v1.3.2-beta
---------------------
Changes in Net_NNTP_Client:
---------------
- getNewArticles() and getNewGroups() now validates that any strtotime() convertions was successful. (@heino)

Changes in Net_NNTP_Protocol_Client:
---------------
- fix bug #6334: cmdNewNews() and cmdNewGroups() no longer localize the timestamp depending on timezones. (@heino)

(Released: 2005-12-28)


Release v1.3.1-alpha
----------------------
Changes in Net_NNTP_Client:
---------------------
- added: mail() as a replacement for experimental post() using identical parameters as PHP's mail() function, (temporarily) preserving backward compatibility with experimental method in v1.0. (@heino)
- modified and rewritten: post(), reduced to having only one parameter, (temporarily) preserving backward compatibility with experimental method in v1.0. (@heino)

Changes in Net_NNTP_Protocol_Client:
---------------------
- added: _sendArticle() (@heino)
- modified and rewritten: cmdPost() split into cmdPost() and cmdPost2() + now sends data via _sendArticle() (@heino)
- modified and rewritten: cmdIhave() split into cmdIhave() and cmdIhave2() + now sends data via _sendArticle() (@heino)

(Released: 2005-12-23)


Release v1.3.0-alpha
----------------------
**WARNING!**
------------------
- Serious backward compatibility break with v1.2.x (alpha) releases!!! The experimental classes Net_NNTP_Header and Net_NNTP_Message has been droped, since such features does not belong in this package! For now loading either class results in a notice/warning, but later on both classes will be removed! Previously unimplementet NNTP commands now allow access to article headers...
- Some backward compatibility break with v1.1.x (beta) releases!!!

**Changes in Net_NNTP_Client:**
------------------
- fixed: connect(), now returns false when posting is prohibited (like cmdModeReader()).
- fixed: getGroupArticles(), now updates internal group summary cache.
- added: getHeaderField().
- added: getGroupArticles().
- modified: connect(), added $encryption parameter to support ssl/tls connections, and $timeout parameter. 
- modified: selectGroup(), added (experimental) parameter to allow fetching of article numbers at once.
- modified: getOverview(), added two (experimental) parameters (rewritten to preserve backward compatible with v1.0).
- modified and rewritten: getGroups(), addition of optional $wildmat parameter.
- modified and rewritten: getDescriptions(), addition of optional $wildmat parameter.
- modified and rewritten: getOverview(), $first and $last parameters changed into $range.
- renamed: quit() into disconnect(), (temporarily) preserving backward compatible with v1.0.
- renamed: getArticleRaw() into getArticle(), (temporarily) preserving backward compatible with v1.1.
- renamed: getHeaderRaw() into getHeader(), (temporarily) preserving backward compatible with v1.1.
- renamed: getBodyRaw() into getBody(), (temporarily) preserving backward compatible with v1.1.
- renamed and rewritten: getReferencesOverview() into getReferences().
- removed: connectAuthenticated() (as in MAINT_1_0 and MAINT_1_2).
- removed: isConnected() removed due to use of private members in Net_Socket! 
- misc: all internal PEAR::throwError() changed to $this->throwError().
- misc: major phpdoc rewrite.
- misc: removal of code related to not yet implemented alternative authentication methods.

Changes in Net_NNTP_Protocol_Client:
------------------
- added: cmdXHdr().
- added: cmdCapabilities().
- added: cmdHelp().
- added: cmdListActive().
- added: cmdXPat().
- modified connect(), added $encryption parameter to support ssl/tls connections. 
- modified: cmdNext(), now returns array by default.
- modified and rewritten: cmdXOver(), first parameter now optional.
- renamed: isConnected() into _isConnected(), due to use of private members in Net_Socket. 
- misc: support for logging via the Log package (debugging rewritten to use logger).
- misc: use of status response code constants in Net_NNTP_Protocol_Responsecode.
- misc: extends PEAR (as in v1.0.x).
- misc: all internal PEAR::throwError() changed to $this->throwError().

Misc.
-------------
- Examples replaced by fully functional newsgroup reader demo.
- License upgraded to newer edition of 'W3C SOFTWARE NOTICE AND LICENSE'

(Released: 2005-12-14)


Release v1.2.6-alpha
----------------------
- renamed: getNewNews() into getNewArticles() (@heino)
- renamed: getReferencesOverview() into getReferences() (@heino)

(Released: 2005-11-30)

Release v1.2.5-alpha
----------------------
- Various additions/modifications: (@heino)
    - getHeaderField() added
    - getReferencesOverview() rewritten
    - cmdOver() added
    - cmdHelp() added
    - cmdXHdr() added
    - cmdCapabilities() added
    - cmdXOver() rewritten
    - cmdListOverviewFmt rewritten
    - cmdNext() modified
    - cmdPrevious() modified
    - cmdStat() modified
    - connectAuthenticated() removed
    - examples rewritten

(Released: 2005-11-27)


Release v1.2.4-alpha
-----------------------
- Fix: connect() now returns false when posting is prohibited, like cmdModeReader() does
- New: Response code constants (@heino)
- Misc. internal rewrites in protocol implementation:
    - Expected response codes in cmdArticle(), cmdHead() and cmdBody() reduced to correspond actual implementations. (@heino)
    - First parameter in cmdXOver(), cmdXROver() and cmdListgroup() is now optional (@heino)
    - New third optional parameter in getNewNews() and cmdNewnews(). (@heino)
    - cmdNext(), cmdLast() and cmdStat() now returns array by default. (@heino)

(Released: 2005-10-20)


Release v1.2.3-alpha
----------------------
- New method in Net_NNTP_Client: getBody(). (@heino)
- Added parameters to Net_NNTP_Client::getArticle/getHeader() to allow use of external return classes. (@heino)
- Added status response code constants in Net_NNTP_Protocol_Clients. (@heino)

(Released: 2005-05-13)


Release v1.2.2-alpha
----------------------
- Bug #3967 fixed (typo in Net_NNTP_Header::decodeString()) (@heino)

(Released: 2005-03-13)


Release v1.2.1-alpha
----------------------
- New methods in Net_NNTP_Client: selectArticle(), selectNextArticle() and selectPreviousArticle(). (@heino)
- New methods in Net_NNTP_Protocol_Client: cmdStat(), cmdNext() and cmdLast(). (@heino)

(Release: 2005-03-28)


Release v1.2.0-alpha
----------------------
- Corresponds to v0.11.3 (exact feature match) (@heino)

(Released: 2005-01-14)


Release v1.1.1-beta
---------------------
- Net_NNTP_Client::connectAuthenticated() removed - it should only exist in the MAINT_1_2 branch for now (was not removed by mistake in the v1.1.0 release) (@heino)

(Released: 2005-03-28)


Release v1.1.0-beta
---------------------
- This release is NOT fully backward compatible with v0.11.3, since experimental features (Net_NNTP_Message and Net_NNTP_Header and related methods Net_NNTP_Client::getArticle() and Net_NNTP_Client::getHeader()) have been removed. Users of those features should consider v1.2.x in stead. (@heino)

(Released: 2005-01-14)


Release v1.0.1
--------------
- Fix lack of $fp property in historical Net_NNTP class (only relevant for backward compatibility with v0.2.5)  (@heino)
- Fix typo in examples. (@heino)

(Released: 2005-03-28)


Release v1.0.0
--------------
- This release is NOT backward compatible with v0.11.3, since all non-stable features (classes, methods etc.) have been removed!!! (@heino)
- Users of releases between 0.3.x and 0.11.x should consider v1.1.x or v1.2.x in stead. (This release is meant only as a final replacement for v0.2.5 - and of cause as a way to finally get rid of the former protocol implementation). (@heino)
- Backward compatible with v0.2.5 (this is ONLY guarantied in v1.0.x releases, and may be removed in the future). (@heino)

(Released: 2005-01-18)


Release v1.0.0-RC1
-------------------------
- This release is NOT backward compatible with v0.11.3, since all non-stable features (classes, methods etc.) have been removed!!! (@heino)
- Users of releases between v0.3.x and v0.11.x should consider v1.1.x or v1.2.x in stead. (This release is meant only as a final replacement for v0.2.5 - and of cause as a way to finally get rid of the former protocol implementation). (@heino)
- Backward compatible with v0.2.5 (this is ONLY guarantied in v1.0.x releases, and may be removed in the future). (@heino)

(Released: 2005-01-14)


Release v0.11.3-beta
----------------------
- Going beta (features not documented in the manual should still be considered experimental) (@heino)
- Added the 'distributions' parameter to getNewGroups() and cmdNewGroups() (@heino)

(Released: 2004-09-06)


Release: v0.11.2-alpha
------------------------
- Update PhpDoc blocks. (@heino)
- Changed xmdXOver(),cmdXROver(),getOverview() and getReferencesOverview() into using only parameter: $range. (@heino)
- Fix whitespace in Net_NNTP_Client, Net_NNTP_Protocol_Client and Net_NNTP. (@heino)

(Released: 2004-08-22)


Release v0.11.1-dev
-----------------------
- Fix bug in (deprecated) Net_NNTP::command(). (@heino)
- Rename a few constants due to renamed classes (BC preserved). (@heino)
- Update class phpdoc blocks. (@heino)

(Released: 2004-07-30)


Release v0.11.0-dev
-----------------------
- New directory structure (classes/files renamed/moved) to allow future server class (dummy files preserves backward compatibility) (@heino)

(Released: 2004-07-19)


Release v0.10.2-alpha
-----------------------
- Fixes bug #1803 (trailing space character sent in cmdListNewsgrups()) (@heino)
- Fixes bug #825 (no more lazy assignement of new values to $this) (@heino)
- Examples moved into docs to comply with the standard PEAR directory structure. (@heino)

(Released: 2003-07-06)


Release: v0.10.1-alpha
------------------------
- Fixes bug #7 (lines longer than 1000 chars no longer stop download) (@heino)

(Released: 2003-10-24)


Release: v0.10.0-alpha
------------------------
- Merges v0.3.3 and v0.9.4 into one package - The 'Net_NNTP' class from v0.9.x is now called 'Net_NNTP_Realtime'. (@heino)

(Release: 2003-10-12)


Release: v0.9.3-alpha
-----------------------
- Constant names pearified. (@heino)
- Deprecated/historical methods that didn't follow PEAR's coding standard have been removed. (@heino)
- Incorrect handling of the XROVER extension corrected in cmdXROver() and removed in getOverview(). (@heino)

(Release: 2003-09-13)


Release v0.9.2-alpha
----------------------
- Fixed syntax typo... (@heino)

(Release: 2003-08-16)


Release v0.9.1-alpha
----------------
- Bug fixing and improvements in Net_NNTP_Header and Net_NNTP_Message (@heino)

(Release: 2003-08-15)


Release v0.9.0-alpha
----------------
- Major rewrite, yet still generaly backward compatible - now uses Net_Socket, lets the user choose if the article data are to be returned as strings or arrays or objects, authentication has been separated from the execution of commands, returns pear_error objects of failure, and handles the server's responses individually. (@heino)

(Released: 2003-08-04)


Release v0.3.3-beta
---------------------
- Fixes bug #85 + a lot of phpdoc updated. (@heino)

(Released: 2003-10-12)


Release v0.3.2-beta
---------------------
- Incorrect handling of the XROVER extension corrected in cmdXROver() and removed in getOverview(). (@heino)

(Released: 2003-09-20)


Release v0.3.1-beta
---------------------
- Uses the new protocol implementation from v0.9.*, but preserves backward compatibility with v0.2, since the experimental header and message classes are not used. (@heino)
- Deprecated/historical methods that didn't follow PEAR's coding standard have been removed. (@heino)

(Released: 2003-08-31)


Release v0.2.5
--------------
- post() rewritten to allow posting using authentication (bug #817). (@heino)
- Examples moved into docs to comply with the standard PEAR directory structure. (@heino)

(Released: 2004-07-19)


Release v0.2.4
--------------
- Not documented...

(Released: 2004-03-10; according to changelog, but not released on pear.php.net)


Release v0.2.3
--------------
- Incorrect handling of the XROVER extension in getOverview() removed. (@heino)

(Released: 2003-09-20)


Release v0.2.2
--------------
- Constant names pearified (@heino)

(Released: 2003-08-31)


Release v0.2.1
--------------
- Fix binary safety (@heino)

(Released: 2003-08-09)


Release v0.2.0
--------------
- Pearified API

(Released: 2002-02-20)


Release v0.1.0
--------------
- This is the initial independent release of the NNTP package.

(Released: 2002-01-27)
