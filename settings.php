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
# Moeten de headers door retrieve volledig geladen worden? Als je dit op 'true' zet wordt 
# het ophalen van headers veel, veel trager. Het staat je dan echter wel toe om te filteren op userid.
#
$settings['retrieve_full'] = true;

# moeten wij comments ophalen?
$settings['retrieve_comments'] = true;

# Retentie op de spots (in dagen). Oudere spots worden verwijderd. Selecteer 0 om spots niet te verwijderen
$settings['retention'] = 0;

# Zet een minimum datum vanaf wanneer je spots op wilt halen, om alle spots van FTD te skippen geef je hier 1290578400 op
# Andere data kun je omrekenen op http://www.unixtimestamp.com/
$settings['retrieve_newer_than'] = 0;

# db
$settings['db']['engine'] = 'mysql';				# <== keuze uit pdo_sqlite, pdo_pgsql, mysql en pdo_mysql
$settings['db']['host'] = 'localhost';
$settings['db']['dbname'] = 'spotweb';
$settings['db']['user'] = 'spotweb';
$settings['db']['pass'] = 'spotweb';

# Als je sqlite wilt gebruiken, vul dan onderstaande in
#$settings['db']['engine'] = 'pdo_sqlite'; 			# <== keuze uit pdo_sqlite, pdo_pgsql, mysql en pdo_mysql
#$settings['db']['path'] = './nntpdb.sqlite3';	# <== als je geen SQLite3 gebruikt, kan dit weg	

# waar moeten we de templates vinden?
# zet eerst de standaard waarden...
# deze kunnen in de ownsettings nog worden aangepast.
# het detecteren komt pas na het laden van de ownsettings.
$settings['templates']['autodetect'] = true;
$settings['templates']['default'] = 'we1rdo';
$settings['templates']['mobile'] = 'mobile';

# Als er een nieuwe user aangemaakt wordt, tot welke groepen maken we deze
# dan standaard lid? 
$settings['newuser_grouplist'] = array(
		Array('groupid' => 1, 'prio' => 1),
		Array('groupid' => 2, 'prio' => 2)
	);

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
					 

# stacktraces maken het gemakkelijk bij een fout om een probleem te achterhalen, 
# ze kunnen echter ook gevoelige informatie (bv. je usenet account!) bevatten. Als je
# je spotweb installatie dus deelt met meerdere mensen, zet deze dan op false.
$settings['enable_stacktrace'] = true;

# Als een user niet expliciet geauthenticeerd is, dan wordt deze user standaard ingelogged
# met een userid van 1 -- dit is de builtin anonymous user. Als je je Spotweb installatie
# helemaal alleen gebruikt, kan je dit eventueel laten herleiden naar een andere user zodat
# je Spotweb volledig kan gebruiken (inclusief posten van comments en dergelijke) zonder
# dat je ooit hoeft in te loggen.
$settings['nonauthenticated_userid'] = 1;

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
	$settings['quicklinks'][] = Array('Reset filters', "images/icons/home.png", "?search[tree]=&amp;search[unfiltered]=true", "", Array(SpotSecurity::spotsec_view_spots_index, ''));
	$settings['quicklinks'][] = Array('Nieuw', "images/icons/today.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=New:0", "", Array(SpotSecurity::spotsec_keep_own_seenlist, ''));
	$settings['quicklinks'][] = Array('Watchlist', "images/icons/fav.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Watch:0", "", Array(SpotSecurity::spotsec_keep_own_watchlist, ''));
	$settings['quicklinks'][] = Array('Gedownload', "images/icons/download.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Downloaded:0", "", Array(SpotSecurity::spotsec_keep_own_downloadlist, ''));
	$settings['quicklinks'][] = Array('Recent bekeken', "images/icons/eye.png", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Seen:0", "", Array(SpotSecurity::spotsec_keep_own_seenlist, ''));
	$settings['quicklinks'][] = Array('Documentatie', "images/icons/help.png", "https://github.com/spotweb/spotweb/wiki", "external", Array(SpotSecurity::spotsec_view_spots_index, ''));
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
	die("allow_user_templates wordt niet meer bijgheouden, dit is een user preference geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['count_newspots'])) {
	die("count_newspots is een user preference geworden (en afschermbaar via het rechtensysteem). Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['keep_seenlist'])) {
	die("keep_seenlist is een user preference geworden (en afschermbaar via het rechtensysteem). Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['auto_markasread'])) {
	die("auto_markasread is een user preference geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['keep_downloadlist'])) {
	die("keep_downloadlist is een user preference geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['keep_watchlist'])) {
	die("keep_watchlist is een user preference geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['show_updatebutton'])) {
	die("show_updatebutton is een user right geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['show_nzbbutton'])) {
	die("show_nzbbutton is een user right geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['nzb_search_engine'])) {
	die("nzb_search_engine is een user preference geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['show_multinzb'])) {
	die("show_multinzb is een user preference geworden. Haal dit aub weg uit je ownsettings.php" . PHP_EOL);
} # if

if (isset($settings['nzbhandling'])) {
	die("nzbhandling is een user preference geworden. Haal dit aub weg uit je ownsettings.php (vergeet de settings niet te noteren in het userpreferences scherm!)" . PHP_EOL);
} # if

if (isset($settings['filters'])) {
	die("filters zijn een user preference geworden. Haal de filters aub weg uit je ownsettings.php" . PHP_EOL);
} # if

# Controleer op oud type quicklinks (zonder security)
foreach($settings['quicklinks'] as $link) {
	if (count($link) != 5) {
		die("Quicklinks moeten voortaan ook een security check bevatten, wijzig je quicklinks in je settings.php (zie settings.php voor een voorbeeld)");
	} # if
} # foreach

