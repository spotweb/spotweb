<?php

class SpotPage_statics extends SpotPage_Abs {
	private $_params;
	private $_currentCssFile;

	function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		
		$this->_params = $params;
	} # ctor

	function cbFixCssUrl($needle) {
		return 'URL(' . $this->_currentCssFile . '/' . trim($needle[1], '"\'') . ')';
	} # cbFixCssUrl
	
	function cbGetText($s) {
		return _($s[1]);
	} # cbGetText

	function mergeFiles($files) {
		$tmp = '';

		foreach($files as $file) {
			$fc = file_get_contents($file) . PHP_EOL;
			$fc = str_replace(
				Array('$COOKIE_EXPIRES',
					  '$COOKIE_HOST'),
				Array($this->_settings->get('cookie_expires'),
					  $this->_settings->get('cookie_host')),
				$fc);

			/*
			 * Usually i don't like regexe's as they are hard(er) to read,
			 * but this saves a lot of parsing so worth it
			 */
			$this->_currentCssFile = dirname($file);
			$fc = preg_replace_callback('/url\(([^)]+)\)/i', array($this, 'cbFixCssUrl'), $fc);
			
			# also replace any internationalisation strings in JS. 
			# Code copied from:
			#	http://stackoverflow.com/questions/5069321/preg-replace-and-gettext-problem
			$fc = preg_replace_callback("%\<t\>([a-zA-Z0-9',\#\%\:\/\?\.\\s\(\))]*)\</t\>%is", array($this, 'cbGetText'), $fc);
			
			$tmp .= $fc;
		} # foreach

		# and return the replaced body
		return array('body' => $tmp);
	} # mergeFiles
	
	function render() {
		$tplHelper = $this->_tplHelper;

		/* Make sure users has sufficient permission to perform this action */
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statics, '');
		
		/* Actually get the (merged) static fils */
		$mergedInfo = $this->mergeFiles($tplHelper->getStaticFiles($this->_params['type']));

		/*
		 * We encounter a bug when using mod_deflate and mod_fastcgi which causes the content-length
		 * header to be sent incorrectly. Hence, if we detect mod_fastcgi we dont send an content-length
		 * header at all
		 */
		if (!isset($_SERVER['REDIRECT_HANDLER']) || ($_SERVER['REDIRECT_HANDLER'] != 'php-fastcgi')) {
			Header("Content-Length: " . strlen($mergedInfo['body']));
		} # if

		switch($this->_params['type']) {
			case 'css'		: $this->sendContentTypeHeader('css');
							  Header('Vary: Accept-Encoding'); // sta toe dat proxy servers dit cachen
							  break;
			case 'js'		: $this->sendContentTypeHeader('js'); break;
			case 'ico'		: $this->sendContentTypeHeader('ico'); break;
		} # switch
		
		# we don't want this code to expire
		$this->sendExpireHeaders(false);
		
		# and send the correct last-modified header
		Header("Last-Modified: " . gmdate("D, d M Y H:i:s", $tplHelper->getStaticModTime($this->_params['type'])) . " GMT"); 

		echo $mergedInfo['body'];
	} # render

} # class SpotPage_statics
