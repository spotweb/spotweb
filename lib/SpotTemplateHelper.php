<?php
# Utility class voor template functies, kan eventueel 
# door custom templates extended worden
class SpotTemplateHelper {	
	protected $_settings;
	protected $_db;
	protected $_spotsOverview;
	protected $_currentSession;
	protected $_params;
	protected $_nzbhandler;
	protected $_spotSec;
	protected $_cachedSpotCount = null;
	
	
	function __construct(SpotSettings $settings, $currentSession, SpotDb $db, $params) {
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
		$this->_db = $db;
		$this->_params = $params;
		
		# We hebben SpotsOverview altijd nodig omdat we die ook voor het
		# maken van de sorturl nodig hebben, dus maken we deze hier aan
		$this->_spotsOverview = new SpotsOverview($db, $settings);

		# We initialiseren hier een NzbHandler object om te voorkomen
		# dat we voor iedere spot een nieuw object initialiseren, een property
		# zou mooier zijn, maar daar is PHP dan weer te traag voor
		$nzbHandlerFactory = new NzbHandler_Factory();
		if (isset($currentSession['user']['prefs']['nzbhandling'])) {
			$this->_nzbHandler = $nzbHandlerFactory->build($settings, 
						$currentSession['user']['prefs']['nzbhandling']['action'], 
						$currentSession['user']['prefs']['nzbhandling']);
		} # if
	} # ctor

	/*
	 * Returns an array of parent template paths
	 */
	function getParentTemplates() {
		return array();
	} // getParentTemplates

	/*
	 * Set params - update de template list of parameters
	 */
	function setParams($params) {
		$this->_params = $params;
	} # setParams
	
	/* 
	 * Returns a paraemter value
	 */
	function getParam($name) {
		if (isset($this->_params[$name])) {
			return $this->_params[$name];
		} else {
			return NULL;
		} # if
	} # getParam
	

	/*
	 * Returns te amount of spots (for a specific filter) which are new for this user
	 */
	function getNewCountForFilter($filterStr) {
		/*
		 * If necessary, fill the cache 
		 */
		if ($this->_cachedSpotCount == null) {
			$this->_cachedSpotCount = $this->_db->getNewCountForFilters($this->_currentSession['user']['userid']);
		 } # if

		# Now parse it to an array as we would get when called from a webpage
		parse_str(html_entity_decode($filterStr), $query_params);
		$query_params['search']['valuelist'] = implode('&', $query_params['search']['value']);

		# Make sure we have a tree variable, even if it is an empty one
		if (!isset($query_params['search']['tree'])) {
			$query_params['search']['tree'] = '';
		} # if
		 
		$filterHash = sha1($query_params['search']['tree'] . '|' . urldecode($query_params['search']['valuelist']));
		 
		if (isset($this->_cachedSpotCount[$filterHash])) {
			return $this->_cachedSpotCount[$filterHash]['newspotcount'];
		} else {
			return -1;
		 } # if
	} # getNewCountForFilter

	/*
	 * Rturn the actual comments for a specific spot
	 */
	function getSpotComments($msgId, $start, $length) {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_comments, '');

		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		
		return $this->_spotsOverview->getSpotComments($this->_currentSession['user']['userid'], $msgId, $spotnntp, $start, $length);
	} # getSpotComments

	/*
	 * Validates wether we can connect to a usenet server succesfully
	 */
	function validateNntpServer($server) {
		$result = '';
		
		try {
			$testNntp = new SpotNntp($server);
			$testNntp->validateServer();
		} # try
		catch(Exception $x) {
			$result = $x->getMessage();
		} # catch
		
		return $result;
	} # validateNntpServer
	 
	/* 
	 * Thin wrapper around the permission allowed function
	 */
	function allowed($perm, $object) {
		return $this->_spotSec->allowed($perm, $object);
	} # allowed
	
	/*
	 * Returns a spot in full including all the information we have available
	 */
	function getFullSpot($msgId, $markAsRead) {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');
		
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		
		$fullSpot = $this->_spotsOverview->getFullSpot($msgId, $this->_currentSession['user']['userid'], $spotnntp);

		# seen list
		if ($markAsRead) {
			if ($this->_spotSec->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) {
				if ($this->_currentSession['user']['prefs']['keep_seenlist']) {
					if ($fullSpot['seenstamp'] == NULL) {
						$this->_db->addToSpotStateList(SpotDb::spotstate_Seen, 
													$msgId, 
													$this->_currentSession['user']['userid']);
					} # if
				} # if
				
			} # if allowed
		} # if
		
		return $fullSpot;
	} # getFullSpot

	/*
	 * Creeert een URL naar de zoekmachine zoals gedefinieerd in de settings
	 */
	function makeSearchUrl($spot) {
		$searchString = (empty($spot['filename'])) ? $spot['title'] : $spot['filename'];
		
		switch ($this->_currentSession['user']['prefs']['nzb_search_engine']) {
			case 'nzbindex'	: return 'http://nzbindex.nl/search/?q=' . $searchString; break;
			case 'binsearch':
			default			: return 'http://www.binsearch.info/?adv_age=&amp;q=' . $searchString;
		} # switch
	} # makeSearchUrl
	
	/*
	 * Geef het volledige URL of path naar Spotweb terug
	 */
	function makeBaseUrl($type) {
		switch ($type) {
			case 'path'	: return parse_url($this->_settings->get('spotweburl'), PHP_URL_PATH); break;
			default		: return $this->_settings->get('spotweburl');
		} # switch
	} # makeBaseurl

	/*
	 * Creeert een linkje naar de sabnzbd API zoals gedefinieerd in de 
	 * settings
	 */
	function makeSabnzbdUrl($spot) {
		$nzbHandling = $this->_currentSession['user']['prefs']['nzbhandling'];
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_download_integration, $nzbHandling['action'])) {
			return '';
		} # if
		
		return $this->_nzbHandler->generateNzbHandlerUrl($spot, $this->makeApiRequestString());
	} # makeSabnzbdUrl

	/*
	 * Creeert een linkje naar een specifieke spot
	 */
	function makeSpotUrl($spot) {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_view_spotdetail, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']); 
	} # makeSpotUrl

	/*
	 * Creeert de action url voor het aanmaken van de user
	 */
	function makeCreateUserAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_create_new_user, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=createuser";
	} # makeCreateUserAction

	/*
	 * Creeert de action url voor het aanmaken van een nieuwe spot
	 */
	function makePostSpotAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_post_spot, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=postspot";
	} # makePostSpotAction
	
	/*
	 * Creeert de action url voor het beweken van een security group 
	 */
	function makeEditSecGroupAction() {
		return $this->makeBaseUrl("path") . "?page=editsecgroup";
	} # makeEditSecGroupAction

	/*
	 * Creates the URL action for editing a blacklist
	 */
	function makeEditBlacklistAction() {
		return $this->makeBaseUrl("path") . "?page=blacklistspotter";
	} # makeEditBlacklistAction	
	
	/*
	 * Creeert de action url voor het wijzigen van een filter
	 */
	function makeEditFilterAction() {
		return $this->makeBaseUrl("path") . "?page=editfilter";
	} # makeEditFilterAction

	/*
	 * Creeert de action url voor het wissen van een filter
	 */
	function makeDeleteFilterAction() {
		return $this->makeBaseUrl("path") . "?page=editfilter";
	} # makeDeleteFilterAction

	/*
	 * Creeert de action url voor het wijzigen van de user (gebruikt in form post actions)
	 */
	function makeEditUserAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_user, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=edituser";
	} # makeEditUserAction

	/*
	 * Creeert de action url voor het wijzigen van de instellingen (gebruikt in form post actions)
	 */
	function makeEditSettingsAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_settings, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=editsettings";
	} # makeEditSettingsAction

	/*
	 * Creeert de action url voor het wijzigen van de users' preferences (gebruikt in form post actions)
	 */
	function makeEditUserPrefsAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=edituserprefs";
	} # makeEditUserPrefsAction
	
	/*
	 * Creeert de url voor het bewerken van een bestaande user
	 */
	function makeEditUserUrl($userid, $action) {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_user, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=edituser&amp;userid=" . ((int) $userid) . '&amp;action=' . $action;
	} # makeEditUserUrl

	/*
	 * Creeert de url voor het bewerken van een bestaande users' preferences
	 */
	function makeEditUserPrefsUrl($userid) {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=edituserprefs&amp;userid=" . ((int) $userid);
	} # makeEditUserPrefsUrl

	/*
	 * Creeert de action url voor het inloggen van een user
	 */
	function makeLoginAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_perform_login, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=login&data[htmlheaderssent]=true";
	} # makeLoginAction

	/*
	 * Creeert de action url voor het inloggen van een user
	 */
	function makePostCommentAction() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_post_comment, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("path") . "?page=postcomment";
	} # makePostCommentAction
	
	/*
	 * Creeert de action url voor het spam reporten van een spot
	 */
	function makeReportAction() {
		if(!$this->_spotSec->allowed(SpotSecurity::spotsec_report_spam, '')) {
			return '';
		}
		
		return $this->makeBaseUrl("path") . "?page=reportpost";
	} # makeReportAction

	/*
	 * Creeert de action url voor het blacklisten van een spotter
	 */
	function makeListAction() {
		if(!$this->_spotSec->allowed(SpotSecurity::spotsec_blacklist_spotter, '')) {
			return '';
		}
		
		return $this->makeBaseUrl("path") . "?page=blacklistspotter";
	} # makeListAction
	
	/*
	 * Only allow a specific set of users to create customized content
	 */
	function allowedToPost() {
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		return $spotUser->allowedToPost($this->_currentSession['user']);	
	} # allowedToPost
	
	/*
	 * Creeert een linkje naar een specifieke nzb
	 */
	function makeNzbUrl($spot) {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_retrieve_nzb, '')) {
			return '';
		} # if
		
		return $this->makeBaseUrl("full") . '?page=getnzb&amp;action=display&amp;messageid=' . urlencode($spot['messageid']) . $this->makeApiRequestString();
	} # makeNzbUrl

	/*
	 * Creeert een linkje naar retrieve.php
	 */
	function makeRetrieveUrl() {
		# Controleer de users' rechten
		if ((!$this->_spotSec->allowed(SpotSecurity::spotsec_retrieve_spots, '')) || (!$this->_spotSec->allowed(SpotSecurity::spotsec_consume_api, ''))) {
			return '';
		} # if
		
		return $this->makeBaseUrl("full") . 'retrieve.php?output=xml' . $this->makeApiRequestString();
	} # makeRetrieveUrl

	/*
	 * Geef het pad op naar de image
	 */
	function makeImageUrl($spot, $height, $width) {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_view_spotimage, '')) {
			return '';
		} # if
		
		# Volgens issue 941 wil men soms vanuit de RSS of Newznab feed rechtstreeks
		# images kunnen laden. We checken of het 'getimage' recht rechtstreeks via de
		# API aan te roepen is, en zo ja, creeren we API urls.
		$apiKey = '';
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_consume_api, 'getimage')) {
			$apiKey = $this->makeApiRequestString();
		} # if
		
		return $this->makeBaseUrl("path") . '?page=getimage&amp;messageid=' . urlencode($spot['messageid']) . '&amp;image[height]=' . $height . '&amp;image[width]=' . $width . $apiKey;
	} # makeImageUrl

	/*
	 * Creert een sorteer url
	 */
	function makeSortUrl($page, $sortby, $sortdir) {
		return $this->makeBaseUrl("path") . '?page=' . $page . $this->convertFilterToQueryParams() . '&amp;sortby=' . $sortby . '&amp;sortdir=' . $sortdir;
	} # makeSortUrl

	/*
	 * Creert een gravatar url
	 */
	function makeCommenterImageUrl($fullComment) {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_view_spotimage, 'avatar')) {
			return '';
		} # if
		
		if (!empty($fullComment['user-avatar'])) {
			# Return the image as inline base64 encoded data
			return 'data:image/png;base64,' . $fullComment['user-avatar'];
		} else {
			$md5 = md5(base64_decode($fullComment['user-key']['modulo']));
			return $this->makeBaseUrl("path") . '?page=getimage&amp;image[type]=avatar&amp;image[size]=32&amp;image[md5]=' . urlencode($md5);
		} # else 
	} # makeCommenterImageUrl

	/*
	 * Creert een gravatar url
	 */
	function makeGravatarUrl($avatar, $size=80, $default='mm', $rating='g') {
		return $this->makeBaseUrl("path") . '?page=getimage&amp;image[type]=gravatar&amp;image[type]=md5' . $avatar . '&amp;image[size]=' . $size . '&amp;image[default]=' . $default . '&amp;image[rating]=' . $rating;
	} # makeGravatarUrl

	/*
	 * Creert een sorteer url die andersom sorteert 
	 * dan de huidige sortering
	 */
	function makeToggleSortUrl($page, $sortby, $sortdir) {
		$curSort = $this->getActiveSorting();

		/**
		 * If we are currently sorting on the same field, make
		 * sure we are reversing direction from the current sort
		 */
		if ($curSort['friendlyname'] == $sortby) {
				if ($curSort['direction'] == 'ASC') {
				$sortdir = 'DESC';
			} else {
				$sortdir = 'ASC';
			} # else
		} # if
		return $this->makeBaseUrl("path") . '?page=' . $page . $this->convertFilterToQueryParams() . '&amp;sortby=' . $sortby . '&amp;sortdir=' . $sortdir;
	} # makeToggleSortUrl
	
	/*
	 * Creert een category url
	 */
	function makeCatUrl($spot) {
		# subcata mag altijd maar 1 category hebben, dus exploden we niet
		$catSpot = substr($spot['subcata'], 0, -1);
		return $this->makeBaseUrl("path") . '?search[tree]=cat' . $spot['category'] . '_' . $catSpot . '&amp;sortby=stamp&amp;sortdir=DESC';
	} # makeCatUrl

	/*
	 * Creert een subcategory url
	 */
	function makeSubCatUrl($spot, $cat) {
		$catSpot = explode("|", $cat);
		
		/* Format the subcatz url */
		$subcatzStr = $spot['subcatz'];
		if (!empty($subcatzStr)) {
			$subcatzStr = '_z' . $subcatzStr[1];
		} # if
		
		return $this->makeBaseUrl("path") . '?search[tree]=cat' . $spot['category'] . $subcatzStr . '_' . $catSpot[0] . '&amp;sortby=stamp&amp;sortdir=DESC';
	} # makeSubCatUrl

	/*
	 * Creert een Poster url
	 */
	function makePosterUrl($spot) {
		return $this->makeBaseUrl("path") . '?search[tree]=&amp;search[value][]=Poster:=:' . urlencode($spot['poster']) . '&amp;sortby=stamp&amp;sortdir=DESC';
	} # makePosterUrl

	/*
	 * Creeert een linkje naar een zoekopdracht op spotterid
	 */
	function makeSpotterIdUrl($spot) {
		return $this->makeBaseUrl("path") . '?search[tree]=&amp;search[value][]=SpotterID:=:' . urlencode($spot['spotterid']) . '&amp;sortby=stamp&amp;sortdir=DESC';
	} # makeSpotterIdUrl

	/*
	 * Creeert een linkje naar een zoekopdracht op tag
	 */
	function makeTagUrl($spot) {
		return $this->makeBaseUrl("path") . '?search[tree]=&amp;search[value][]=Tag:=:' . urlencode($spot['tag']);
	} # makeTagUrl

	/*
	 * Creeert een request string met username en apikey als deze zijn opgegeven
	 */
	function makeApiRequestString() {
		# Controleer de users' rechten
		if (!$this->_spotSec->allowed(SpotSecurity::spotsec_consume_api, '')) {
			return '';
		} # if

		if ($this->_currentSession['user']['userid'] > SPOTWEB_ADMIN_USERID) {
			return '&amp;apikey=' . $this->_currentSession['user']['apikey'];
		} else {
			return '';
		} # else
	} # makeApiRequestString

	/* 
	 * Creert een RSS url
	 */
	function makeRssUrl() {
		if (isset($this->_params['parsedsearch'])) {
			return $this->makeBaseUrl("path") . '?page=rss&amp;' . $this->convertFilterToQueryParams() . '&amp;' . $this->convertSortToQueryParams();
		} else {
			return '';
		} # if
	} # makeRssUrl
	
	/*
	 * Creert een basis navigatie pagina
	 */
	function getPageUrl($page) {
		return $this->makeBaseUrl("path") . '?page=' . $page;
	} # getPageUrl
	
	/*
	 * Geeft het linkje terug naar ons zelf
	 */
	function makeSelfUrl($type) {
		return $this->makeBaseUrl($type) . htmlspecialchars((isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ""));
	} # makeSelfUrl
	
	# Function from http://www.php.net/manual/en/function.filesize.php#99333
	function format_size($size) {
		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		if ($size == 0) { 
			return('n/a'); 
		} else {
			return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]); 

			// test (n.a.v. http://gathering.tweakers.net/forum/list_message/36208481#36208481) om altijd op 
			// 3 getallen te eindigen, maar maakt het niet rustiger.
			//
			//		$roundedSize = round($size/pow(1024, ($i = floor(log($size, 1024)))),99);
			//		return number_format($roundedSize, 3 - strlen(round($roundedSize))) . $sizes[$i];
		} # else
	} # format_size

	
	function formatContent($tmp) {
		# escape alle embedded HTML, maar eerst zetten we de spot inhoud om naar 
		# volledige HTML, dit doen we omdat er soms embedded entities (&#237; e.d.) 
		# in zitten welke we wel willen behouden.
		$tmp = html_entity_decode($tmp, ENT_COMPAT, 'UTF-8');
		$tmp = htmlentities($tmp, ENT_QUOTES, 'UTF-8');
		
		# Code gecopieerd vanaf 
		#		http://stackoverflow.com/questions/635844/php-how-to-grab-an-url-out-of-a-chunk-of-text
		# converteert linkjes naar bb code
		$pattern = "(([^=])((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\))+[\w\d:#@%/;$()~_?\+-=\\\.&]*))";
		$tmp = preg_replace($pattern, '\1[url=\2]\2[/url]', $tmp);

		# initialize ubb parser
		$parser = new SpotUbb_parser($tmp);
		TagHandler::setDeniedTags( Array() );
		TagHandler::setadditionalinfo('img', 'allowedimgs', $this->getSmileyList() );
        $tmp = $parser->parse();
		$tmp = $tmp[0];
	
		# en replace eventuele misvormde br tags
		$tmp = str_ireplace('&lt;br&gt;', '<br />', $tmp);
		$tmp = str_ireplace('&lt;br /&gt;', '<br />', $tmp);
		$tmp = str_ireplace('&amp;lt;br />', '<br />', $tmp);

		return $tmp;
	} # formatContent

	/*
	 * Geeft de huidige lijst met categoryselectie terug
	 * als een comma seperated lijst voor de dynatree initialisatie
	 */
	function categoryListToDynatree() {
		return $this->_spotsOverview->compressCategorySelection($this->_params['parsedsearch']['categoryList'], $this->_params['parsedsearch']['strongNotList']);
	} # categoryListToDynatree
	
	/*
	 * Converteert de aanwezige filters naar een nieuwe, eventueel
	 * geoptimaliserde GET (query) parameters 
	 */
	function convertFilterToQueryParams() {
		#var_dump($this->_params['parsedsearch']['categoryList']);
		#var_dump($this->_params['parsedsearch']['strongNotList']);
		#var_dump($this->_params['parsedsearch']['filterValueList']);
		#var_dump($this->_params['parsedsearch']['sortFields']);

		
		//$xml = $this->_spotsOverview->parsedSearchToXml($this->_params['parsedsearch']);
		//$parsed = $this->_spotsOverview->xmlToParsedSearch($xml, $this->_currentSession);
		//var_dump($parsed);
		//die();
		
		return $this->convertUnfilteredToQueryParams() . $this->convertTreeFilterToQueryParams() . $this->convertTextFilterToQueryParams();
	} # convertFilterToQueryParams

	/*
	 * Converteer de huidige unfiltered setting
	 * naar een nieuwe GET query
	 */
	function convertUnfilteredToQueryParams() {
		if (!isset($this->_params['parsedsearch']['unfiltered'])) {
			return '';
		} # if
		
		# en eventueel als de huidige list unfiltered is, geef
		# dat ook mee
		$unfilteredStr = '';
		if ($this->_params['parsedsearch']['unfiltered']) {
			$unfilteredStr = '&amp;search[unfiltered]=true';
		} # if

		return $unfilteredStr;
	} # convertUnfilteredToQueryParams()
	
	/*
	 * Converteert de aanwezige filter boom naar een
	 * nieuwe GET query
	 */
	function convertTreeFilterToQueryParams() {
		if (!isset($this->_params['parsedsearch']['categoryList'])) {
			return '';
		} # if
		
		# Bouwen de search[tree] value op
		return '&amp;search[tree]=' . $this->_spotsOverview->compressCategorySelection($this->_params['parsedsearch']['categoryList'],
														$this->_params['parsedsearch']['strongNotList']);
	} # convertTreeFilterToQueryParams

	/*
	 * Converteert de aanwezige filter velden (behalve de boom)
	 * naar een nieuwe GET query
	 */
	function convertTextFilterToQueryParams() {
		if (!isset($this->_params['parsedsearch']['filterValueList'])) {
			return '';
		} # if

		# Vervolgens bouwen we de filtervalues op
		$filterStr = '';
		foreach($this->_params['parsedsearch']['filterValueList'] as $value) {
			$filterStr .= '&amp;search[value][]=' . $value['fieldname'] . ':' . $value['operator'] . ':' . htmlspecialchars($value['value'], ENT_QUOTES, "utf-8");
		} # foreach

		return $filterStr;
	} # convertTextFilterToQueryParams

	/*
	 * Geeft de huidige actieve sortering terug
	 */
	function getActiveSorting() {
		$activeSort = array('field' => '',
							'direction' => '',
							'friendlyname' => '');
		
		# als we niet aan het sorteren zijn, doen we niets
		if (!isset($this->_params['parsedsearch'])) {
			return $activeSort;
		} # if
		
		# we voegen alleen sorteringen toe die ook door
		# de gebruiker expliciet zijn toegevoegd
		foreach($this->_params['parsedsearch']['sortFields'] as $value) {
			if (!$value['autoadded']) {
				$activeSort['field'] = $value['field'];
				$activeSort['direction'] = $value['direction'];
				$activeSort['friendlyname'] = $value['friendlyname'];
				break;
			} # if
		} # foreach
		
		return $activeSort;
	} # getActiveSorting
	
	/*
	 * Converteert de huidige actieve sorteer parameters
	 * naar GET parameters voor in de URL
	 */
	function convertSortToQueryParams() {
		$sortStr = '';
		$activeSort = $this->getActiveSorting();
		
		if (!empty($activeSort['field'])) {
			return '&amp;sortby=' . $activeSort['friendlyname'] . '&amp;sortdir=' . $activeSort['direction'];
		} # if
		
		return '';
	} # convertSortToQueryParams

	/* 
	 * Safely escape de velden en vul wat velden in
	 */
	function formatSpotHeader($spot) {
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
		
		# fix the sabnzbdurl, searchurl, sporturl, subcaturl, posterurl
		$spot['sabnzbdurl'] = $this->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $this->makeSearchUrl($spot);
		$spot['spoturl'] = $this->makeSpotUrl($spot);
		$spot['caturl'] = $this->makeCatUrl($spot);
		$spot['subcaturl'] = $this->makeSubCatUrl($spot, $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]);
		$spot['posterurl'] = $this->makePosterUrl($spot);

		// title escapen
		$spot['title'] = htmlentities($spot['title'], ENT_QUOTES, 'UTF-8');
		$spot['title'] = html_entity_decode($spot['title'], ENT_COMPAT, 'UTF-8');
		$spot['title'] = strip_tags($this->remove_extensive_dots($spot['title']));
		$spot['poster'] = htmlspecialchars(strip_tags($spot['poster']), ENT_QUOTES, 'UTF-8');
		
		// we zetten de short description van de category bij
		$spot['catshortdesc'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
		$spot['catdesc'] = SpotCategories::Cat2Desc($spot['category'], $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]);
		
		// commentcount en rating altijd teruggeven
		$spot['commentcount'] = (int) $spot['commentcount'];
		$spot['rating'] = (int) $spot['rating'];
		
		// is deze spot al eens gedownload?
		$spot['hasbeendownloaded'] = ($spot['downloadstamp'] != NULL);

		// is deze spot al eens bekeken?
		$spot['hasbeenseen'] = ($spot['seenstamp'] != NULL);
		
		// zit deze spot in de watchlist?
		$spot['isbeingwatched'] = ($spot['watchstamp'] != NULL);
		
		return $spot;
	} # formatSpotHeader

	/*
	 * Formatteert (maakt op) een lijst van comments
	 */
	function formatComments($comments) {
		// escape de HTML voor de comments
		$commentCount = count($comments);
		for($i = 0; $i < $commentCount; $i++ ){
			$comments[$i]['fromhdr'] = htmlentities($comments[$i]['fromhdr'], ENT_NOQUOTES, 'UTF-8');
			
			# we joinen eerst de contents zodat we het kunnen parsen als 1 string
			# en tags over meerdere lijnen toch nog werkt. We voegen een extra \n toe
			# om zeker te zijn dat we altijd een array terugkrijgen
			$tmpBody = implode("\n", $comments[$i]['body']);
			$tmpBody = $this->formatContent($tmpBody);
			$comments[$i]['body'] = explode("\n", $tmpBody);
		} # for

		return $comments;
	} # formatComments
	
	/*
	 * Omdat we geen zin hebben elke variabele te controleren of hij bestaat,
	 * vullen we een aantal defaults in.
	 */
	function formatSpot($spot) {
		# formatteer de spot
		$spot = $this->formatSpotHeader($spot);
		
		// Category is altijd een integer bij ons
		$spot['category'] = (int) $spot['category'];
		
		// Geen website? Dan standaard naar de zoekmachine
		if (empty($spot['website'])) {
			$spot['website'] = $this->makeSearchUrl($spot);
		} # if
		
		// geef de category een fatsoenlijke naam
		$spot['catname'] = SpotCategories::HeadCat2Desc($spot['category']);
		$spot['formatname'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
		
		// properly escape several  urls
		if (!is_array($spot['image'])) {
			$spot['image'] = htmlspecialchars($spot['image']);
		} # if
		$spot['website'] = htmlspecialchars($spot['website']);
		$spot['tag'] = htmlspecialchars(strip_tags($spot['tag']), ENT_QUOTES, 'UTF-8');
		
		// description
		$spot['description'] = $this->formatContent($spot['description']);
				
		return $spot;
	} # formatSpot

	function isSpotNew($spot) {
		return ( $this->_currentSession['user']['lastread'] < $spot['stamp'] && $spot['seenstamp'] == NULL);
	} # isSpotNew
	
	#
	# Copied from:
	# 	http://www.mdj.us/web-development/php-programming/another-variation-on-the-time-ago-php-function-use-mysqls-datetime-field-type/
	# DISPLAYS COMMENT POST TIME AS "1 year, 1 week ago" or "5 minutes, 7 seconds ago", etc...	
	function time_ago($date, $granularity=2) {
		$difference = time() - $date;
		$periods = array('decade' => 315360000,
			'year' => 31536000,
			'month' => 2628000,
			'week' => 604800, 
			'day' => 86400,
			'hour' => 3600,
			'minute' => 60,
			'second' => 1);
			
		$retval = '';
		foreach ($periods as $key => $value) {
			if ($difference >= $value) {
				$time = floor($difference/$value);
				$difference %= $value;
				$retval .= ($retval ? ' ' : '').$time.' ';
				$retval .= ngettext($key, $key.'s', $time);
				$retval .= ', ';
				$granularity--;
			} # if
			
			if ($granularity == '0') { break; }

		}
		
		return substr($retval, 0, -2);
	} # time_ago()


	function formatDate($stamp, $type) {
		if (empty($stamp)) {
			return "onbekend";
		} elseif (substr($type, 0, 6) == 'force_') {
			return strftime("%a, %d-%b-%Y (%H:%M)", $stamp);
		} elseif ($this->_currentSession['user']['prefs']['date_formatting'] == 'human') {
			return $this->time_ago($stamp);
		} else {
			switch($type) {
				case 'comment'		:
				case 'spotlist'		: 
				case 'lastupdate'	: 
				case 'lastvisit'	:
				case 'userlist'		:
				default 			: return strftime($this->_currentSession['user']['prefs']['date_formatting'], $stamp);
			} # switch
		} # else
	} # formatDate
	
	function isModerated($spot) {
		return ($spot['moderated'] != 0);
	} # isModerated

	/*
	 * Geeft een lijst van mogelijke smilies terug
	 */
	function getSmileyList() {
		return array();
	} # getSmileyList
	
	# Functie voor in combinatie met SpotPage_statics.php -
	# deze functie hoort een lijst van onze static files terug te geven die door de SpotPage_statics
	# dan geserved wordt als nooit meer veranderend. 
	function getStaticFiles($type) {
		return array();
	} # getStaticFiles

	# Functie voor in combinatie met SpotPage_statics.php -
	# deze functie kijkt wat de laatste timetsamp is van de file en kan gebruikt worden in de templates.
	# Omdat stat() behoorlijk traag is, is het voor betere performance aan te raden handmatig je versie nummer
	# op te hogen in je template en deze functie niet te gebruiken
	function getStaticModTime($type) {
		$fileTime = 0;
		$fileList = $this->getStaticFiles($type);
		
		foreach($fileList as $file) {
			$thisftime = filemtime($file);
			
			if ($thisftime > $fileTime) {
				$fileTime = $thisftime;
			} # if
		} # foreach
		
		return $fileTime;
	} # getStaticFiles

	function remove_extensive_dots($s) {
		if (substr_count($s,  '.') > 3) {
			$s = str_replace('.', ' ', $s);
		} # if
		return $s;
	} # remove_extensive_dots
	
	/*
	 * Creeer een anti-XSRF cookie
	 */
	function generateXsrfCookie($action) {
		return SpotReq::generateXsrfCookie($action);
	} # generateXsrfCookie

	/*
	 * API to hash
	 */
	function apiToHash($api) {
		return sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)) . $api . $this->_settings->get('pass_salt'));
	} # apiToHash 
	
 	/*
	 * Geeft de lijst met users terug
	 */
	function getUserList() {
		# Check users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_list_all_users, '');
		
		return $this->_db->getUserListForDisplay();
	} # getUserList

 	/*
	 * Returns the list of all spotters on the users' blacklist
	 */
	function getSpotterList() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_blacklist_spotter, '');
		
		return $this->_db->getSpotterList($this->_currentSession['user']['userid']);
	} # getSpotterList
	
	/*
	 * Returns the specific blacklist record for one spotterid
	 */
	function getBlacklistForSpotterId($spotterId) {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_blacklist_spotter, '');
		
		return $this->_db->getBlacklistForSpotterId($this->_currentSession['user']['userid'], $spotterId);
	} # getBlacklistForSpotterId
	
	/*
	 * Wanneer was de spotindex voor het laatst geupdate?
	 */
	function getLastSpotUpdates() {
		# query wanneer de laatste keer de spots geupdate werden
		$nntp_hdr_settings = $this->_settings->get('nntp_hdr');
		return $this->_db->getLastUpdate($nntp_hdr_settings['host']);
	} # getLastSpotUpdates
	
	/*
	 * Leegt de lijst met gedownloade NZB bestanden
	 */
	function clearDownloadList() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, 'erasedls');
		
		$this->_db->clearSpotStateList(SpotDb::spotstate_Down, $this->_currentSession['user']['userid']);
	} # clearDownloadList
	
	/*
	 * Converteert een permission id naar een string
	 */
	function permToString($perm) {
		return $this->_spotSec->toHuman($perm);
	} # permToString
	
	/*
	 * Geeft alle mogelijke Spotweb permissies terug
	 */
	function getAllAvailablePerms() {
		return $this->_spotSec->getAllPermissions();
	} # getAllAvailablePerms
	
	/*
	 * Geeft een lijst met alle security groepen terug
	 */
	function getGroupList() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_display_groupmembership, '');
		
		return $this->_db->getGroupList(null);
	}  # getGroupList

	/*
 	 * Geeft een lijst met alle security groepen terug voor een bepaalde user
	 */
	function getGroupListForUser($userId) {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_display_groupmembership, '');
		
		return $this->_db->getGroupList($userId);
	}  # getGroupListForUser

	/*
	 * Geeft de users' custom CSS terug 
	 */
	function getUserCustomCss() {
		if (!$this->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) {
			return '';
		} # if
		
		return $this->_currentSession['user']['prefs']['customcss'];
	} # if 
	
	/*
	 * Geeft alle permissies in een bepaalde securitygroup terug
	 */
	function getSecGroup($groupId) {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');
		
		$tmpGroup = $this->_db->getSecurityGroup($groupId);
		if (!empty($tmpGroup)) {
			return $tmpGroup[0];
		} else {
			return false;
		} # else
	} # getSecGroup

	/*
	 * Geeft alle permissies in een bepaalde securitygroup terug
	 */
	function getSecGroupPerms($id) {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_securitygroups, '');
		
		return $this->_db->getGroupPerms($id);
	} # getSecGroupPerms
	
	/*
	 * Redirect naar een opgegeven url
	 */
	function redirect($url) {
		Header("Location: " . $url); 
	} # redirect()
	
	/*
	 * Get users' filter list
	 */
	function getUserFilterList() {
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		return $spotUser->getFilterList($this->_currentSession['user']['userid'], 'filter');
	} # getUserFilterList

	/*
	 * Get specific filter
	 */
	function getUserFilter($filterId) {
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		return $spotUser->getFilter($this->_currentSession['user']['userid'], $filterId);
	} # getUserFilter

	/*
	 * Get index filter
	 */
	function getIndexFilter() {
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		return $spotUser->getIndexFilter($this->_currentSession['user']['userid']);
	} # getIndexFilter

	/*
	 * Controleer  of de user al een report heeft aangemaakt
	 */
	function isReportPlaced($messageId) {
		return $this->_db->isReportPlaced($messageId, $this->_currentSession['user']['userid']);
	} # isReportPlaced
	
	/*
	 * Genereert een random string
	 */
	function getCleanRandomString($len) {
		$spotParser = new SpotParser();
		$spotSigning = new SpotSigning();
		return substr($spotParser->specialString(base64_encode($spotSigning->makeRandomStr($len))), 0, $len);
	} # getRandomStr
	
	/*
	 * Geeft de naam van de nzbhandler terug
	 */
	function getNzbHandlerName(){
		return $this->_nzbHandler->getName();
	} # getNzbHandlerName

	/*
	 * Geeft een string met gesupporte API functies terug of false wanneer er geen API support is
	 * voor de geselecteerde NzbHandler
	 */
	function getNzbHandlerApiSupport(){
		return $this->_nzbHandler->hasApiSupport();
	} # getNzbHandlerApiSupport

	/*
	 * Geeft een array met valide statistics graphs terug
	 */
	function getValidStatisticsGraphs(){
		$spotImage = new SpotImage($this->_db);
		return $spotImage->getValidStatisticsGraphs();
	} # getValidStatisticsGraphs

	/*
	 * Geeft een array met valide statistics limits terug
	 */
	function getValidStatisticsLimits(){
		$spotImage = new SpotImage($this->_db);
		return $spotImage->getValidStatisticsLimits();
	} # getValidStatisticsGraphs
	
	/*
	 * Returns an array with configured languages for this system
	 */
	function getConfiguredLanguages() {
		return $this->_settings->get('system_languages');
	} # getConfiguredLanguages

} # class SpotTemplateHelper
