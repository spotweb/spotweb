<?php
class SpotPage_index extends SpotPage_Abs {
	private $_params;


	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);

		$this->_params = $params;

		$action = $this->_params['action'];
		if (array_search($action, array('add', 'remove')) === false) {
			$action = '';
		}
		$this->_action = $action;		
	} # ctor

	function render() {
		SpotTiming::start(__FUNCTION__);

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');
		
		# als een zoekopdracht is meegegevne, moeten er ook rechten zijn om te mogen zoeken
		if (!empty($this->_params['search'])) {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_search, '');
		} # if
		
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		
		# Zet the query parameters om naar een lijst met filters, velden,
		# en sorteringen etc
		$parsedSearch = $spotsOverview->filterToQuery($this->_params['search'], 
							array('field' => $this->_params['sortby'], 'direction' => $this->_params['sortdir']),
							$this->_currentSession);

 		# Haal de offset uit de URL en zet deze als startid voor de volgende zoektocht
		# Als de offset niet in de url staat, zet de waarde als 0, het is de eerste keer
		# dat de index pagina wordt aangeroepen
		$pageNr = $this->_params['pagenr'];
		$nextPage = $pageNr + 1;
		if ($nextPage == 1) {
			$prevPage = -1;
		} else {
			$prevPage = max($pageNr - 1, 0);
		} # else
		
		# afhankelijk van wat er gekozen is, voer het uit
		if (isset($parsedSearch['filterValueList'][0]['fieldname']) && $parsedSearch['filterValueList'][0]['fieldname'] == "Watch") {
			# Controleer de users' rechten
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_watchlist, '');
			
			switch($this->_action) {
				case 'remove'	: $this->_db->removeFromSpotStateList(SpotDb::spotstate_Watch, $this->_params['messageid'], $this->_currentSession['user']['userid']);
								  $spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);
								  $spotsNotifications->sendWatchlistHandled($this->_action, $this->_params['messageid']);
								  break;
				case 'add'		: $this->_db->addToSpotStateList(SpotDb::spotstate_Watch, $this->_params['messageid'], $this->_currentSession['user']['userid'], '');
								  $spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);
								  $spotsNotifications->sendWatchlistHandled($this->_action, $this->_params['messageid']);
								  break;
				default			: ;
			} # switch 
		} # if
		
		# laad de spots
		$spotsTmp = $spotsOverview->loadSpots($this->_currentSession['user']['userid'],
							$pageNr, 
							$this->_currentSession['user']['prefs']['perpage'],
							$parsedSearch);
							
		# als er geen volgende pagina is, ook niet tonen
		if (!$spotsTmp['hasmore']) {
			$nextPage = -1;
		} # if
		
		# zet de page title
		$this->_pageTitle = "overzicht";
		
		#- display stuff -#
		$this->template('spots', array(
								'spots' => $spotsTmp['list'],
								'quicklinks' => $this->_settings->get('quicklinks'),
								'filters' => $this->_db->getFilterList(2),
		                        'nextPage' => $nextPage,
								'prevPage' => $prevPage,
								'parsedsearch' => $parsedSearch,
								'data' => $this->_params['data']));
		SpotTiming::stop(__FUNCTION__);
	} # render()
	
} # class SpotPage_index
