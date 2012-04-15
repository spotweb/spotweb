<?php
class SpotPage_editfilter extends SpotPage_Abs {
	private $_editFilterForm;
	private $_filterId;
	private $_orderList;
	private $_search;
	private $_sorton;
	private $_sortorder;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editFilterForm = $params['editfilterform'];
		$this->_filterId = $params['filterid'];
		$this->_orderList = $params['orderfilterslist'];
		$this->_search = $params['search'];
		$this->_sorton = $params['sorton'];
		$this->_sortorder = $params['sortorder'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_filters, '');
		
		# editfilter resultaat is standaard niet geprobeerd
		$editResult = array();

		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: filters";
		
		# haal de te editten filter op 
		$spotFilter = $spotUserSystem->getFilter($this->_currentSession['user']['userid'], $this->_filterId);
		
		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editFilterForm['action'];

		# als de te wijzigen security group niet gevonden kan worden,
		# geef dan een error
		if ((empty($spotFilter)) && ($formAction == 'changefilter')) {
			$editResult = array('result' => 'failure');
			$formMessages['errors'][] = _("Filter doesn't exist");
		} # if

		
		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'removefilter' : {
					$spotUserSystem->removeFilter($this->_currentSession['user']['userid'], $this->_filterId);
					$editResult = array('result' => 'success');
					
					break;
				} # case 'removefilter'
				
				case 'discardfilters' : {
					$spotUserSystem->resetFilterList($this->_currentSession['user']['userid']);
					$editResult = array('result' => 'success');
					
					break;
				} # case 'discardfilters'
				
				case 'setfiltersasdefault' : {
					$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_set_filters_as_default, '');

					$spotUserSystem->setFiltersAsDefault($this->_currentSession['user']['userid']);
					$editResult = array('result' => 'success');
					
					break;
				} # case 'setfiltersasdefault'

				case 'exportfilters': {
					$editResult = $spotUserSystem->filtersToXml($spotUserSystem->getPlainFilterList($this->_currentSession['user']['userid'], 'filter'));
					
					break;
				} # case 'exportfilters' 

				case 'importfilters': {
					if (isset($_FILES['filterimport'])) {
						
						if ($_FILES['filterimport']['error'] == UPLOAD_ERR_OK) {
							$xml = file_get_contents($_FILES['filterimport']['tmp_name']);
							try {
								$filterList = $spotUserSystem->xmlToFilters($xml);
								$spotUserSystem->setFilterList($this->_currentSession['user']['userid'], $filterList);
							} catch(Exception $x) {
								$editResult = array('result' => 'failure');
								$formMessages['errors'][] = _('Uploaded Spotwebfilter in invalid');
							} # catch
						} else {
							$editResult = array('result' => 'failure');
							$formMessages['errors'][] = sprintf(_('Error while uploading filter (%s)'), $_FILES['filterimport']['error']);
						} # if
					
					} else {
						$editResult = array('result' => 'failure');
						$formMessages['errors'][] = _("Filter hasn't been uploaded");
					} # else
					
					break;
				} # case 'importfilters' 
				
				case 'addfilter'	: {
					# Creeer een nieuw filter record - we voegen een filter altijd aan de root toe
					$filter = $this->_editFilterForm;
					$filter['valuelist'] = explode('&', $filter['valuelist']) ;
					$filter['torder'] = 999;
					$filter['tparent'] = 0;
					$filter['children'] = array();
					$filter['filtertype'] = 'filter';
					$filter['sorton'] = $filter['sorton'];
					$filter['sortorder'] = $filter['sortorder'];
					$filter['enablenotify'] = isset($filter['enablenotify']) ? true : false;
						
					# en probeer de filter toe te voegen
					$formMessages['errors'] = $spotUserSystem->addFilter($this->_currentSession['user']['userid'], $filter);
					
					if (!empty($formMessages['errors'])) {
						$editResult = array('result' => 'failure');
					} else {
						$editResult = array('result' => 'success');
					} # else
					
					break;
				} # case 'addfilter' 

				case 'reorder' : {
					$orderCounter = 0;
					
					# Omdat de nestedSortable jquery widget niet een expliciete sortering meegeeft, voegen
					# we die zelf toe aan de hand van hoe de elementen binnen komen
					foreach($this->_orderList as $id => $parent) {
						$spotFilter = $spotUserSystem->getFilter($this->_currentSession['user']['userid'], $id);

						# Als de volgorde of hierarchie dan moet de filter geupdate worden
						if (($spotFilter['torder'] <> $orderCounter) || ($spotFilter['tparent'] <> $parent)) { 
							$spotFilter['torder'] = (int) $orderCounter;
							$spotFilter['tparent'] = (int) $parent;
							$spotUserSystem->changeFilter($this->_currentSession['user']['userid'], $spotFilter);
						} # if
						
						$orderCounter++;
					} # foreach
				} # case 'reorder' 
				
				case 'changefilter'	: {
					$spotFilter = array_merge($spotFilter, $this->_editFilterForm);
					
					$spotUserSystem->changeFilter($this->_currentSession['user']['userid'],
												  $spotFilter);
					$editResult = array('result' => 'success');

					break;
				} # case 'changefilter' 
				
			} # switch
		} # if

		#- display stuff -#
		$this->template('editfilter', array('filter' => $spotFilter,
											'sorton' => $this->_sorton,
											'sortorder' => $this->_sortorder,
											'sortby' => $this->_sorton,
											'sortdir' => $this->_sortorder,
											'lastformaction' => $formAction,
										    'formmessages' => $formMessages,
											'http_referer' => $this->_editFilterForm['http_referer'],
											'editresult' => $editResult));
	} # render
	
} # class SpotPage_editfilter
