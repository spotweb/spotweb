<?php
class SpotPage_statistics extends SpotPage_Abs {
	private $_params;

	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);

		$this->_params = $params;
	} # ctor

	function render() {
		# Validate permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statistics, '');

		# init
		$spotUserSystem = new SpotUserSystem($this->_daoFactory, $this->_settings);

		# set the page title
		$this->_pageTitle = _("Statistics");

		#- display stuff -#
		$svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());
		$parsedSearch = $svcSearchQp->filterToQuery('', 
													array('field' => '', 'direction' => ''), 
													$this->_currentSession, 
													$spotUserSystem->getIndexFilter($this->_currentSession['user']['userid'])
												);
		
		$this->template('statistics', array('quicklinks' => $this->_settings->get('quicklinks'),
											'filters' => $spotUserSystem->getFilterList($this->_currentSession['user']['userid'], 'filter'),
											'parsedsearch' => $parsedSearch,
											'limit' => $this->_params['limit']));
	} # render

} # class SpotPage_statistics
