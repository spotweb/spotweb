<?php
abstract class SpotPage_Abs {
	protected $_db;
	protected $_settings;
	protected $_pageTitle;
	protected $_currentSession;
	protected $_spotSec;
	protected $_tplHelper;

	protected $_templatePaths;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
		$this->_tplHelper = $this->getTplHelper(array());

		/*
		 * Create a list of paths where to look for template files in
		 * the correct (last template first) order
		 */
		$this->_templatePaths = array('templates/' . $currentSession['active_tpl'] . '/');
		foreach($this->_tplHelper->getParentTemplates() as $parentTemplate) {
			$this->_templatePaths[] = 'templates/' . $parentTemplate . '/';
		} # foreach
	} # ctor

	/* 
	 * Standaard mogen paginas niet gecached worden 
	 * om invalid cached informatie te voorkomen. Kan overriden worden
	 * per pagina
	 */
	function sendExpireHeaders($preventCaching) {
		if ($preventCaching) {
			Header("Cache-Control: private, post-check=1, pre-check=2, max-age=1, must-revalidate");
			Header("Expires: Mon, 12 Jul 2000 01:00:00 GMT");
		} else {
			# stuur een expires header zodat dit een jaar of 10 geldig is
			Header("Cache-Control: public");
			Header("Expires: " . gmdate("D, d M Y H:i:s", (time() + (86400 * 3650))) . " GMT");
			Header("Pragma: ");
		} # if
	} # sendExpireHeaders
	
	/*
	 * Stuur een content header, dit zorgt er voor dat de browser
	 * eventuele content sneller kan parsen
	 */
	function sendContentTypeHeader($type) {
		switch($type) {
			case 'xml'		: Header("Content-Type: text/xml; charset=utf-8"); break;
			case 'rss'		: Header("Content-Type: application/rss+xml; charset=utf-8"); break;
			case 'json'		: Header("Content-Type: application/json; charset=utf-8"); break;
			case 'css'		: Header("Content-Type: text/css; charset=utf-8"); break;
			case 'js'		: Header("Content-Type: application/javascript; charset=utf-8"); break;
			case 'ico'		: Header("Content-Type: image/x-icon"); break;
			
			default 		: Header("Content-Type: text/html; charset=utf-8"); break;
		} # switch
		
	} # sendContentTypeHeader

	
	# Geef the tpl helper terug
	private function getTplHelper($params) {
		$tplName = $this->_currentSession['active_tpl'];

		$className = 'SpotTemplateHelper_' . ucfirst($tplName);
		$tplHelper = new $className($this->_settings, $this->_currentSession, $this->_db, $params);

		return $tplHelper;
	} # getTplHelper
		
	
	/*
	 * Display de template
	 */
	function template($tpl, $params = array()) {
		SpotTiming::start(__FUNCTION__ . ':' . $tpl);
		
		extract($params, EXTR_REFS);
		$settings = $this->_settings;
		$pagetitle = $this->_pageTitle;
		
		# update the template helper variables
		$this->_tplHelper->setParams($params);
		
		# Expose some variables to the template script in its local scope
		$tplHelper = $this->_tplHelper;
		$currentSession = $this->_currentSession;
		$spotSec = $this->_currentSession['security'];

		# send any expire headers
		$this->sendExpireHeaders(true);
		$this->sendContentTypeHeader('html');
		
		# and include the template
		foreach($this->_templatePaths as $tplPath) {
			if (file_exists($tplPath . $tpl . '.inc.php')) {
				require_once($tplPath . $tpl . '.inc.php');

				break;
			} # if
		} # foreach
		SpotTiming::stop(__FUNCTION__ . ':' . $tpl, array($params));
	} # template
	
	/*
	 * Daadwerkelijk renderen van de pagina -- implementatie specifiek
	 */
	abstract function render();
	
	/*
	 * Renderen van een permission denied pagina, kan overridden worden door een implementatie
	 * specifieke renderer
	 */
	function permissionDenied($exception, $page, $http_referer) {
		$this->template('permdenied',
							array('exception' => $exception,
								  'page' => $page,
								  'http_referer' => $http_referer));
	} # permissionDenied
	
} # SpotPage_Abs
