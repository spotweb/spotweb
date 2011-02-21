<?php

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-   WIJZIG ONDERSTAANDE  =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
$settings['nntp_nzb']['host'] = 'news.ziggo.nl';	# <== Geef hier je nntp server in
$settings['nntp_nzb']['user'] = 'xx';				# <== Geef hier je username in
$settings['nntp_nzb']['pass'] = 'yy';				# <== Geef hier je password in
$settings['nntp_nzb']['enc'] = false; 				# <== false|'tls'|'ssl', defaults to false.
$settings['nntp_nzb']['port'] = 119; 				# <== set to 563 in case of encryption

# =-=-=-=-=-=-=-=- Als je een aparte 'headers' newsserver nodig hebt, uncomment dan volgende =-=-=-=-=-=-=-=-=-
$settings['nntp_hdr']['host'] = '';					# <== Geef hier je nntp server voor headers in, maar enkel als dit nodig is
$settings['nntp_hdr']['user'] = '';
$settings['nntp_hdr']['pass'] = '';
$settings['nntp_hdr']['enc'] = false;				# <== false|'tls'|'ssl', defaults to false.
$settings['nntp_hdr']['port'] = 119;				# <== set to 563 in case of encryption


@include('../ownsettings.php'); 					# <== deze lijn mag je verwijderen	

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

# override NNTP header settings 
if (empty($settings['nntp_hdr']['host'])) {
	$settings['nntp_hdr'] = $settings['nntp_nzb'];
} # if 

// version
define('VERSION', '0.2b');

// settings 
$settings['hdr_group'] = 'free.pt';
$settings['nzb_group'] = 'alt.binaries.ftd';

// db
$settings['sqlite3_path'] = './nntpdb.sqlite3';
$settings['tpl_path'] = './templates/';


// RSA keys
$settings['rsa_keys'] = array();
$settings['rsa_keys'][2] = array('modulo' => 'ys8WSlqonQMWT8ubG0tAA2Q07P36E+CJmb875wSR1XH7IFhEi0CCwlUzNqBFhC+P',
								 'exponent' => 'AQAB');
$settings['rsa_keys'][3] = array('modulo' => 'uiyChPV23eguLAJNttC/o0nAsxXgdjtvUvidV2JL+hjNzc4Tc/PPo2JdYvsqUsat',
								 'exponent' => 'AQAB');
$settings['rsa_keys'][4] = array('modulo' => '1k6RNDVD6yBYWR6kHmwzmSud7JkNV4SMigBrs+jFgOK5Ldzwl17mKXJhl+su/GR9',
								 'exponent' => 'AQAB');


