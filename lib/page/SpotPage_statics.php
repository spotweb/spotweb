<?php
# Deze klasse proxied feitelijk requests voor meerdere resources, dit is voornamelijk handig
# als er meerdere JS files e.d. geinclude moeten worden. 
# 
# Normaal kan je dit ook met mod_expires (van apache) en gelijkaardigen oplossen, maar dit vereist
# server configuratie en dit kunnen we op deze manier vrij makkelijk in de webapp oplossen.
#
class SpotPage_statics extends SpotPage_Abs {
	private $_params;
	private $_currentCssFile;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		
		$this->_params = $params;
	} # ctor

	function cbFixCssUrl($needle) {
		return 'URL(' . dirname($this->_currentCssFile) . '/' . trim($needle[1], '"\'') . ')';
	} # cbFixCssUrl

	function mergeFiles($files) {
		$tmp = '';

		foreach($files as $file) {
			$fc = file_get_contents($file) . PHP_EOL;
			$fc = str_replace(
				Array('$HTTP_S',
					  '$COOKIE_EXPIRES',
					  '$COOKIE_HOST'),
				Array((@$_SERVER['HTTPS'] == 'on' ? 'https' : 'http'),
				       $this->_settings->get('cookie_expires'),
					   $this->_settings->get('cookie_host')),
				$fc);

			# ik ben geen fan van regexpen maar in dit scheelt het
			# het volledig parsen van de content van de CSS file dus
			# is het het overwegen waard.
			$this->_currentCssFile = $file;
			$fc = preg_replace_callback('/url\((.+)\)/i', array($this, 'cbFixCssUrl'), $fc);
			$tmp .= $fc;
		} # foreach

		# en geef de body terug
		return array('body' => $tmp);
	} # mergeFiles

	function render() {
		$tplHelper = $this->getTplHelper(array());

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statics, '');
		
		# vraag de content op
		$mergedInfo = $this->mergeFiles($tplHelper->getStaticFiles($this->_params['type']));

		# stuur een expires header zodat dit een jaar of 10 geldig is
		Header("Cache-Control: public");
		Header("Expires: " . gmdate("D, d M Y H:i:s", (time() + (86400 * 3650))) . " GMT");
		Header("Pragma: ");

		# Er is een bug met mod_deflate en mod_fastcgi welke ervoor zorgt dat de content-length
		# header niet juist geupdate wordt. Als we dus mod_fastcgi detecteren, dan sturen we
		# content-length header niet mee
		if (isset($_SERVER['REDIRECT_HANDLER']) && ($_SERVER['REDIRECT_HANDLER'] != 'php-fastcgi')) {
			Header("Content-Length: " . strlen($mergedInfo['body']));
		} # if

		# en stuur de versie specifieke content
		switch($this->_params['type']) {
			case 'css'		: Header('Content-Type: text/css'); break;
			case 'js'		: Header('Content-Type: application/javascript; charset=utf-8'); break;
			case 'ico'		: Header('Content-Type: image/x-icon'); break;
		} # switch

		echo $mergedInfo['body'];
	} # render

} # class SpotPage_statics