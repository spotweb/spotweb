<?php
class SpotPage_getnzbmobile extends SpotPage_Abs {
	private $_messageid;
	private $_action;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
		$this->_action = $params['action'];
	} # ctor

	
	function render() {
		$hdr_spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');

		/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
		$settings_nntp_hdr = $this->_settings->get('nntp_hdr');
		$settings_nntp_nzb = $this->_settings->get('nntp_nzb');
		if ($settings_nntp_hdr['host'] == $settings_nntp_nzb['host']) {
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings->get('nntp_nzb'));
		} # else

		try {
			$spotNzb = new SpotNzb($this->_db, $this->_settings);
			$spotNzb->handleNzbAction($this->_messageid, $this->_currentSession,
							$this->_action, $hdr_spotnntp, $nzb_spotnntp);
			
			if ($this->_action != 'display') {
				echo "<div data-role=page><div data-role=content><p>NZB saved.</p><a href='" .$this->_settings->get('spotweburl') ."' rel=external data-role='button'>OK</a></div></div>";			
			} # if
		}
		catch(Exception $x) {
			echo "<div data-role=page><div data-role=content><p>" . $x->getMessage() . "</p><a href='". $this->_settings->get('spotweburl') ."' rel=external data-role='button'>OK</a></div></div>";
		} # catch
	} # render
	
} # SpotPage_getnzb
