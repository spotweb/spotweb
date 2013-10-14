<?php

class SpotPage_editfilter extends SpotPage_Abs {
	private $_editFilterForm;
	private $_filterId;
	private $_orderList;
	private $_search;
	private $_sorton;
	private $_sortorder;
	private $_data;
	
	function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_editFilterForm = $params['editfilterform'];
		$this->_filterId = $params['filterid'];
		$this->_orderList = $params['orderfilterslist'];
		$this->_search = $params['search'];
		$this->_sorton = $params['sorton'];
		$this->_sortorder = $params['sortorder'];
		$this->_data = $params['data'];
	} # ctor

	function render() {
		$result = new Dto_FormResult('notsubmitted');
							  
		# Make sure the user has the appropriate rights
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_filters, '');
		
		# Instantiate the SpotUser system
		$svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);
        $spotFilter = $svcUserFilter->getFilter($this->_currentSession['user']['userid'], $this->_filterId);

		# set the page title
		$this->_pageTitle = "spot: filters";

		/*
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editFilterForm['action'];
		
		# Are we submitting this form, or just rendering it?
		if (!empty($formAction)) {
			switch($formAction) {
				case 'removefilter' : {
					$result = $svcUserFilter->removeFilter($this->_currentSession['user']['userid'], $this->_filterId);
					
					break;
				} # case 'removefilter'
				
				case 'discardfilters' : {
					$result = $svcUserFilter->resetFilterList($this->_currentSession['user']['userid']);
					
					break;
				} # case 'discardfilters'

				case 'setfiltersasdefault' : {
					$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_set_filters_as_default, '');

					$result = $svcUserFilter->setFiltersAsDefault($this->_currentSession['user']['userid']);
					
					break;
				} # case 'setfiltersasdefault'

				case 'exportfilters': {
					$result = $svcUserFilter->filtersToXml($svcUserFilter->getPlainFilterList($this->_currentSession['user']['userid'], 'filter'));
					
					break;
				} # case 'exportfilters' 

				case 'importfilters': {
                    $uploadHandler = new Services_Providers_FileUpload('editfilterform', 'filterimport');

                    if ($uploadHandler->isUploaded()) {
                        if ($uploadHandler->success()) {

                            try {
                                $xml = file_get_contents($uploadHandler->getTempName());
                                $filterList = $svcUserFilter->xmlToFilters($xml);
                                $svcUserFilter->setFilterList($this->_currentSession['user']['userid'],
                                                              $filterList->getData('filters'));
                            } catch(Exception $x) {
                                $result->addError(_('Uploaded Spotwebfilter in invalid'));
                            } # catch

                        } else {
                            $result->addError(sprintf(_('Error while uploading filter (%s)', $uploadHandler->errorText())));
                        } # else
                    } else {
                        $result->addError(_("Filter hasn't been uploaded"));
                    } # else

					break;
				} # case 'importfilters' 
				
				case 'addfilter'	: {
					# Create a new filter record, we will always add the filter to the root with no children
					$filter = $this->_editFilterForm;
					$filter['valuelist'] = explode('&', $filter['valuelist']) ;
					$filter['torder'] = 999;
					$filter['tparent'] = 0;
					$filter['children'] = array();
					$filter['filtertype'] = 'filter';
					$filter['enablenotify'] = isset($filter['enablenotify']) ? true : false;
						
					# and actually add the filter
					$result = $svcUserFilter->addFilter($this->_currentSession['user']['userid'], $filter);

					break;
				} # case 'addfilter' 

				case 'reorder' : {
					/*
					 * The nestedSortable jquery widget will not pass an explicit sorting, we
					 * add it ourselves using the order of the elements we are given
					 */
					$result = $svcUserFilter->reorderFilters($this->_currentSession['user']['userid'], $this->_orderList);
                    break;
				} # case 'reorder' 
				
				case 'changefilter'	: {
					# Retrieve the filter we want to edit
                    $this->_editFilterForm['id'] = $this->_filterId;
					
					$result = $svcUserFilter->changeFilter($this->_currentSession['user']['userid'],
                                                           $this->_editFilterForm);

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
										    'result' => $result,
										    'data' => $this->_data,
											'http_referer' => $this->_editFilterForm['http_referer']));
	} # render
	
} # class SpotPage_editfilter
