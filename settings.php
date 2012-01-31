<?php
# db
$settings['db']['engine'] = 'mysql';				# <== keuze uit pdo_pgsql, mysql en pdo_mysql
$settings['db']['host'] = 'localhost';
$settings['db']['dbname'] = 'spotweb';
$settings['db']['user'] = 'spotweb';
$settings['db']['pass'] = 'spotweb';

# Als je sqlite wilt gebruiken, vul dan onderstaande in
#$settings['db']['engine'] = 'pdo_sqlite'; 			# <== keuze uit pdo_sqlite, pdo_pgsql, mysql en pdo_mysql
#$settings['db']['path'] = './nntpdb.sqlite3';	# <== als je geen SQLite3 gebruikt, kan dit weg	

# Waar is SpotWeb geinstalleerd (voor de buitenwereld), deze link is nodig voor zaken als de RSS feed en de 
# sabnzbd integratie. Let op de afsluitende slash "/"!
if (isset($_SERVER['SERVER_PROTOCOL'])) {
    $settings['spotweburl'] = (@$_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . @$_SERVER['HTTP_HOST'] . (dirname($_SERVER['PHP_SELF']) != '/' && dirname($_SERVER['PHP_SELF']) != '\\' ? dirname($_SERVER['PHP_SELF']). '/' : '/');	
} else {
	$settings['spotweburl'] = 'http://mijnuniekeservernaam/spotweb/';
} # if

# Waar staat je OpenSSL.cnf ? Deze file moet leesbaar zijn voor de webserver als je de OpenSSL
# extensie geinstalleerd hebt
$settings['openssl_cnf_path'] = "lib/openssl/openssl.cnf";

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
		Array('groupid' => 2, 'prio' => 1),
		Array('groupid' => 3, 'prio' => 2)
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
					 
# Als een user niet expliciet geauthenticeerd is, dan wordt deze user standaard ingelogged
# met een userid van 1 -- dit is de builtin anonymous user. Als je je Spotweb installatie
# helemaal alleen gebruikt, kan je dit eventueel laten herleiden naar een andere user zodat
# je Spotweb volledig kan gebruiken (inclusief posten van comments en dergelijke) zonder
# dat je ooit hoeft in te loggen.
$settings['nonauthenticated_userid'] = 1;

#
# Include eventueel eigen settings, dit is ook een PHP file. 
# Settings welke hierin staan zullen de instellingen van deze file overiden.
#
# We raden aan om je instellingen in deze eigen file te zetten zodat bij een upgrade
# je instellingen bewaard blijven.
#
if (@file_exists('../ownsettings.php')) { include_once('../ownsettings.php'); }	# <== deze lijn mag je eventueel verwijderen	
if (file_exists('ownsettings.php')) { include_once('ownsettings.php'); }	# <== deze lijn mag je eventueel verwijderen	

# QuickLinks, we testen eerst of hij niet al door iemand anders is gezet in ownsettings.php en
# anders vullen we hem zelf op. We kunnen dit niet boven ownsettings.php plaatsen want dan missen
# we de keep_watchlist en keep_downloadlist settings.
if (!isset($settings['quicklinks'])) {
	$settings['quicklinks'] = Array();
	$settings['quicklinks'][] = Array('Reset filters', "home", "?search[tree]=&amp;search[unfiltered]=true", "", Array(SpotSecurity::spotsec_view_spots_index, ''), null);
	$settings['quicklinks'][] = Array('Nieuw', "today", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=New:0", "", Array(SpotSecurity::spotsec_keep_own_seenlist, ''), 'count_newspots');
	$settings['quicklinks'][] = Array('Watchlist', "fav", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Watch:0", "", Array(SpotSecurity::spotsec_keep_own_watchlist, ''), 'keep_watchlist');
	$settings['quicklinks'][] = Array('Gedownload', "download", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Downloaded:0", "", Array(SpotSecurity::spotsec_keep_own_downloadlist, ''), 'keep_downloadlist');
	$settings['quicklinks'][] = Array('Recent bekeken', "eye", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=Seen:0", "", Array(SpotSecurity::spotsec_keep_own_seenlist, ''), 'keep_seenlist');
	$settings['quicklinks'][] = Array('Mijn spots', "fav", "?search[tree]=&amp;search[unfiltered]=true&amp;search[value][]=MyPostedSpots:0", "", Array(SpotSecurity::spotsec_post_spot, ''), null);
	$settings['quicklinks'][] = Array('Statistieken', "stats", "?page=statistics", "", Array(SpotSecurity::spotsec_view_statistics, ''), null);
	$settings['quicklinks'][] = Array('Documentatie', "help", "https://github.com/spotweb/spotweb/wiki", "external", Array(SpotSecurity::spotsec_view_spots_index, ''), null);
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

# Als de OpenSSL module geladen is, moet de openssl_cnf_path naar een 
# leesbare configuratie file wijzen
if ((!is_readable($settings['openssl_cnf_path'])) && (extension_loaded("openssl"))) {
	throw new InvalidOwnSettingsSettingException("openssl_cnf_path does not contain a readable OpenSSL configuration filepath");
} # if

# Voeg een sluitende slash toe als die er nog niet is
if (substr($settings['spotweburl'], -1) != '/') {
	$settings['spotweburl'] .= '/';
} # if

# Preferences lokaal niet meer toestaan
if (isset($settings['prefs']['perpage']) || (isset($settings['prefs']['date_formatting']))) {
	throw new InvalidOwnSettingsSettingException("Preferences worden voortaan per user gezet");
} # if

# deprecated settings niet meer toestaan
$ownsettingserror = '';
$array = array('blacklist_url', 'cookie_expires', 'deny_robots', 'enable_stacktrace', 'enable_timing', 'external_blacklist', 'nntp_hdr', 'nntp_nzb', 'nntp_post', 'prefetch_image', 'prefetch_nzb', 'retention', 'retrieve_comments', 'retrieve_full', 'retrieve_full_comments', 'retrieve_increment', 'retrieve_newer_than', 'retrieve_reports', 'sendwelcomemail', 'spot_moderation');
foreach ($array as $value) {
	if (isset($settings[$value])) {
		$ownsettingserror .= ' * ' . $value . PHP_EOL;
	} # if
} # foreach

$array = array('allow_user_template', 'auto_markasread', 'filters', 'index_filter', 'keep_downloadlist', 'keep_watchlist', 'nzb_search_engine', 'nzbhandling', 'show_multinzb');
foreach ($array as $value) {
	if (isset($settings[$value])) {
		$ownsettingserror .= ' * ' . $value . PHP_EOL;
	} # if
} # foreach

$array = array('count_newspots', 'keep_seenlist');
foreach ($array as $value) {
	if (isset($settings[$value])) {
		$ownsettingserror .= ' * ' . $value . PHP_EOL;
	} # if
} # foreach

$array = array('show_nzbbutton', 'show_updatebutton');
foreach ($array as $value) {
	if (isset($settings[$value])) {
		$ownsettingserror .= ' * ' . $value . PHP_EOL;
	} # if
} # foreach

if (!empty($ownsettingserror)) {
	throw new InvalidOwnSettingsSettingException("Please remove " . $ownsettingserror . " from your 'ownsettings.php' file, this setting is set in the settings panel from within Spotweb itself");
} # if

# Make sure the template name in ownsettings.php doesn't end with a slash
foreach($settings['templates'] as $x => $y) {
	if (substr($y, -1) == '/') {
		throw new InvalidOwnSettingsSettingException("Please remove the trailing slash for the template name " . $x . " in your ownsettings.php");
	} # if
} # if

# Controleer op oud type quicklinks (zonder preference link)
foreach($settings['quicklinks'] as $link) {
	if (count($link) < 6) {
		throw new InvalidOwnSettingsSettingException("Quicklinks have to have a preferences check as well. Please modify the quickinks in your ownettings.php or remove them from your ownsetings.php");
	} # if
} # foreach
