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

# =-=-=-=-=-=-=-=- Als je een aparte 'upload' newsserver nodig hebt, uncomment dan volgende =-=-=-=-=-=-=-=-=-
$settings['nntp_post']['host'] = '';
$settings['nntp_post']['user'] = '';
$settings['nntp_post']['pass'] = '';
$settings['nntp_post']['enc'] = false;
$settings['nntp_post']['port'] = 119;

# Waar is SpotWeb geinstalleerd (voor de buitenwereld), deze link is nodig voor zaken als de RSS feed en de 
# sabnzbd integratie. Let op de afsluitende slash "/"!
if (isset($_SERVER['SERVER_PROTOCOL'])) {
    $settings['spotweburl'] = (@$_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . @$_SERVER['HTTP_HOST'] . (dirname($_SERVER['PHP_SELF']) != '/' ? dirname($_SERVER['PHP_SELF']). '/' : '/');	
} else {
	$settings['spotweburl'] = 'http://mijnuniekeservernaam/spotweb/';
} # if

# Waar staat je OpenSSL.cnf ? Deze file moet leesbaar zijn voor de webserver als je de OpenSSL
# extensie geinstalleerd hebt
$settings['openssl_cnf_path'] = "lib/openssl/openssl.cnf";

# Standaard willen we niet dat robots ons kunnen indexeren, deze setting geeft de *hint* aan
# robots om ons niet te indexeren, maar dit is geen garantie dat het niet gebeurt.
$settings['deny_robots'] = true;

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= Filters =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# Default set gemaakt door 'Nakebod'
$settings['filters'] = array(    
    Array("Beeld", "images/icons/film.png", "cat0_z0", "",
        Array(
            Array("DivX", "images/icons/divx.png", "cat0_a0,~cat0_z1,~cat0_z2,~cat0_z3", ""),
            Array("WMV", "images/icons/wmv.png", "cat0_a1,~cat0_z1,~cat0_z2,~cat0_z3", ""),
            Array("MPEG", "images/icons/mpg.png", "cat0_a2,~cat0_z1,~cat0_z2,~cat0_z3", ""),
            Array("DVD", "images/icons/dvd.png", "cat0_a3,cat0_a10,~cat0_z1,~cat0_z2,~cat0_z3", ""),
            Array("HD", "images/icons/hd.png", "cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9,~cat0_z1,~cat0_z2,~cat0_z3", ""),
            Array("Series", "images/icons/tv.png", "cat0_z1", ""),
            Array("Boeken", "images/icons/book.png", "cat0_z2", ""),
            Array("Erotiek", "images/icons/female.png", "cat0_z3", "")
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
#	* save				- Save de file op disk gebruik makend van de sabnzbd category mapping
#	* runcommand		- Save de file op disk en roep een commando aan
#	* push-sabnzbd		- Roep sabnzbd+ aan via HTTP door SpotWeb, schrijft de NZB lokaal weg
#	* client-sabnzbd	- Roep sabnzbd+ aan via de users' browser (oude default)
#	* nzbget			- Roep NZBGet aan via HTTP door SpotWeb
#
# Settings:
#   local_dir			- Waar moet de NZB file opgeslagen worden (voor save en runcommand), mogelijke parameters: $SABNZBDCAT
#	command				- Programma dat uitgevoerd moet worden (bij savecommand), Mogelijke parameters: $SPOTTITLE en $NZBPATH
#	prepare_action		- Wat moet er gebeuren met NZB's? Mogelijke params: 'merge' en 'zip'
#	sabnzbd				- host		 - Pas deze aan naar de sabnzbd host plus port
#						- apikey	 - sabnzbd API key	
#						- url		 - 
#   nzbget				- host		 - Pas deze aan naar de nzbget host (zonder de port)
#						- port		 - Pas deze aan naar de nzbget port
#						- timeout
#						- username	 - Gereserveerd voor de toekomst (Username is hardcoded in nzbget v0.70)
#						- password	 - Server password van nzbget (zie config file van nzbget)
#
$settings['nzbhandling']['action'] = 'push-sabnzbd';
$settings['nzbhandling']['local_dir'] = '';
$settings['nzbhandling']['prepare_action'] = 'merge';
$settings['nzbhandling']['command'] = '';
$settings['nzbhandling']['sabnzbd'] = array();
$settings['nzbhandling']['sabnzbd']['host'] = '192.168.10.122:8081';
$settings['nzbhandling']['sabnzbd']['apikey'] = 'xxx';
$settings['nzbhandling']['sabnzbd']['url'] = 'http://$SABNZBDHOST/sabnzbd/api?mode=$SABNZBDMODE&name=$NZBURL&nzbname=$SPOTTITLE&cat=$SABNZBDCAT&apikey=$APIKEY&output=text';
$settings['nzbhandling']['nzbget'] = array();
$settings['nzbhandling']['nzbget']['host'] = '127.0.0.1';
$settings['nzbhandling']['nzbget']['port'] = '6789';
$settings['nzbhandling']['nzbget']['timeout'] = '30';
$settings['nzbhandling']['nzbget']['username'] = 'nzbget';
$settings['nzbhandling']['nzbget']['password'] = 'tegbzn6789';	
#
# Moeten de headers door retrieve volledig geladen worden? Als je dit op 'true' zet wordt 
# het ophalen van headers veel, veel trager. Het staat je dan echter wel toe om te filteren op userid.
#
$settings['retrieve_full'] = true;

# moeten wij comments ophalen?
$settings['retrieve_comments'] = true;

# Retentie op de spots (in dagen). Oudere spots worden verwijderd. Selecteer 0 om spots niet te verwijderen
$settings['retention'] = 0;

# Zet een minimum datum vanaf wanneer je spots op wilt halen, om alle spots van FTD te skippen geef je hier 1290578400 op
$settings['retrieve_newer_than'] = 0;

# db
$settings['db']['engine'] = 'mysql';
$settings['db']['host'] = 'localhost';
$settings['db']['dbname'] = 'spotweb';
$settings['db']['user'] = 'spotweb';
$settings['db']['pass'] = 'spotweb';

# Als je sqlite wilt gebruiken, vul dan onderstaande in
#$settings['db']['engine'] = 'pdo_sqlite'; 			# <== keuze uit pdo_sqlite, mysql en pdo_mysql
#$settings['db']['path'] = './nntpdb.sqlite3';	# <== als je geen SQLite3 gebruikt, kan dit weg	

# waar moeten we de templates vinden?
# zet eerst de standaard waarden...
# deze kunnen in de ownsettings nog worden aangepast.
# het detecteren komt pas na het laden van de ownsettings.

$settings['templates']['autodetect'] = true;
$settings['templates']['default'] = 'we1rdo';
$settings['templates']['mobile'] = 'mobile';

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

# Moeten we bijhouden welke individuele spots er zijn bekeken?
# Deze lijst wordt automatisch geleegd wanneer je "Markeer alles als gelezen" aanklikt!
$settings['keep_seenlist'] = true;

# Moeten spots automatisch na elke visit als gelezen worden gemarkeerd?
$settings['auto_markasread'] = true;

# moeten we bijhouden welke downloads er gedaan zijn?
$settings['keep_downloadlist'] = true;

# moeten we een watchlist bijhouden?
$settings['keep_watchlist'] = true;

# Cookie host
if (isset($_SERVER['HTTP_HOST'])) {
	// Strip leading periods
	$cookie_domain = ltrim($_SERVER['HTTP_HOST'], '.');

	// Strip www.
	if (strpos($cookie_domain, 'www.') === 0) {
		$cookie_domain = substr($cookie_domain, 4);
	}

	//Strip port numbers
	$cookie_domain = explode(':', $cookie_domain);
	$cookie_domain = '.' . $cookie_domain[0];
} # if

// Per RFC 2109, cookie domains must contain at least one dot other than the
// first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
if (isset($cookie_domain) && count(explode('.', $cookie_domain)) > 2 && !filter_var(ltrim($cookie_domain, '.'), FILTER_VALIDATE_IP)) {
	$settings['cookie_host'] = $cookie_domain;
	unset($cookie_domain);
} else {
	$settings['cookie_host'] = '';
} # else

# We kunnen een aantal onderdelen van Spotweb laten timen / profilen, zet deze op true om
# dat ook daadwerkelijk te doen
$settings['enable_timing'] = false;

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
					 

# NZB zoekmachine (gebruikt bij spots voor 24 november als download knop, en onderaan de spot info)
$settings['nzb_search_engine'] = 'binsearch';
#$settings['nzb_search_engine'] = 'nzbindex';

# de filter die standaard gebruikt wordt op de index pagina (als er geen filters oid opgegeven zijn), 
# zorg dat deze wel gedefinieerd is.
$settings['index_filter'] = array();

# als je standaard geen erotiek wilt op de index, uncomment dan volgende filter, je kan wel erotiek vinden door te zoeken
# $settings['index_filter'] = array('tree' => '~cat0_z3');

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
		$settings['quicklinks'][] = Array('Watchlist', "images/icons/fav.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Watch:0", "");
	}
	if ($settings['keep_downloadlist']) {
		$settings['quicklinks'][] = Array('Gedownload', "images/icons/download.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Downloaded:0", "");
	}
	if ($settings['keep_seenlist']) {
		$settings['quicklinks'][] = Array('Recent bekeken', "images/icons/eye.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Seen:0", "");
	}
	$settings['quicklinks'][] = Array('Documentatie', "images/icons/help.png", "https://github.com/spotweb/spotweb/wiki", "external");
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
			$settings['tpl_name'] = $settings['templates']['mobile']; 
		} else { 
			$settings['tpl_name'] = $settings['templates']['default']; 
		} # else
} else {
	$settings['tpl_name'] = $settings['templates']['default'];
} # else
$settings['tpl_name'] = str_replace('templates/', '', $settings['tpl_name']);

# Override NNTP header/comments settings, als er geen aparte NNTP header/comments server is opgegeven, gebruik die van 
# de NZB server
#
if (empty($settings['nntp_hdr']['host'])) {
	$settings['nntp_hdr'] = $settings['nntp_nzb'];
} # if

# Hetzelfde voor de NNTP upload server
if (empty($settings['nntp_post']['host'])) {
	$settings['nntp_post'] = $settings['nntp_nzb'];
} # if

# Als de OpenSSL module geladen is, moet de openssl_cnf_path naar een 
# leesbare configuratie file wijzen
if ((!is_readable($settings['openssl_cnf_path'])) && (extension_loaded("openssl"))) {
	die("openssl_cnf_path verwijst niet naar een leesbare OpenSSL configuratie file" . PHP_EOL);
} # if

# Voeg een sluitende slash toe als die er nog niet is
if (substr($settings['spotweburl'], -1) != '/') {
	$settings['spotweburl'] .= '/';
} # if

# Preferences lokaal niet meer toestaan
if (isset($settings['prefs']['perpage']) || (isset($settings['prefs']['date_formatting']))) {
	die("Preferences worden voortaan per user gezet, haal aub de preferences weg uit je ownsettings.php" . PHP_EOL);
} # if

# deprecated settings niet meer toestaan
if (isset($settings['cookie_expires'])) {
	die("Cookie_expires wordt voortaan in de db bijgehouden, haal aub deze weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['allow_user_template'])) {
	die("allow_user_templates wordt niet meer bijgheouden, dit is een user preference geworden" . PHP_EOL);
} # if