<?php
class SpotTemplateHelper_We1rdo extends SpotTemplateHelper {

	/*
	 * Return a list of preferences specific for this template.
	 *
	 * When a user changes their template, and changes their
	 * preferences these settings are lost.
	 *
	 * Settings you want to be able to set must always be 
	 * present in this array with a sane default value, else
	 * the setting will not be saved.
	 */
	function getTemplatePreferences() {
		return array('we1rdo' =>
						array('example_setting' => 1)
					);
	} # getTemplatePreferences

	function cat2color($spot) {
		switch( (int) $spot['category']) {
			case 0: return 'blue'; break;
			case 1: return 'orange'; break;
			case 2: return 'green'; break;
			case 3: return 'red'; break;
		} # switch
		
		return '-';
	} # cat2color
	
	function filter2cat($s) {
		$cat = 0;
		if (stripos($s, 'cat0') !== false) {
			return "blue";
		} elseif (stripos($s, 'cat1') !== false) {
			return "orange";
		} elseif (stripos($s, 'cat2') !== false) {
			return "green";
		} elseif (stripos($s, 'cat3') !== false) {
			return "red";
		} # else
	} # filter2cat

	function getFilterIcons() {
		return array(
					'application'		=> _('Application'),
					'bluray'			=> _('Blu-Ray'),
					'book'				=> _('Book'),
					'controller'		=> _('Game'),
					'custom'			=> _('Plain'),
					'divx'				=> _('DivX'),
					'female'			=> _('Erotica'),
					'film'				=> _('Movie'),
					'hd'				=> _('HD'),
					'ipod'				=> _('iPod'),
					'linux'				=> _('Linux'),
					'apple'				=> _('Apple'),
					'mpg'				=> _('MPEG'),
					'music'				=> _('Music'),
					'nintendo_ds'		=> _('Nintendo DS'),
					'nintendo_wii'		=> _('Nintendo Wii'),
					'phone'				=> _('Phone'),
					'picture'			=> _('Picture'),
					'playstation'		=> _('Playstation'),
					'tv'				=> _('TV'),
					'vista'				=> _('Vista'),
					'windows'			=> _('Windows'),
					'wmv'				=> _('WMV'),
					'xbox'				=> _('Xbox'),
					'dvd'				=> _('DVD'),
					'pda'				=> _('PDA')
		);
	} # getFilterIconList

	function getSmileyList() {
		return array('biggrin' => 'templates/we1rdo/smileys/biggrin.gif',
				'bloos' => 'templates/we1rdo/smileys/bloos.gif',
				'buigen' => 'templates/we1rdo/smileys/buigen.gif',
				'censored' => 'templates/we1rdo/smileys/censored.gif',
				'clown' => 'templates/we1rdo/smileys/clown.gif',
				'confused' => 'templates/we1rdo/smileys/confused.gif',
				'cool' => 'templates/we1rdo/smileys/cool.gif',
				'exactly' => 'templates/we1rdo/smileys/exactly.gif',
				'frown' => 'templates/we1rdo/smileys/frown.gif',
				'grijns' => 'templates/we1rdo/smileys/grijns.gif',
				'heh' => 'templates/we1rdo/smileys/heh.gif',
				'huh' => 'templates/we1rdo/smileys/huh.gif',
				'klappen' => 'templates/we1rdo/smileys/klappen.gif',
				'knipoog' => 'templates/we1rdo/smileys/knipoog.gif',
				'kwijl' => 'templates/we1rdo/smileys/kwijl.gif',
				'lollig' => 'templates/we1rdo/smileys/lollig.gif',
				'maf' => 'templates/we1rdo/smileys/maf.gif',
				'ogen' => 'templates/we1rdo/smileys/ogen.gif',
				'oops' => 'templates/we1rdo/smileys/oops.gif',
				'pijl' => 'templates/we1rdo/smileys/pijl.gif',
				'redface' => 'templates/we1rdo/smileys/redface.gif',
				'respekt' => 'templates/we1rdo/smileys/respekt.gif',
				'schater' => 'templates/we1rdo/smileys/schater.gif',
				'shiny' => 'templates/we1rdo/smileys/shiny.gif',
				'sleephappy' => 'templates/we1rdo/smileys/sleephappy.gif',
				'smile' => 'templates/we1rdo/smileys/smile.gif',
				'uitroepteken' => 'templates/we1rdo/smileys/uitroepteken.gif',
				'vlag' => 'templates/we1rdo/smileys/vlag.gif',
				'vraagteken' => 'templates/we1rdo/smileys/vraagteken.gif',
				'wink' => 'templates/we1rdo/smileys/wink.gif');
	} # getSmileyList
	
	# Geeft een lijst van onze static files terug die door de static page gelezen wordt
	function getStaticFiles($type) {
		switch($type) {
			case 'js'	: {
				return array('js/jquery/jquery.min.js', 
								'js/jquery/jquery-ui.custom.min.js',
								'js/jquery/jquery.cookie.js',
								'js/jquery/jquery.hotkeys.js',
								'js/jquery/jquery.form.js',
								'js/jquery-json/jquery.json-2.3.js',
								'js/sha1/jquery.sha1.js',
								'js/posting/posting.js',
								'js/dynatree/jquery.dynatree.min.js',
								'templates/we1rdo/js/jquery.address.js',
								'templates/we1rdo/js/scripts.js',
								'templates/we1rdo/js/we1rdopost.js',
								'templates/we1rdo/js/treehelper.js',
								'templates/we1rdo/js/jquery.ui.nestedSortable.js',
								'templates/we1rdo/js/jquery.tipTip.minified.js'
								);
				break;
			} # case js
			
			case 'css'	: {
				return array('js/dynatree/skin-vista/ui.dynatree.css',
							 'templates/we1rdo/css/jquery-ui-1.8.13.custom.css',
							 'templates/we1rdo/css/spoticons.css',
							 'templates/we1rdo/css/style.css',
							 'templates/we1rdo/css/tipTip.css'
							 );
				break;
			} # case css
							 
			case 'ico'	: {
				return array('images/favicon.ico');
				break;
			} # case 'ico'
		} # switch
		
		return array();
	} # getStaticFiles 
	
} # class We1rdoTemplateHelper
