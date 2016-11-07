<?php

abstract class SpotPage_Abs {
    /**
     * @var Dao_Factory
     */
    protected $_daoFactory;
    /**
     * @var Services_Settings_Base
     */
    protected $_settings;
    /**
     * Name of the page which we should render.
     * @var string
     */
    protected $_pageTitle;
    /**
     * @var array
     */
    protected $_currentSession;
    /**
     * @var SpotSecurity
     */
    protected $_spotSec;
    /**
     * @var SpotTemplateHelper
     */
    protected $_tplHelper;
    /**
     * @var string
     */
	protected $_templatePaths;
	
	function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession) {
		$this->_daoFactory = $daoFactory;
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
	 * Send either 'do cache' or 'do not cache' headers to the client
	 */
	function sendExpireHeaders($preventCaching) {
		if ($preventCaching) {
			Header("Cache-Control: private, post-check=1, pre-check=2, max-age=1, must-revalidate");
			Header("Expires: Mon, 12 Jul 2000 01:00:00 GMT");
		} else {
			# send an expire header claiming this content is at least valid for 10 years
			Header("Cache-Control: public");
			Header("Expires: " . gmdate("D, d M Y H:i:s", (time() + (86400 * 3650))) . " GMT");
			Header("Pragma: ");
		} # if
	} # sendExpireHeaders
	
	/*
	 * Send the correct content header and character set to the browser
	 */
	function sendContentTypeHeader($type) {
		switch($type) {
			case 'xml'		: Header("Content-Type: text/xml; charset=utf-8"); break;
			case 'rss'		: Header("Content-Type: application/rss+xml; charset=utf-8"); break;
			case 'json'		: Header("Content-Type: application/json; charset=utf-8"); break;
			case 'css'		: Header("Content-Type: text/css; charset=utf-8"); break;
			case 'js'		: Header("Content-Type: application/javascript; charset=utf-8"); break;
			case 'ico'		: Header("Content-Type: image/x-icon"); break;
            case 'nzb'		: Header("Content-Type: application/x-nzb"); break;

			default 		: Header("Content-Type: text/html; charset=utf-8"); break;
		} # switch
		
	} # sendContentTypeHeader


	/*
	 * Returns an TemplateHelper instance. Instantiates an
	 * dynamic class name which is ugly.
	 */	
	private function getTplHelper($params) {
		$tplName = $this->_currentSession['active_tpl'];

		$className = 'SpotTemplateHelper_' . ucfirst($tplName);
		$tplHelper = new $className($this->_settings, $this->_currentSession, $this->_daoFactory, $params);

		return $tplHelper;
	} # getTplHelper
		
	
	/*
	 * Actually run the templating code
	 */
	function template($tpl, $params = array()) {
        SpotTiming::start(__CLASS__ . '::' .__FUNCTION__ . ':' . $tpl);

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
			$path = sprintf('%s%s.inc.php', $tplPath, $tpl);
			if (file_exists($path)) {
				require_once $path;
				break;
			} # if
		} # foreach
		
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':' . $tpl, array($params));
	} # template
	
	/*
	 * Actually render the page, must be overridden by the specific implementation
	 */
	abstract function render();
	
	/*
	 * Render a permission denied page. Might be overridden by a page specific
	 * implementation to allow rendering of XML or other type of pages
	 */
	function permissionDenied($exception, $page, $http_referer) {
		$this->template('permdenied',
							array('exception' => $exception,
								  'page' => $page,
								  'http_referer' => $http_referer));
	} # permissionDenied
	
} # SpotPage_Abs
