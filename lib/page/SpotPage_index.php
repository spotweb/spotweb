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
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		
		# Zet the query parameters om naar een lijst met filters, velden,
		# en sorteringen etc
		$parsedSearch = $spotsOverview->filterToQuery($this->_params['search'], $this->_currentSession);
		$this->_params['search'] = $parsedSearch['search'];
		
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
		if (isset($this->_params['search']['filterValues']['Watch'])) {
			switch($this->_action) {
				case 'remove'	: $this->_db->removeFromWatchlist($this->_params['messageid'], $this->_currentSession['user']['userid']); break;
				case 'add'		: $this->_db->addToWatchList($this->_params['messageid'], $this->_currentSession['user']['userid'], ''); break;
				default			: ;
			}
		}
		
		# laad de spots
		$spotsTmp = $spotsOverview->loadSpots($this->_currentSession['user']['userid'],
							$pageNr, $this->_currentSession['user']['prefs']['perpage'],
							$parsedSearch,
							array('field' => $this->_params['sortby'], 
								  'direction' => $this->_params['sortdir']));

							
		# als er geen volgende pagina is, ook niet tonen
		if (!$spotsTmp['hasmore']) {
			$nextPage = -1;
		} # if
		
		# query wanneer de laatste keer de spots geupdate werden
		$nntp_hdr_settings = $this->_settings->get('nntp_hdr');
		$lastUpdateTime = $this->_db->getLastUpdate($nntp_hdr_settings['host']);
								  
		# zet de page title
		$this->_pageTitle = "overzicht";
		
		#- display stuff -#
		$this->template('header', array('activefilter' => $this->_params['search']));

		$this->template('filters', array('search' => $this->_params['search'],
								  'lastupdate' => $lastUpdateTime,
								  'quicklinks' => $this->_settings->get('quicklinks'),
								  'filters' => $this->_settings->get('filters'),
  								  'activefilter' => $this->_params['search']));
		$this->template('spots', array('spots' => $spotsTmp['list'],
		                        'nextPage' => $nextPage,
								'prevPage' => $prevPage,
								'lastupdate' => $lastUpdateTime,
								'activefilter' => $this->_params['search'],
								'sortby' => $this->_params['sortby'],
								'sortdir' => $this->_params['sortdir']));
		$this->template('footer');
		SpotTiming::stop(__FUNCTION__);
	} # render()
	
} # class SpotPage_index
