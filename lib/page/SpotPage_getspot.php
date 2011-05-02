<?php
class SpotPage_getspot extends SpotPage_Abs {
	private $_messageid;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $messageid) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $messageid;
	} # ctor

	function render() {
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));

		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($this->_messageid, $this->_currentSession['user']['userid'], $spotnntp);

		# zet de page title
		$this->_pageTitle = "spot: " . $fullSpot['title'];

		# seen list
		if (	$this->_settings->get('keep_seenlist')
				&& $fullSpot['seenstamp'] == NULL
					&& (
						( $this->_settings->get('auto_markasread') && max($this->_currentSession['user']['lastvisit'],$this->_currentSession['user']['lastread']) < $fullSpot['stamp'] )
						|| $this->_currentSession['user']['lastread'] < $fullSpot['stamp']
					)
			) {
				$spotsOverview->addToSeenList($this->_messageid, $this->_currentSession['user']['userid']);
		} # if

		#- display stuff -#
		$this->template('header');
		$this->template('spotinfo', array('spot' => $fullSpot));
		$this->template('footer');
	} # render

} # class SpotPage_getspot