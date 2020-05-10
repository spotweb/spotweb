<?php

abstract class SpotPage_Abs
{
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
     *
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

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;
        $this->_currentSession = $currentSession;

        $this->_spotSec = $currentSession['security'];
        $this->_tplHelper = $this->getTplHelper([]);

        /*
         * Create a list of paths where to look for template files in
         * the correct (last template first) order
         */
        $this->_templatePaths = ['templates/'.$currentSession['active_tpl'].'/'];
        foreach ($this->_tplHelper->getParentTemplates() as $parentTemplate) {
            $this->_templatePaths[] = 'templates/'.$parentTemplate.'/';
        } // foreach
    }

    // ctor

    /*
     * Send either 'do cache' or 'do not cache' headers to the client
     */
    public function sendExpireHeaders($preventCaching)
    {
        if ($preventCaching) {
            header('Cache-Control: private, post-check=1, pre-check=2, max-age=1, must-revalidate');
            header('Expires: Mon, 12 Jul 2000 01:00:00 GMT');
        } else {
            // send an expire header claiming this content is at least valid for 10 years
            header('Cache-Control: public');
            header('Expires: '.gmdate('D, d M Y H:i:s', (time() + (86400 * 3650))).' GMT');
            header('Pragma: ');
        } // if
    }

    // sendExpireHeaders

    /*
     * Send the correct content header and character set to the browser
     */
    public function sendContentTypeHeader($type)
    {
        switch ($type) {
            case 'xml': header('Content-Type: text/xml; charset=utf-8'); break;
            case 'rss': header('Content-Type: application/rss+xml; charset=utf-8'); break;
            case 'json': header('Content-Type: application/json; charset=utf-8'); break;
            case 'css': header('Content-Type: text/css; charset=utf-8'); break;
            case 'js': header('Content-Type: application/javascript; charset=utf-8'); break;
            case 'ico': header('Content-Type: image/x-icon'); break;
            case 'nzb': header('Content-Type: application/x-nzb'); break;

            default: header('Content-Type: text/html; charset=utf-8'); break;
        } // switch
    }

    // sendContentTypeHeader

    /*
     * Returns an TemplateHelper instance. Instantiates an
     * dynamic class name which is ugly.
     */
    private function getTplHelper($params)
    {
        $tplName = $this->_currentSession['active_tpl'];

        $className = 'SpotTemplateHelper_'.ucfirst($tplName);
        $tplHelper = new $className($this->_settings, $this->_currentSession, $this->_daoFactory, $params);

        return $tplHelper;
    }

    // getTplHelper

    /*
     * Actually run the templating code
     */
    public function template($tpl, $params = [])
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':'.$tpl);

        extract($params, EXTR_REFS);
        $settings = $this->_settings;
        $pagetitle = $this->_pageTitle;

        // update the template helper variables
        $this->_tplHelper->setParams($params);

        // Expose some variables to the template script in its local scope
        $tplHelper = $this->_tplHelper;
        $currentSession = $this->_currentSession;
        $spotSec = $this->_currentSession['security'];

        // send any expire headers
        $this->sendExpireHeaders(true);
        $this->sendContentTypeHeader('html');

        // and include the template
        foreach ($this->_templatePaths as $tplPath) {
            $path = sprintf('%s%s.inc.php', $tplPath, $tpl);
            if (file_exists($path)) {
                require_once $path;
                break;
            } // if
        } // foreach

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':'.$tpl, [$params]);
    }

    // template

    /*
     * Actually render the page, must be overridden by the specific implementation
     */
    abstract public function render();

    /*
     * Render a permission denied page. Might be overridden by a page specific
     * implementation to allow rendering of XML or other type of pages
     */
    public function permissionDenied($exception, $page, $http_referer)
    {
        $this->template(
            'permdenied',
            ['exception'       => $exception,
                'page'         => $page,
                'http_referer' => $http_referer, ]
        );
    }

    // permissionDenied
} // SpotPage_Abs
