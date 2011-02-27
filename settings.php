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

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= Filters =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
$settings['filters'] = array(
	Array("Films (geen erotiek)", "images/video2.png", "cat0_d,!cat0_d11,!cat0_d23,!cat0_d24,!cat0_d25,!cat0_d26,!cat0_a5", "", array()),
	Array("Series", "images/series2.png", "cat0_d11", "", array()),
	Array("Boeken", "images/books2.png", "cat0_a5", "", array()),
	Array("Muziek", "images/audio2.png", "cat1", "", 
		Array(
			Array("Compressed", "images/audio2.png", "cat1_a0,cat1_a3,cat1_a5,cat1_a6", ""),
			Array("Lossless", "images/audio2.png", "cat1_a2,cat1_a4,cat1_a7,cat1_a8", "")
		)
	),
	Array("Spellen", "images/games2.png", "cat2", "", array()),
	Array("Applicaties", "images/applications2.png", "cat3", "", array()),
	Array("Erotiek", "images/x2.png", "cat0_d23,cat0_d24,cat0_d25,cat0_d26", "", array()),
	Array("Reset filters", "images/custom2.png", "", "", array())
	
	# Uncomment onderstaande als voorbeeld van een custom filter
	# ,Array("Lossless MJ", "images/audio2.png", "cat1_a2,cat1_a4,cat1_a7,cat1_a8&search[type]=Titel&search[text]=Michael+Jackson", "", array())
);	


// version
define('VERSION', '0.3a');

// settings 
$settings['hdr_group'] = 'free.pt';
$settings['nzb_group'] = 'alt.binaries.ftd';
$settings['comment_group'] = 'free.usenet';

// db
$settings['db']['engine'] = 'sqlite3'; 			# <== keuze uit sqlite3 en mysql
$settings['db']['path'] = './nntpdb.sqlite3';	# <== als je geen SQLite3 gebruikt, kan dit weg	

# Als je MySQL wilt gebruiken, vul dan onderstaande in
#$settings['db']['engine'] = 'mysql';
#$settings['db']['host'] = 'localhost';
#$settings['db']['dbname'] = 'spotweb';
#$settings['db']['user'] = 'spotweb';
#$settings['db']['pass'] = 'spotweb';

# welke database engine willen we gebruiken?

# waar moeten ew de templates vinden?
$settings['tpl_path'] = './templates/';

# tonen we een update knop in de web ui?
$settings['show_updatebutton'] = false;

# toon een download-nzb knop op het overzicht?
$settings['show_nzbbutton'] = true;

# integratie met sabnzbd+? uncomment als dit gewenst is
#$settings['sabnzbd']['host'] = '192.168.10.122:8081';					# <== Pas deze aan naar de sabnzbd host plus port
#$settings['sabnzbd']['apikey'] = '';									# <== Pas deze aan naar jouw sabnzbd api key
#$settings['sabnzbd']['spotweburl'] = 'http://server/spotweb/';			# <== URL naar spotweb, gezien vanuit de Sabnzbd machine
$settings['sabnzbd']['url'] = 'http://$SABNZBDHOST/sabnzbd/api?mode=addurl&amp;name=$NZBURL&nzbname=$SPOTTITLE&cat=$SANZBDCAT&apikey=$APIKEY&output=json'; # <== Hoef je niet aan te passen

# vertaal de categorieen uit spots (zie SpotCategories.php) naar sabnzbd categorieen
$settings['sabnzbd']['categories'] = Array(
		0	=> Array('default' 	=> "movies",				# Default categorie als niets anders matched
					 'a5'		=> "books",
					 'd2'		=> "anime",
					 'd11'		=> "tv",
					 'd29'		=> "anime"),
		1	=> Array('default'	=> 'music'),
		2	=> Array('default'	=> 'games'),
		3	=> Array('default'	=> 'apps',
					 'a3'		=> 'consoles',
					 'a3'		=> 'consoles',
					 'a4'		=> 'consoles',
					 'a5'		=> 'consoles',
					 'a6'		=> 'consoles',
					 'a7'		=> 'consoles',
					 'a8'		=> 'consoles',
					 'a9'		=> 'consoles',
					 'a10'		=> 'consoles',
					 'a11'		=> 'consoles',
					 'a12'		=> 'consoles',
					 'a13'		=> 'pda',
					 'a14'		=> 'pda',
					 'a15'		=> 'pda')
	);
					 
					 
					 

# zoekmachine url (gebruikt bij spots voor 24 november als download knop, en onderaan de spot info)
$settings['search_url'] = 'http://www.binsearch.info/?adv_age=&q=$SPOTFNAME';
# $settings['search_url'] = 'http://nzbindex.nl/search/?q=$SPOTFNAME';

# de filter die standaard gebruikt wordt op de index pagina (als er geen filters oid opgegeven zijn), 
# zorg dat deze wel gedefinieerd is.
$settings['index_filter'] = array();

# als je standaard geen erotiek wilt op de index, uncomment dan volgende filter, je kan wel erotiek vinden door te zoeken
# $settings['index_filter'] = array('cat' => array('0' => array('a!d23', 'a!d24', 'a!d25', 'a!d26')));

// RSA keys
$settings['rsa_keys'] = array();
$settings['rsa_keys'][2] = array('modulo' => 'ys8WSlqonQMWT8ubG0tAA2Q07P36E+CJmb875wSR1XH7IFhEi0CCwlUzNqBFhC+P',
								 'exponent' => 'AQAB');
$settings['rsa_keys'][3] = array('modulo' => 'uiyChPV23eguLAJNttC/o0nAsxXgdjtvUvidV2JL+hjNzc4Tc/PPo2JdYvsqUsat',
								 'exponent' => 'AQAB');
$settings['rsa_keys'][4] = array('modulo' => '1k6RNDVD6yBYWR6kHmwzmSud7JkNV4SMigBrs+jFgOK5Ldzwl17mKXJhl+su/GR9',
								 'exponent' => 'AQAB');

# Include eventueel eigen settings, dit is ook een PHP file die settings die 
# hierin staan override (moet in de parent directory staan). Kan vooral handig zijn bij 
# upgrades van SpotWeb zodat je eigen settings bewaard blijven.
#
if (file_exists('../ownsettings.php')) { @include('../ownsettings.php'); }	# <== deze lijn mag je eventueel verwijderen	
if (file_exists('./ownsettings.php')) { @include('./ownsettings.php'); }	# <== deze lijn mag je eventueel verwijderen	

#
# override NNTP header/comments settings, als er geen aparte NNTP header/comments server is opgegeven, gebruik die van 
# de NZB server
#
if (empty($settings['nntp_hdr']['host'])) {
	$settings['nntp_hdr'] = $settings['nntp_nzb'];
} # if 


# Fix E_DEPRECATED errors
if (!defined(E_DEPRECATED)) {
	define(E_DEPRECATED, 8192);
} # if