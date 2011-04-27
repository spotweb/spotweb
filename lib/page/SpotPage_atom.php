<?php
class SpotPage_atom extends SpotPage_Abs {
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		
		$this->_params = $params;
	}

	function render() {
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# Zet the query parameters om naar een lijst met filters, velden,
		# en sorteringen etc
		$parsedSearch = $spotsOverview->filterToQuery($this->_params['search'], $this->_currentSession);
		$this->_params['search'] = $parsedSearch['search'];
		
		# laad de spots
		$pageNr = $this->_params['page'];
		$spotsTmp = $spotsOverview->loadSpots($this->_currentSession['user']['userid'],
							$pageNr, 
							$this->_currentSession['user']['prefs']['perpage'],
							$parsedSearch,
							array('field' => $this->_params['sortby'], 
								  'direction' => $this->_params['sortdir']));
		
		$fullSpots = array();
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));

		foreach($spotsTmp['list'] as $spot) {
			try {
				$fullSpots[] = $spotsOverview->getFullSpot($spot['messageid'], $this->_currentSession['user']['userid'], $spotnntp); 					
			}catch(Exception $x) {
				// Article not found. ignore.
			}

		}
			
		$this->template('atom', array('spots' => $fullSpots));
	} # render()
	
} # class SpotPage_index
