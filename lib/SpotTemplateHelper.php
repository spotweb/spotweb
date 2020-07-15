<?php

// Utility class voor template functies, kan eventueel
// door custom templates extended worden
class SpotTemplateHelper
{
    protected $_settings;
    protected $_daoFactory;
    protected $_currentSession;
    protected $_params;
    protected $_nzbhandler;
    protected $_spotSec;
    protected $_svcCacheNewSpotCount = null;
    protected $_treeFilterCache = null;

    public function __construct(Services_Settings_Container $settings, $currentSession, Dao_Factory $daoFactory, $params)
    {
        $this->_settings = $settings;
        $this->_currentSession = $currentSession;
        $this->_daoFactory = $daoFactory;

        $this->_spotSec = $currentSession['security'];
        $this->_params = $params;

        // We initialiseren hier een NzbHandler object om te voorkomen
        // dat we voor iedere spot een nieuw object initialiseren, een property
        // zou mooier zijn, maar daar is PHP dan weer te traag voor
        $nzbHandlerFactory = new Services_NzbHandler_Factory();
        if (isset($currentSession['user']['prefs']['nzbhandling'])) {
            $this->_nzbHandler = $nzbHandlerFactory->build(
                $settings,
                $currentSession['user']['prefs']['nzbhandling']['action'],
                $currentSession['user']['prefs']['nzbhandling']
            );
        } // if
    }

    // ctor

    /*
     * Returns an array of parent template paths
     */
    public function getParentTemplates()
    {
        return [];
    }

    // getParentTemplates

    /*
     * Set params - update the template list of parameters
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    // setParams

    /*
     * Returns a paraemter value
     */
    public function getParam($name)
    {
        if (isset($this->_params[$name])) {
            return $this->_params[$name];
        } else {
            return null;
        } // if
    }

    // getParam

    /*
     * Returns te amount of spots (for a specific filter) which are new for this user
     */
    public function getNewCountForFilter($filterStr)
    {
        if ($this->_svcCacheNewSpotCount === null) {
            $this->_svcCacheNewSpotCount = new Services_Actions_CacheNewSpotCount(
                $this->_daoFactory->getUserFilterCountDao(),
                $this->_daoFactory->getUserFilterDao(),
                $this->_daoFactory->getSpotDao(),
                new Services_Search_QueryParser($this->_daoFactory->getConnection())
            );
        } // if

        return $this->_svcCacheNewSpotCount->getNewCountForFilter($this->_currentSession['user']['userid'], $filterStr);
    }

    // getNewCountForFilter

    /*
     * Return the actual comments for a specific spot
     */
    public function getSpotComments($msgId, $prevMsgids, $start, $length)
    {
        $language = substr($this->_currentSession['user']['prefs']['user_language'], 0, 2);

        $svcActnComments = new Services_Actions_GetComments($this->_settings, $this->_daoFactory, $this->_spotSec);

        return $svcActnComments->getSpotComments($msgId, $prevMsgids, $this->_currentSession['user']['userid'], $start, $length, $language);
    }

    // getSpotComments

    public function getFullSpot($messageId)
    {
        // and actually retrieve the spot
        $svcActn_GetSpot = new Services_Actions_GetSpot($this->_settings, $this->_daoFactory, $this->_spotSec);
        $fullSpot = $svcActn_GetSpot->getFullSpot($this->_currentSession, $messageId, true);

        return $fullSpot;
    }

    // getFullSpot

    /*
     * Validates wether we can connect to a usenet server succesfully
     */
    public function validateNntpServer($server)
    {
        $result = '';

        try {
            $testNntp = new Services_Nntp_Engine($server);
            $testNntp->validateServer();
        } // try
        catch (Exception $x) {
            $result = $x->getMessage();
        } // catch

        return $result;
    }

    // validateNntpServer

    /*
     * Thin wrapper around the permission allowed function
     */
    public function allowed($perm, $object)
    {
        return $this->_spotSec->allowed($perm, $object);
    }

    // allowed

    /*
     * Creeert een URL naar de zoekmachine zoals gedefinieerd in de settings
     */
    public function makeSearchUrl($spot)
    {
        $searchString = (empty($spot['filename'])) ? $spot['title'] : $spot['filename'];
        $searchString = urlencode($searchString);

        switch ($this->_currentSession['user']['prefs']['nzb_search_engine']) {
            case 'nzbindex': return 'https://nzbindex.nl/search/?q='.$searchString;
            case 'binsearch':
            default: return 'https://www.binsearch.info/?adv_age=&amp;q='.$searchString;
        } // switch
    }

    // makeSearchUrl

    /*
     * Geef het volledige URL of path naar Spotweb terug
     */
    public function makeBaseUrl($type)
    {
        switch ($type) {
            case 'path': return parse_url($this->_settings->get('spotweburl'), PHP_URL_PATH);
            default: return $this->_settings->get('spotweburl');
        } // switch
    }

    // makeBaseurl

    /*
     * Creeert een linkje naar de sabnzbd API zoals gedefinieerd in de
     * settings
     */
    public function makeSabnzbdUrl($spot)
    {
        $nzbHandling = $this->_currentSession['user']['prefs']['nzbhandling'];
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_download_integration, $nzbHandling['action'])) {
            return '';
        } // if

        return $this->_nzbHandler->generateNzbHandlerUrl($spot, $this->makeApiRequestString());
    }

    // makeSabnzbdUrl

    /*
     * Creeert een linkje naar een specifieke spot
     */
    public function makeSpotUrl($spot)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_view_spotdetail, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=getspot&amp;messageid='.urlencode($spot['messageid']);
    }

    // makeSpotUrl

    /*
     * Creates the url for editing an existing spot
     */
    public function makeEditSpotUrl($spot, $action)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_spotdetail, '')) {
            return '';
        }

        return $this->makeBaseUrl('path').'?page=editspot&amp;messageid='.urlencode($spot['messageid']).'&amp;action='.$action;
    }

    // makeEditSpotUrl

    /*
     * Creeert de action url voor het aanmaken van de user
     */
    public function makeCreateUserAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_create_new_user, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=createuser';
    }

    // makeCreateUserAction

    /*
     * Creeert de action url voor het aanmaken van een nieuwe spot
     */
    public function makePostSpotAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_post_spot, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=postspot';
    }

    // makePostSpotAction

    /*
     * Creates the action url for editing an existing spot (used in form post actions)
     */
    public function makeEditSpotAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_spotdetail, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=editspot';
    }

    // makeEditSpotAction

    /*
     * Creeert de action url voor het beweken van een security group
     */
    public function makeEditSecGroupAction()
    {
        return $this->makeBaseUrl('path').'?page=editsecgroup';
    }

    // makeEditSecGroupAction

    /*
     * Creates the URL action for editing a blacklist
     */
    public function makeEditBlacklistAction()
    {
        return $this->makeBaseUrl('path').'?page=blacklistspotter';
    }

    // makeEditBlacklistAction

    /*
     * Creeert de action url voor het wijzigen van een filter
     */
    public function makeEditFilterAction()
    {
        return $this->makeBaseUrl('path').'?page=editfilter';
    }

    // makeEditFilterAction

    /*
     * Creeert de action url voor het wissen van een filter
     */
    public function makeDeleteFilterAction()
    {
        return $this->makeBaseUrl('path').'?page=editfilter';
    }

    // makeDeleteFilterAction

    /*
     * Creeert de action url voor het wijzigen van de user (gebruikt in form post actions)
     */
    public function makeEditUserAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_user, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=edituser';
    }

    // makeEditUserAction

    /*
     * Creeert de action url voor het wijzigen van de instellingen (gebruikt in form post actions)
     */
    public function makeEditSettingsAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_settings, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=editsettings';
    }

    // makeEditSettingsAction

    /*
     * Creeert de action url voor het wijzigen van de users' preferences (gebruikt in form post actions)
     */
    public function makeEditUserPrefsAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=edituserprefs';
    }

    // makeEditUserPrefsAction

    /*
     * Creeert de url voor het bewerken van een bestaande user
     */
    public function makeEditUserUrl($userid, $action)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_user, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=edituser&amp;userid='.((int) $userid).'&amp;action='.$action;
    }

    // makeEditUserUrl

    /*
     * Creeert de url voor het bewerken van een bestaande users' preferences
     */
    public function makeEditUserPrefsUrl($userid)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=edituserprefs&amp;userid='.((int) $userid);
    }

    // makeEditUserPrefsUrl

    /*
     * Creeert de action url voor het inloggen van een user
     */
    public function makeLoginAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_perform_login, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=login&data[htmlheaderssent]=true';
    }

    // makeLoginAction

    /*
     * Creeert de action url voor het inloggen van een user
     */
    public function makePostCommentAction()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_post_comment, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('path').'?page=postcomment';
    }

    // makePostCommentAction

    /*
     * Creeert de action url voor het spam reporten van een spot
     */
    public function makeReportAction()
    {
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_report_spam, '')) {
            return '';
        }

        return $this->makeBaseUrl('path').'?page=reportpost';
    }

    // makeReportAction

    /*
     * Creeert de action url voor het blacklisten van een spotter
     */
    public function makeListAction()
    {
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_blacklist_spotter, '')) {
            return '';
        }

        return $this->makeBaseUrl('path').'?page=blacklistspotter';
    }

    // makeListAction

    /*
     * Only allow a specific set of users to create customized content
     */
    public function allowedToPost()
    {
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        return $svcUserRecord->allowedToPost($this->_currentSession['user']);
    }

    // allowedToPost

    /*
     * Creeert een linkje naar een specifieke nzb
     */
    public function makeNzbUrl($spot)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_retrieve_nzb, '')) {
            return '';
        } // if

        return $this->makeBaseUrl('full').'?page=getnzb&amp;action=display&amp;messageid='.urlencode($spot['messageid']).$this->makeApiRequestString();
    }

    // makeNzbUrl

    /*
     * Creeert een linkje naar retrieve.php
     */
    public function makeRetrieveUrl()
    {
        // Controleer de users' rechten
        if ((!$this->_spotSec->allowed(SpotSecurity::spotsec_retrieve_spots, '')) || (!$this->_spotSec->allowed(SpotSecurity::spotsec_consume_api, ''))) {
            return '';
        } // if

        return $this->makeBaseUrl('full').'retrieve.php?output=xml'.$this->makeApiRequestString();
    }

    // makeRetrieveUrl

    /*
     * Geef het pad op naar de image
     */
    public function makeImageUrl($spot, $height, $width)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_view_spotimage, '')) {
            return '';
        } // if

        // Volgens issue 941 wil men soms vanuit de RSS of Newznab feed rechtstreeks
        // images kunnen laden. We checken of het 'getimage' recht rechtstreeks via de
        // API aan te roepen is, en zo ja, creeren we API urls.
        $apiKey = '';
        if ($this->_spotSec->allowed(SpotSecurity::spotsec_consume_api, 'getimage')) {
            $apiKey = $this->makeApiRequestString();
        } // if

        return $this->makeBaseUrl('path').'?page=getimage&amp;messageid='.urlencode($spot['messageid']).'&amp;image[height]='.$height.'&amp;image[width]='.$width.$apiKey;
    }

    // makeImageUrl

    /*
     * Creert een sorteer url
     */
    public function makeSortUrl($page, $sortby, $sortdir)
    {
        return $this->makeBaseUrl('path').'?page='.$page.$this->convertFilterToQueryParams().'&amp;sortby='.$sortby.'&amp;sortdir='.$sortdir;
    }

    // makeSortUrl

    /*
     * Creert een gravatar url
     */
    public function makeCommenterImageUrl($fullComment)
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_view_spotimage, 'avatar')) {
            return '';
        } // if

        if (!empty($fullComment['user-avatar'])) {
            // Return the image as inline base64 encoded data
            return 'data:image/png;base64,'.$fullComment['user-avatar'];
        } else {
            $md5 = md5(base64_decode($fullComment['user-key']['modulo']));

            return $this->makeBaseUrl('path').'?page=getimage&amp;image[type]=avatar&amp;image[size]=32&amp;image[md5]='.urlencode($md5);
        } // else
    }

    // makeCommenterImageUrl

    /*
     * Creert een sorteer url die andersom sorteert
     * dan de huidige sortering
     */
    public function makeToggleSortUrl($page, $sortby, $sortdir)
    {
        $curSort = $this->getActiveSorting();

        /**
         * If we are currently sorting on the same field, make
         * sure we are reversing direction from the current sort.
         */
        if ($curSort['friendlyname'] == $sortby) {
            if ($curSort['direction'] == 'ASC') {
                $sortdir = 'DESC';
            } else {
                $sortdir = 'ASC';
            } // else
        } // if

        return $this->makeBaseUrl('path').'?page='.$page.$this->convertFilterToQueryParams().'&amp;sortby='.$sortby.'&amp;sortdir='.$sortdir;
    }

    // makeToggleSortUrl

    /*
     * Creert een category url
     */
    public function makeCatUrl($spot)
    {
        // subcata mag altijd maar 1 category hebben, dus exploden we niet
        $catSpot = substr($spot['subcata'], 0, -1);

        return $this->makeBaseUrl('path').'?search[tree]=cat'.$spot['category'].'_'.$catSpot.'&amp;sortby=stamp&amp;sortdir=DESC';
    }

    // makeCatUrl

    /*
     * Creert een subcategory url
     */
    public function makeSubCatUrl($spot, $cat)
    {
        $catSpot = explode('|', $cat);

        /* Format the subcatz url */
        $subcatzStr = $spot['subcatz'];
        if (!empty($subcatzStr)) {
            $subcatzStr = '_z'.$subcatzStr[1];
        } // if

        return $this->makeBaseUrl('path').'?search[tree]=cat'.$spot['category'].$subcatzStr.'_'.$catSpot[0].'&amp;sortby=stamp&amp;sortdir=DESC';
    }

    // makeSubCatUrl

    /*
     * Creert een Poster url
     */
    public function makePosterUrl($spot)
    {
        return $this->makeBaseUrl('path').'?search[tree]=&amp;search[value][]=Poster:=:'.urlencode($spot['poster']).'&amp;sortby=stamp&amp;sortdir=DESC';
    }

    // makePosterUrl

    /*
     * Creeert een linkje naar een zoekopdracht op spotterid
     */
    public function makeSpotterIdUrl($spot)
    {
        return $this->makeBaseUrl('path').'?search[tree]=&amp;search[value][]=SpotterID:=:'.urlencode($spot['spotterid']).'&amp;sortby=stamp&amp;sortdir=DESC';
    }

    // makeSpotterIdUrl

    /*
     * Creeert een linkje naar een zoekopdracht op tag
     */
    public function makeTagUrl($spot)
    {
        return $this->makeBaseUrl('path').'?search[tree]=&amp;search[value][]=Tag:=:'.urlencode($spot['tag']);
    }

    // makeTagUrl

    /*
     * Creeert een request string met username en apikey als deze zijn opgegeven
     */
    public function makeApiRequestString()
    {
        // Controleer de users' rechten
        if (!$this->_spotSec->allowed(SpotSecurity::spotsec_consume_api, '')) {
            return '';
        } // if

        if ($this->_currentSession['user']['userid'] > SPOTWEB_ADMIN_USERID) {
            return '&amp;apikey='.$this->_currentSession['user']['apikey'];
        } else {
            return '';
        } // else
    }

    // makeApiRequestString

    /*
     * Creert een RSS url
     */
    public function makeRssUrl()
    {
        if (isset($this->_params['parsedsearch'])) {
            return $this->makeBaseUrl('path').'?page=rss&amp;'.$this->convertFilterToQueryParams().'&amp;'.$this->convertSortToQueryParams();
        } else {
            return '';
        } // if
    }

    // makeRssUrl

    /*
     * Creert een basis navigatie pagina
     */
    public function getPageUrl($page)
    {
        return $this->makeBaseUrl('path').'?page='.$page;
    }

    // getPageUrl

    /*
     * Geeft het linkje terug naar ons zelf
     */
    public function makeSelfUrl($type)
    {
        return $this->makeBaseUrl($type).htmlspecialchars((isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : ''));
    }

    // makeSelfUrl

    // Function from http://www.php.net/manual/en/function.filesize.php#99333
    public function format_size($size)
    {
        $sizes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        if ($size == 0) {
            return 'n/a';
        } else {
            return round($size / pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0).$sizes[$i];

            // test (n.a.v. http://gathering.tweakers.net/forum/list_message/36208481#36208481) om altijd op
            // 3 getallen te eindigen, maar maakt het niet rustiger.
            //
            //		$roundedSize = round($size/pow(1024, ($i = floor(log($size, 1024)))),99);
            //		return number_format($roundedSize, 3 - strlen(round($roundedSize))) . $sizes[$i];
        } // else
    }

    // format_size

    public function formatContent($tmp)
    {
        // escape alle embedded HTML, maar eerst zetten we de spot inhoud om naar
        // volledige HTML, dit doen we omdat er soms embedded entities (&#237; e.d.)
        // in zitten welke we wel willen behouden.
        $tmp = html_entity_decode($tmp, ENT_COMPAT, 'UTF-8');
        $tmp = htmlentities($tmp, ENT_COMPAT, 'UTF-8');

        // Code gecopieerd vanaf
        //		http://stackoverflow.com/questions/635844/php-how-to-grab-an-url-out-of-a-chunk-of-text
        // converteert linkjes naar bb code
        $pattern = "(([^=])((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\))+[\w\d:#@%/;$()~_?\+-=\\\.&]*))";
        $tmp = preg_replace($pattern, '\1[url=\2]\2[/url]', $tmp);

        // initialize ubb parser
        $parser = new SpotUbb_parser($tmp);
        TagHandler::setDeniedTags([]);
        TagHandler::setadditionalinfo('img', 'allowedimgs', $this->getSmileyList());
        $tmp = $parser->parse();
        $tmp = $tmp[0];

        // en replace eventuele misvormde br tags
        $tmp = str_ireplace('&lt;br&gt;', '<br />', $tmp);
        $tmp = str_ireplace('&lt;br /&gt;', '<br />', $tmp);
        $tmp = str_ireplace('&amp;lt;br />', '<br />', $tmp);

        return $tmp;
    }

    // formatContent

    /*
     * Geeft de huidige lijst met categoryselectie terug
     * als een comma seperated lijst voor de dynatree initialisatie
     */
    public function categoryListToDynatree()
    {
        $svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());

        return $svcSearchQp->compressCategorySelection($this->_params['parsedsearch']['categoryList'], $this->_params['parsedsearch']['strongNotList']);
    }

    // categoryListToDynatree

    /*
     * Converteert de aanwezige filters naar een nieuwe, eventueel
     * geoptimaliserde GET (query) parameters
     */
    public function convertFilterToQueryParams()
    {
        //var_dump($this->_params['parsedsearch']['categoryList']);
        //var_dump($this->_params['parsedsearch']['strongNotList']);
        //var_dump($this->_params['parsedsearch']['filterValueList']);
        //var_dump($this->_params['parsedsearch']['sortFields']);

        return $this->convertUnfilteredToQueryParams().$this->convertTreeFilterToQueryParams().$this->convertTextFilterToQueryParams();
    }

    // convertFilterToQueryParams

    /*
     * Converteer de huidige unfiltered setting
     * naar een nieuwe GET query
     */
    public function convertUnfilteredToQueryParams()
    {
        if (!isset($this->_params['parsedsearch']['unfiltered'])) {
            return '';
        } // if

        // en eventueel als de huidige list unfiltered is, geef
        // dat ook mee
        $unfilteredStr = '';
        if ($this->_params['parsedsearch']['unfiltered']) {
            $unfilteredStr = '&amp;search[unfiltered]=true';
        } // if

        return $unfilteredStr;
    }

    // convertUnfilteredToQueryParams()

    /*
     * Converteert de aanwezige filter boom naar een
     * nieuwe GET query
     */
    public function convertTreeFilterToQueryParams()
    {
        if (!isset($this->_params['parsedsearch']['categoryList'])) {
            return '';
        } // if

        if ($this->_treeFilterCache !== null) {
            return $this->_treeFilterCache;
        } // if

        // Rebuild the category tree
        $svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());
        $this->_treeFilterCache = '&amp;search[tree]='.urlencode($svcSearchQp->compressCategorySelection(
            $this->_params['parsedsearch']['categoryList'],
            $this->_params['parsedsearch']['strongNotList']
        ));

        return $this->_treeFilterCache;
    }

    // convertTreeFilterToQueryParams

    /*
     * Converteert de aanwezige filter velden (behalve de boom)
     * naar een nieuwe GET query
     */
    public function convertTextFilterToQueryParams()
    {
        if (!isset($this->_params['parsedsearch']['filterValueList'])) {
            return '';
        } // if

        // Vervolgens bouwen we de filtervalues op
        $filterStr = '';
        foreach ($this->_params['parsedsearch']['filterValueList'] as $value) {
            $filterStr .= '&amp;search[value][]='.urlencode($value['fieldname']).':'.urlencode($value['operator']).':'.htmlspecialchars(urlencode($value['value']), ENT_QUOTES, 'utf-8');
        } // foreach

        return $filterStr;
    }

    // convertTextFilterToQueryParams

    /*
     * Geeft de huidige actieve sortering terug
     */
    public function getActiveSorting()
    {
        $activeSort = ['field' => '',
            'direction'        => '',
            'friendlyname'     => '', ];

        // als we niet aan het sorteren zijn, doen we niets
        if (!isset($this->_params['parsedsearch'])) {
            return $activeSort;
        } // if

        // we voegen alleen sorteringen toe die ook door
        // de gebruiker expliciet zijn toegevoegd
        foreach ($this->_params['parsedsearch']['sortFields'] as $value) {
            if (!$value['autoadded']) {
                $activeSort['field'] = $value['field'];
                $activeSort['direction'] = $value['direction'];
                $activeSort['friendlyname'] = $value['friendlyname'];
                break;
            } // if
        } // foreach

        return $activeSort;
    }

    // getActiveSorting

    /*
     * Converteert de huidige actieve sorteer parameters
     * naar GET parameters voor in de URL
     */
    public function convertSortToQueryParams()
    {
        $activeSort = $this->getActiveSorting();

        if (!empty($activeSort['field'])) {
            return '&amp;sortby='.urlencode($activeSort['friendlyname']).'&amp;sortdir='.urlencode($activeSort['direction']);
        } // if

        return '';
    }

    // convertSortToQueryParams

    /*
     * Safely escape de velden en vul wat velden in
     */
    public function formatSpotHeader($spot)
    {
        /*
            $spot['sabnzbdurl'] = '';
            $spot['searchurl'] = '';
            $spot['spoturl'] = '';
            $spot['caturl'] = '';
            $spot['subcaturl'] = '';
            $spot['posterurl'] = '';
            $spot['title'] = '';
            $spot['poster'] = '';
            $spot['catshortdesc'] = '';
            $spot['catdesc'] = '';
            $spot['hasbeendownloaded'] = ($spot['downloadstamp'] != NULL);
            $spot['isbeingwatched'] = ($spot['watchstamp'] != NULL);
            return $spot;
        */

        // fix the sabnzbdurl, searchurl, sporturl, subcaturl, posterurl
        $spot['sabnzbdurl'] = $this->makeSabnzbdUrl($spot);
        $spot['nzbhandlertype'] = $this->_currentSession['user']['prefs']['nzbhandling']['action'];
        $spot['searchurl'] = $this->makeSearchUrl($spot);
        $spot['spoturl'] = $this->makeSpotUrl($spot);
        $spot['caturl'] = $this->makeCatUrl($spot);
        $spot['subcaturl'] = $this->makeSubCatUrl($spot, $spot['subcat'.SpotCategories::SubcatNumberFromHeadcat($spot['category'])]);
        $spot['posterurl'] = $this->makePosterUrl($spot);

        // title escapen !!
        $spot['title'] = html_entity_decode($spot['title'], ENT_QUOTES, 'UTF-8');
        $spot['title'] = htmlentities($spot['title'], ENT_COMPAT, 'UTF-8');
        $spot['title'] = strip_tags($this->remove_extensive_dots($spot['title']));
        $spot['poster'] = htmlspecialchars(strip_tags($spot['poster']), ENT_QUOTES, 'UTF-8');

        // we zetten de short description van de category bij
        $spot['catshortdesc'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
        $spot['catdesc'] = SpotCategories::Cat2Desc($spot['category'], $spot['subcat'.SpotCategories::SubcatNumberFromHeadcat($spot['category'])]);

        // commentcount en rating altijd teruggeven
        $spot['commentcount'] = (int) $spot['commentcount'];
        $spot['rating'] = (int) $spot['rating'];

        // is deze spot al eens gedownload?
        $spot['hasbeendownloaded'] = ($spot['downloadstamp'] != null);

        // is deze spot al eens bekeken?
        $spot['hasbeenseen'] = ($spot['seenstamp'] != null);

        // zit deze spot in de watchlist?
        $spot['isbeingwatched'] = ($spot['watchstamp'] != null);

        return $spot;
    }

    // formatSpotHeader

    /*
     * Formatteert (maakt op) een lijst van comments
     */
    public function formatComments($comments)
    {
        // escape de HTML voor de comments
        $commentCount = count($comments);
        for ($i = 0; $i < $commentCount; $i++) {
            $comments[$i]['fromhdr'] = htmlentities($comments[$i]['fromhdr'], ENT_NOQUOTES, 'UTF-8');
            // verwijder AVG commentaar
            $q = strstr($comments[$i]['body'], '-- '.PHP_EOL, true);
            if ($q != false) {
                $comments[$i]['body'] = $q;
            }
            // we joinen eerst de contents zodat we het kunnen parsen als 1 string
            // en tags over meerdere lijnen toch nog werkt. We voegen een extra \n toe
            // om zeker te zijn dat we altijd een array terugkrijgen
            $comments[$i]['body'] = $this->formatContent($comments[$i]['body']);
        } // for

        return $comments;
    }

    // formatComments

    /*
     * Returns a list of nntprefs' with new commentcounts
     */
    public function getNewCommentCountFor($spotList)
    {
        // Prepare the spotlisting with an list of nntp items
        $nntpRefList = [];
        foreach ($spotList as $spot) {
            $nntpRefList['messageid'] = $spot['messageid'];
        } // foreach

        return $this->_daoFactory->getCommentDao()->getNewCommentCountFor($nntpRefList, $this->_currentSession['user']['lastvisit']);
    }

    // getNewCommentCountFor

    /*
     * Omdat we geen zin hebben elke variabele te controleren of hij bestaat,
     * vullen we een aantal defaults in.
     */
    public function formatSpot($spot)
    {
        // formatteer de spot
        $spot = $this->formatSpotHeader($spot);

        // Category is altijd een integer bij ons
        $spot['category'] = (int) $spot['category'];

        // Geen website? Dan standaard naar de zoekmachine
        if (empty($spot['website'])) {
            $spot['website'] = $this->makeSearchUrl($spot);
        } else {
            $spot['website'] = htmlspecialchars($spot['website']);
        } // else

        // geef de category een fatsoenlijke naam
        $spot['catname'] = SpotCategories::HeadCat2Desc($spot['category']);
        $spot['formatname'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);

        // properly escape several  urls
        if (!is_array($spot['image'])) {
            $spot['image'] = htmlspecialchars($spot['image']);
        } // if
        $spot['tag'] = htmlspecialchars(strip_tags($spot['tag']), ENT_QUOTES, 'UTF-8');

        // description
        $spot['description'] = $this->formatContent($spot['description']);

        return $spot;
    }

    // formatSpot

    public function isSpotNew($spot)
    {
        return  $this->_currentSession['user']['lastread'] < $spot['stamp'] && $spot['seenstamp'] == null;
    }

    // isSpotNew

    //
    // Copied from:
    // 	http://www.mdj.us/web-development/php-programming/another-variation-on-the-time-ago-php-function-use-mysqls-datetime-field-type/
    // DISPLAYS COMMENT POST TIME AS "1 year, 1 week ago" or "5 minutes, 7 seconds ago", etc...
    public function time_ago($date, $granularity = 2)
    {
        $difference = time() - $date;
        $periods = ['decade' => 315360000,
            'year'           => 31536000,
            'month'          => 2628000,
            'week'           => 604800,
            'day'            => 86400,
            'hour'           => 3600,
            'minute'         => 60,
            'second'         => 1, ];

        $retval = '';
        foreach ($periods as $key => $value) {
            if ($difference >= $value) {
                $time = floor($difference / $value);
                $difference %= $value;
                $retval .= ($retval ? ' ' : '').$time.' ';
                $retval .= ngettext($key, $key.'s', $time);
                $retval .= ', ';
                $granularity--;
            } // if

            if ($granularity == '0') {
                break;
            }
        }

        return substr($retval, 0, -2);
    }

    // time_ago()

    public function formatDate($stamp, $type)
    {
        if (empty($stamp)) {
            return _('unknown');
        } elseif (substr($type, 0, 6) == 'force_') {
            return strftime('%a, %d-%b-%Y (%H:%M)', $stamp);
        } elseif ($this->_currentSession['user']['prefs']['date_formatting'] == 'human') {
            return $this->time_ago($stamp);
        } else {
            switch ($type) {
                case 'comment':
                case 'spotlist':
                case 'lastupdate':
                case 'lastvisit':
                case 'userlist':
                default: return strftime($this->_currentSession['user']['prefs']['date_formatting'], $stamp);
            } // switch
        } // else
    }

    // formatDate

    public function isModerated($spot)
    {
        return $spot['moderated'] != 0;
    }

    // isModerated

    /*
     * Geeft een lijst van mogelijke smilies terug
     */
    public function getSmileyList()
    {
        return [];
    }

    // getSmileyList

    // Functie voor in combinatie met SpotPage_statics.php -
    // deze functie hoort een lijst van onze static files terug te geven die door de SpotPage_statics
    // dan geserved wordt als nooit meer veranderend.
    public function getStaticFiles($type)
    {
        return [];
    }

    // getStaticFiles

    // Functie voor in combinatie met SpotPage_statics.php -
    // deze functie kijkt wat de laatste timetsamp is van de file en kan gebruikt worden in de templates.
    // Omdat stat() behoorlijk traag is, is het voor betere performance aan te raden handmatig je versie nummer
    // op te hogen in je template en deze functie niet te gebruiken
    public function getStaticModTime($type)
    {
        $fileTime = 0;
        $fileList = $this->getStaticFiles($type);

        foreach ($fileList as $file) {
            $thisftime = filemtime($file);

            if ($thisftime > $fileTime) {
                $fileTime = $thisftime;
            } // if
        } // foreach

        return $fileTime;
    }

    // getStaticFiles

    public function remove_extensive_dots($s)
    {
        if (substr_count($s, '.') > 3) {
            $s = str_replace('.', ' ', $s);
        } // if

        return $s;
    }

    // remove_extensive_dots

    /*
     * Creeer een anti-XSRF cookie
     */
    public function generateXsrfCookie($action)
    {
        return SpotReq::generateXsrfCookie($action);
    }

    // generateXsrfCookie

    /*
     * API to hash
     */
    public function apiToHash($api)
    {
        return sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)).$api.$this->_settings->get('pass_salt'));
    }

    // apiToHash

    /*
     * Geeft de lijst met users terug
     */
    public function getUserList()
    {
        // Check users' permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_list_all_users, '');

        return $this->_daoFactory->getUserDao()->getUserListForDisplay();
    }

    // getUserList

    /*
     * Returns the list of all spotters on the users' blacklist
     */
    public function getSpotterList()
    {
        // Controleer de users' rechten
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_blacklist_spotter, '');

        return $this->_daoFactory->getBlackWhiteListDao()->getSpotterList($this->_currentSession['user']['userid']);
    }

    // getSpotterList

    /*
     * Returns the specific blacklist record for one spotterid
     */
    public function getBlacklistForSpotterId($spotterId)
    {
        // Controleer de users' rechten
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_blacklist_spotter, '');

        return $this->_daoFactory->getBlackWhiteListDao()->getBlacklistForSpotterId($this->_currentSession['user']['userid'], $spotterId);
    }

    // getBlacklistForSpotterId

    /*
     * Wanneer was de spotindex voor het laatst geupdate?
     */
    public function getLastSpotUpdates()
    {
        return $this->_daoFactory->getUsenetStateDao()->getLastUpdate(Dao_UsenetState::State_Spots);
    }

    // getLastSpotUpdates

    /*
     * Converteert een permission id naar een string
     */
    public function permToString($perm)
    {
        return $this->_spotSec->toHuman($perm);
    }

    // permToString

    /*
     * Geeft alle mogelijke Spotweb permissies terug
     */
    public function getAllAvailablePerms()
    {
        return $this->_spotSec->getAllPermissions();
    }

    // getAllAvailablePerms

    /*
     * Geeft een lijst met alle security groepen terug
     */
    public function getGroupList()
    {
        // Controleer de users' rechten
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_display_groupmembership, '');

        return $this->_daoFactory->getUserDao()->getGroupList(null);
    }

    // getGroupList

    /*
     * Geeft een lijst met alle security groepen terug voor een bepaalde user
     */
    public function getGroupListForUser($userId)
    {
        // Controleer de users' rechten
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_display_groupmembership, '');

        return $this->_daoFactory->getUserDao()->getGroupList($userId);
    }

    // getGroupListForUser

    /*
     * Geeft de users' custom CSS terug
     */
    public function getUserCustomCss()
    {
        if (!$this->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) {
            return '';
        } // if

        return $this->_currentSession['user']['prefs']['customcss'];
    }

    // if

    /*
     * Geeft alle permissies in een bepaalde securitygroup terug
     */
    public function getSecGroup($groupId)
    {
        // Controleer de users' rechten
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');

        $tmpGroup = $this->_daoFactory->getUserDao()->getSecurityGroup($groupId);
        if (!empty($tmpGroup)) {
            return $tmpGroup[0];
        } else {
            return false;
        } // else
    }

    // getSecGroup

    /*
     * Geeft alle permissies in een bepaalde securitygroup terug
     */
    public function getSecGroupPerms($id)
    {
        // Controleer de users' rechten
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');
        $permList = $this->_daoFactory->getUserDao()->getGroupPerms($id);
        for ($i = 0; $i < count($permList); $i++) {
            $permList[$i]['permissionname'] = _($this->permToString($permList[$i]['permissionid']));
        }
        usort($permList, 'SpotTemplateHelper::comparePermissionName');

        return $permList;
    }

    // getSecGroupPerms

    /*
     * Callback function for usort
     */
    public static function comparePermissionName($a, $b)
    {
        $retval = strnatcmp($a['permissionname'], $b['permissionname']);
        if (!$retval) {
            return strnatcmp($a['objectid'], $b['objectid']);
        }

        return $retval;
    }

    /*
     * Redirect naar een opgegeven url
     */
    public function redirect($url)
    {
        header('Location: '.$url);
    }

    // redirect()

    /*
     * Get users' filter list
     */
    public function getUserFilterList()
    {
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);

        return $svcUserFilter->getFilterList($this->_currentSession['user']['userid'], 'filter');
    }

    // getUserFilterList

    /*
     * Get specific filter
     */
    public function getUserFilter($filterId)
    {
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);

        return $svcUserFilter->getFilter($this->_currentSession['user']['userid'], $filterId);
    }

    // getUserFilter

    /*
     * Get index filter
     */
    public function getIndexFilter()
    {
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);

        return $svcUserFilter->getIndexFilter($this->_currentSession['user']['userid']);
    }

    // getIndexFilter

    /*
     * Controleer  of de user al een report heeft aangemaakt
     */
    public function isReportPlaced($messageId)
    {
        return $this->_daoFactory->getSpotReportDao()->isReportPlaced($messageId, $this->_currentSession['user']['userid']);
    }

    // isReportPlaced

    /*
     * Genereert een random string
     */
    public function getCleanRandomString($len)
    {
        $spotParseUtil = new Services_Format_Util();
        $spotSigning = Services_Signing_Base::factory();

        return substr($spotParseUtil->spotPrepareBase64(base64_encode($spotSigning->makeRandomStr($len))), 0, $len);
    }

    // getRandomStr

    /*
     * Geeft de naam van de NzbHandler terug
     */
    public function getNzbHandlerType()
    {
        return $this->_currentSession['user']['prefs']['nzbhandling']['action'];
    }

    // getNzbHandlerType

    /*
     * Returns name of nzbhandler action
     */
    public function getNzbHandlerName()
    {
        return $this->_nzbHandler->getName();
    }

    // getNzbHandlerName

    /*
     * Geeft een string met gesupporte API functies terug of false wanneer er geen API support is
     * voor de geselecteerde NzbHandler
     */
    public function getNzbHandlerApiSupport()
    {
        return $this->_nzbHandler->hasApiSupport();
    }

    // getNzbHandlerApiSupport

    /*
     * Geeft een array met valide statistics graphs terug
     */
    public function getValidStatisticsGraphs()
    {
        $svcPrv_Stats = new Services_Providers_Statistics(
            $this->_daoFactory->getSpotDao(),
            $this->_daoFactory->/** @scrutinizer ignore-call */getCacheDao($this->_settings->get('cache_path')),
            0
        );

        return $svcPrv_Stats->getValidStatisticsGraphs();
    }

    // getValidStatisticsGraphs

    /*
     * Geeft een array met valide statistics limits terug
     */
    public function getValidStatisticsLimits()
    {
        $svcPrv_Stats = new Services_Providers_Statistics(
            $this->_daoFactory->getSpotDao(),
            $this->_daoFactory->getCacheDao(),
            0
        );

        return $svcPrv_Stats->getValidStatisticsLimits();
    }

    // getValidStatisticsGraphs

    /*
     * Returns an array with configured languages for this system
     */
    public function getConfiguredLanguages()
    {
        return $this->_settings->get('system_languages');
    }

    // getConfiguredLanguages

    /*
     * Returns an array with configured templates for this system
     */
    public function getConfiguredTemplates()
    {
        return $this->_settings->get('valid_templates');
    }

    // getConfiguredTemplates

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
    public function getTemplatePreferences()
    {
        return [];
    }

    // getTemplatePreferences
} // class SpotTemplateHelper
