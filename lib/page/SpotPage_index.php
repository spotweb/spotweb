<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_index extends SpotPage_Abs {
	private $_params;

	function __construct($db, $settings, $prefs, $params) {
		parent::__construct($db, $settings, $prefs);
		
		$this->_params = $params;
	} # ctor

	function render() {
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$filter = $spotsOverview->filterToQuery($this->_params['search']);
		
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
		
		# laad de spots
		$spotsTmp = $spotsOverview->loadSpots($pageNr, $this->_prefs['perpage'], $filter, 
							array('field' => $this->_params['sortby'], 
								  'direction' => $this->_params['sortdir']));

							
		# als er geen volgende pagina is, ook niet tonen
		if (!$spotsTmp['hasmore']) {
			$nextPage = -1;
		} # if
		
		# query wanneer de laatste keer de spots geupdate werden
		$lastUpdateTime = $this->_db->getLastUpdate($this->_settings['nntp_hdr']['host']);
								  
		# zet de page title
		$this->_pageTitle = "overzicht";
		
		#- display stuff -#
		$this->template('header');

		$this->template('filters', array('search' => $this->_params['search'],
								  'lastupdate' => $lastUpdateTime,
								  'quicklinks' => $this->_settings['quicklinks'],
								  'filters' => $this->_settings['filters'],
  								  'activefilter' => $this->_params['search']));
		$this->template('spots', array('spots' => $spotsTmp['list'],
		                        'nextPage' => $nextPage,
								'prevPage' => $prevPage,
								'lastupdate' => $lastUpdateTime,
								'activefilter' => $this->_params['search'],
								'sortby' => $this->_params['sortby'],
								'sortdir' => $this->_params['sortdir']));
		$this->template('footer');
	} # render()
	
} # class SpotPage_index
