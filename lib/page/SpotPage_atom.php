<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_atom extends SpotPage_Abs {
	private $_params;

	function __construct($db, $settings, $currentUser, $params) {
		parent::__construct($db, $settings, $currentUser);
		
		$this->_params = $params;
	}

	function render() {
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$filter = $spotsOverview->filterToQuery($this->_params['search']);
		$pageNr = $this->_params['page'];

		# laad de spots
		$spotsTmp = $spotsOverview->loadSpots($this->_currentUser['userid'],
							$pageNr, 
							$this->_currentUser['prefs']['perpage'],
							$filter,
							array('field' => $this->_params['sortby'], 
								  'direction' => $this->_params['sortdir']));
		
		$fullSpots = array();
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));

		foreach($spotsTmp['list'] as $spot) {
			try {
				$fullSpots[] = $spotsOverview->getFullSpot($spot['messageid'], $this->_currentUser['userid'], $spotnntp); 					
			}catch(Exception $x) {
				// Article not found. ignore.
			}

		}
			
		$this->template('atom', array('spots' => $fullSpots));
	} # render()
	
} # class SpotPage_index
