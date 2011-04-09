<?php

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-   WIJZIG ONDERSTAANDE  =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
$settings['nntp_nzb']['host'] = 'news.ziggo.nl';    # <== Geef hier je nntp server in
$settings['nntp_nzb']['user'] = 'xx';               # <== Geef hier je username in
$settings['nntp_nzb']['pass'] = 'yy';               # <== Geef hier je password in
$settings['nntp_nzb']['enc'] = false;               # <== false|'tls'|'ssl', defaults to false.
$settings['nntp_nzb']['port'] = 119;                # <== set to 563 in case of encryption

# =-=-=-=-=-=-=-=- Als je een aparte 'headers' newsserver nodig hebt, uncomment dan volgende =-=-=-=-=-=-=-=-=-
$settings['nntp_hdr']['host'] = '';
$settings['nntp_hdr']['user'] = '';
$settings['nntp_hdr']['pass'] = '';
$settings['nntp_hdr']['enc'] = false;
$settings['nntp_hdr']['port'] = 119;

# Waar is SpotWeb geinstalleerd (voor de buitenwereld), deze link is nodig voor zaken als de RSS feed en de 
# sabnzbd integratie. Let op de afsluitende slash "/"!
if (isset($_SERVER['SERVER_PROTOCOL'])) {
	$settings['spotweburl'] = (strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . (dirname($_SERVER['PHP_SELF']) != '/' ? dirname($_SERVER['PHP_SELF']). '/' : '/');
} else {
	$settings['spotweburl'] = 'http://mijnuniekeservernaam/spotweb/';
} # if

# Standaard willen we niet dat robots ons kunnen indexeren, deze setting geeft de *hint* aan
# robots om ons niet te indexeren, maar dit is geen garantie dat het niet gebeurt.
$settings['deny_robots'] = true;

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= Filters =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# Default set gemaakt door 'Nakebod'
$settings['filters'] = array(    
    Array("Beeld", "images/icons/film.png", "cat0_a,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", "", 
        Array(
            Array("DivX", "images/icons/divx.png", "cat0_a0,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", ""),
            Array("WMV", "images/icons/wmv.png", "cat0_a1,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", ""),
            Array("MPEG", "images/icons/mpg.png", "cat0_a2,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", ""),
            Array("DVD", "images/icons/dvd.png", "cat0_a3,cat0_a10,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", ""),
            Array("HD", "images/icons/hd.png", "cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", ""),
            Array("Series", "images/icons/tv.png", "cat0_d11", ""),
            Array("Boeken", "images/icons/book.png", "cat0_a5,~cat0_d11,~cat0_d23,~cat0_d24,~cat0_d25,~cat0_d26", ""),
            Array("Erotiek", "images/icons/female.png", "cat0_d23,cat0_d24,cat0_d25,cat0_d26", "")
        )
    ),    
    Array("Muziek", "images/icons/music.png", "cat1_a", "", 
        Array(
            Array("Compressed", "images/icons/music.png", "cat1_a0,cat1_a3,cat1_a5,cat1_a6", ""),
            Array("Lossless", "images/icons/music.png", "cat1_a2,cat1_a4,cat1_a7,cat1_a8", "")
        )
    ),
    Array("Spellen", "images/icons/controller.png", "cat2_a", "", 
        Array(
            Array("Windows", "images/icons/windows.png", "cat2_a0", ""),
            Array("Mac / Linux", "images/icons/linux.png", "cat2_a1,cat2_a2", ""),
            Array("Playstation", "images/icons/playstation.png", "cat2_a3,cat2_a4,cat2_a5,cat2_a12", ""),
            Array("XBox", "images/icons/xbox.png", "cat2_a6,cat2_a7", ""),
            Array("Nintendo", "images/icons/nintendo_ds.png", "cat2_a8,cat2_a9,cat2_a10,cat2_a11", ""),
            Array("Smartphone / PDA", "images/icons/pda.png", "cat2_a13,cat2_a14,cat2_a15", "")
        )
    ),
    Array("Applicaties", "images/icons/application.png", "cat3_a", "", 
        Array(
            Array("Windows", "images/icons/vista.png", "cat3_a0", ""),
            Array("Mac / Linux / OS2", "images/icons/linux.png", "cat3_a1,cat3_a2,cat3_a3", ""),
            Array("PDA / Navigatie", "images/icons/pda.png", "cat3_a4,cat3_a5,cat3_a6,cat3_a7", "")
        )
    )
);

// version
define('VERSION', '0.6a');

#
# SpotNet ondersteund moderatie van de gepostte spots en reacties, dit gebeurt
# door middel van moderatie berichten in de nieuwsgroup waar ook de spots worden
# gepost.
#
# SpotWeb ziet deze berichten ook, en zal er iets mee moeten doen. Afhankelijk van
# wat je wilt moet je onderstaande setting aanpassen naar een van de volgende waardes:
#
# 	disable				- 	Doe helemaal niks met de moderatie
#	act					- 	Wis de gemodereerde spots
#	markspot			-	Markeer de gemodereerde spots als gemodereerd. Er is op 
#							dit moment nog geen UI om dit te filteren of iets dergelijks.
#
$settings['spot_moderation'] = 'act';


#
# We definieeren in een aantal stappen wat er moet gebeuren met NZB files
# Er zijn een aantal verschillende acties mogelijk:
#	* disable			- Geen acties, toon enkel de 'download nzb' knop
#	* display			- Stuurt de NZB file naar de server, intern gebruik
#	* save				- Save de file op disk
#	* runcommand		- Save de file op disk en roep een commando aan
#	* push-sabnzbd		- Roep sabnzbd+ aan via HTTP door SpotWeb, schrijft de NZB lokaal weg
#	* client-sabnzbd	- Roep sabnzbd+ aan via de users' browser (oude default)
#
# Settings:
#   local_dir			- Waar moet de NZB file opgeslagen worden (voor save en runcommand)
#	command				- Programma dat uitgevoerd moet worden (bij savecommand), Mogelijke parameters: $SPOTTITLE en $NZBPATH
#	prepare_action		- Wat moet er gebeuren met NZB's? Mogelijke params: 'merge' en 'zip'
#	sabnzbd				- host		 - Pas deze aan naar de sabnzbd host plus port
#						- apikey	 - sabnzbd API key	
#						- url		 - 
#
$settings['nzbhandling']['action'] = 'push-sabnzbd';
$settings['nzbhandling']['local_dir'] = '';
$settings['nzbhandling']['prepare_action'] = 'merge';
$settings['nzbhandling']['command'] = '';
$settings['nzbhandling']['sabnzbd'] = array();
$settings['nzbhandling']['sabnzbd']['host'] = '192.168.10.122:8081';
$settings['nzbhandling']['sabnzbd']['apikey'] = 'xxx';
$settings['nzbhandling']['sabnzbd']['url'] = 'http://$SABNZBDHOST/sabnzbd/api?mode=$SABNZBDMODE&name=$NZBURL&nzbname=$SPOTTITLE&cat=$SANZBDCAT&apikey=$APIKEY&output=text';
	
#
# Moeten de headers door retrieve volledig geladen worden? Als je dit op 'true' zet wordt 
# het ophalen van headers veel, veel trager. Het staat je dan echter wel toe om te filteren op userid.
#
$settings['retrieve_full'] = true;

# moeten wij comments ophalen?
$settings['retrieve_comments'] = true;

# hoeveel spots wil je tonen op 1 pagina?
$settings['prefs']['perpage'] = 100;

# Retentie op de spots (in dagen). Oudere spots worden verwijderd. Selecteer 0 om spots niet te verwijderen
$settings['retention'] = 0;

# Zet een minimum datum vanaf wanneer je spots op wilt halen, om alle spots van FTD te skippen geef je hier 1290578400 op
$settings['retrieve_newer_than'] = 0;

# hoe willen we datums geformatteerd hebben? Geef een strng in compatibel met http://php.net/strftime of 'human' voor 
# een human-readable verhaal
# $settings['prefs']['date_formatting'] = "%a, %d-%b-%Y (%H:%M)";
$settings['prefs']['date_formatting'] = "human";

# Is OpenSSL juist geconfigureerd en geladen als module? Zet dit dan op true, dan gaat het signen en verifieeren
# van spots en comments sneller
$settings['use_openssl'] = false; 

# settings 
$settings['hdr_group'] = 'free.pt';
$settings['nzb_group'] = 'alt.binaries.ftd';
$settings['comment_group'] = 'free.usenet';

# db
$settings['db']['engine'] = 'pdo_sqlite'; 			# <== keuze uit pdo_sqlite, mysql en pdo_mysql
$settings['db']['path'] = './nntpdb.sqlite3';	# <== als je geen SQLite3 gebruikt, kan dit weg	

# Als je MySQL wilt gebruiken, vul dan onderstaande in
#$settings['db']['engine'] = 'mysql';
#$settings['db']['host'] = 'localhost';
#$settings['db']['dbname'] = 'spotweb';
#$settings['db']['user'] = 'spotweb';
#$settings['db']['pass'] = 'spotweb';

# waar moeten we de templates vinden?
# zet eerst de standaard waarden...
# deze kunnen in de ownsettings nog worden aangepast.
# het detecteren komt pas na het laden van de ownsettings.

$settings['templates']['autodetect'] = true;
$settings['templates']['default'] = 'templates/we1rdo/';
$settings['templates']['mobile'] = 'templates/mobile/';

$settings['allow_user_template'] = true;
$settings['available_templates'] = Array('we1rdo'	=> 'we1rdo', 
						'mobile'	=> 'mobile',
						'splendid'	=> 'splendid'
					);

# tonen we een update knop in de web ui?
$settings['show_updatebutton'] = false;

# toon een download-nzb knop op het overzicht?
$settings['show_nzbbutton'] = true;

# toon een multi-nzb knop?
$settings['show_multinzb'] = true;

# toon aantal nieuwe spots in het menu? Kan vertragend werken, uitzetten op trage systemen!
$settings['count_newspots'] = true;

# moeten we bijhouden welke downloads er gedaan zijn?
$settings['keep_downloadlist'] = true;

# moeten we een watchlist bijhouden?
$settings['keep_watchlist'] = true;

# highlight nieuwe items - cookies
$settings['cookie_expires'] = 30; // aantal dagen dat cookie bewaard moet worden
if (isset($_SERVER['HTTP_HOST'])) {
	$settings['cookie_host'] = $_SERVER['HTTP_HOST']; // cookie host
} # if

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
$settings['search_url'] = 'http://www.binsearch.info/?adv_age=&amp;q=$SPOTFNAME';
# $settings['search_url'] = 'http://nzbindex.nl/search/?q=$SPOTFNAME';

# de filter die standaard gebruikt wordt op de index pagina (als er geen filters oid opgegeven zijn), 
# zorg dat deze wel gedefinieerd is.
$settings['index_filter'] = array();

# als je standaard geen erotiek wilt op de index, uncomment dan volgende filter, je kan wel erotiek vinden door te zoeken
# $settings['index_filter'] = array('tree' => '~cat0_z4');

#
# RSA keys
# Worden gebruikt om te valideren of spots geldig zijn, hoef je normaal niet aan te komen
#
$settings['rsa_keys'] = array();
$settings['rsa_keys'][2] = array('modulo' => 'ys8WSlqonQMWT8ubG0tAA2Q07P36E+CJmb875wSR1XH7IFhEi0CCwlUzNqBFhC+P',
								 'exponent' => 'AQAB');
$settings['rsa_keys'][3] = array('modulo' => 'uiyChPV23eguLAJNttC/o0nAsxXgdjtvUvidV2JL+hjNzc4Tc/PPo2JdYvsqUsat',
								 'exponent' => 'AQAB');
$settings['rsa_keys'][4] = array('modulo' => '1k6RNDVD6yBYWR6kHmwzmSud7JkNV4SMigBrs+jFgOK5Ldzwl17mKXJhl+su/GR9',
								 'exponent' => 'AQAB');

#
# Hoeveel verschillende headers (van danwel spots danwel comments) moeten er per keer opgehaald worden? 
# Als je regelmatig timeouts krijgt van retrieve.php, vrelaag dan dit aantal
#
$settings['retrieve_increment'] = 1000;

#
# Include eventueel eigen settings, dit is ook een PHP file. 
# Settings welke hierin staan zullen de instellingen van deze file overiden.
#
# We raden aan om je instellingen in deze eigen file te zetten zodat bij een upgrade
# je instellingen bewaard blijven.
#
if (file_exists('../ownsettings.php')) { include_once('../ownsettings.php'); }	# <== deze lijn mag je eventueel verwijderen	
if (file_exists('ownsettings.php')) { include_once('ownsettings.php'); }	# <== deze lijn mag je eventueel verwijderen	

# QuickLinks, we testen eerst of hij niet al door iemand anders is gezet in ownsettings.php en
# anders vullen we hem zelf op. We kunnen dit niet boven ownsettings.php plaatsen want dan missen
# we de keep_watchlist en keep_downloadlist settings.
if (!isset($settings['quicklinks'])) {
	$settings['quicklinks'] = Array();
	$settings['quicklinks'][] = Array('Reset filters', "images/icons/home.png", "?search[tree]=&amp;search[unfiltered]=true", "");
	$settings['quicklinks'][] = Array('Nieuw', "images/icons/today.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=New:0", "");
	if ($settings['keep_watchlist']) {
		$settings['quicklinks'][] = Array('Watchlist', "images/icons/fav.png", "?page=watchlist", "");
	}
	if ($settings['keep_downloadlist']) {
		$settings['quicklinks'][] = Array('Gedownload', "images/icons/download.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Downloaded:0", "");
	}
} # if isset

#
# Ga nu de template zetten
#

if (($settings['templates']['autodetect']) && 
	(isset($_SERVER['HTTP_USER_AGENT'])) &&
	(isset($_SERVER['HTTP_ACCEPT'])) ) {
		include_once('Mobile_Detect.php');
		$detect = new Mobile_Detect();

		if ($detect->isMobile()) {
			$settings['tpl_path'] = $settings['templates']['mobile']; 
		} else { 
			if (isset($_COOKIE['template'])) {
				$chosenTemplate = $_COOKIE['template'];
			} # if 

			if ($settings['allow_user_template'] == true && isset($chosenTemplate) && 
				(array_search($chosenTemplate, $settings['available_templates']) !== false)) {
				// allow_user_template is ingeschakeld EN er is een cookie EN de cookie bevat een geldige template-naam --> tpl_path opzoeken
				$settings['tpl_path'] = 'templates/' . $settings['available_templates'][$chosenTemplate] . '/';
				
				// verleng cookie
				setcookie('template', $chosenTemplate, time()+(86400*$settings['cookie_expires']), '/', $settings['cookie_host']);
			} else {
				$settings['tpl_path'] = $settings['templates']['default']; 
			} # else
		} # else
} else {
	$settings['tpl_path'] = $settings['templates']['default'];
} # else

# Fix eventueel oude template paths naar nieuwe template paths
if (substr($settings['tpl_path'], 0, strlen('./templates_')) == './templates_') {
	echo "LET OP! De lokatie van de templates is gewijzigd, pas je ownsettings.php aan!<br>";
	$settings['tpl_path'] = str_replace('templates_', 'templates/', $settings['tpl_path']);
} # if

# Override NNTP header/comments settings, als er geen aparte NNTP header/comments server is opgegeven, gebruik die van 
# de NZB server
#
if (empty($settings['nntp_hdr']['host'])) {
	$settings['nntp_hdr'] = $settings['nntp_nzb'];
} # if 

# Voeg een sluitende slash toe als die er nog niet is
if (substr($settings['spotweburl'], -1) != '/') {
	$settings['spotweburl'] .= '/';
} # if
