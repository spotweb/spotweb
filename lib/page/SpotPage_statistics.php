<?php
class SpotPage_statistics extends SpotPage_Abs {
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_params = $params;
	} # ctor

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statistics, '');

		# init
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);

		# zet de page title
		$this->_pageTitle = _("Statistics");

		#- display stuff -#
		$parsedSearch = $spotsOverview->filterToQuery('', array('field' => '', 'direction' => ''), $this->_currentSession, $spotUserSystem->getIndexFilter($this->_currentSession['user']['userid']));
		$this->template('statistics', array('quicklinks' => $this->_settings->get('quicklinks'),
											'filters' => $spotUserSystem->getFilterList($this->_currentSession['user']['userid'], 'filter'),
											'parsedsearch' => $parsedSearch,
											'limit' => $this->_params['limit']));
	} # render

} # class SpotPage_statistics
