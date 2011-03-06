<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "SpotCategories.php";

class SpotPage_getnzb extends SpotPage_Abs {
	private $_messageid;
	
	function __construct($db, $settings, $prefs, $messageid) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $messageid;
	} # ctor

	
	function render() {
		$hdr_spotnntp = new SpotNntp($this->_settings['nntp_hdr']['host'],
									$this->_settings['nntp_hdr']['enc'],
									$this->_settings['nntp_hdr']['port'],
									$this->_settings['nntp_hdr']['user'],
									$this->_settings['nntp_hdr']['pass']);
		$hdr_spotnntp->connect(); 

		/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
		if ($this->_settings['nntp_hdr']['host'] == $this->_settings['nntp_nzb']['host']) {
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings['nntp_nzb']['host'],
										$this->_settings['nntp_nzb']['enc'],
										$this->_settings['nntp_nzb']['port'],
										$this->_settings['nntp_nzb']['user'],
										$this->_settings['nntp_nzb']['pass']);
			$nzb_spotnntp->connect(); 
		} # else
	
		# Haal de spot op en gebruik de informatie daarin om de NZB file op te halen
		$fullSpot = $hdr_spotnntp->getFullSpot($this->_messageid);
		$nzb = $nzb_spotnntp->getNzb($fullSpot['segment']);
		
		# afhankelijk van de NZB actie die er gekozen is schrijven we het op het filesysteem
		# weg, of geven we de inhoud van de nzb gewoon terug
		if ($this->_settings['nzb_download_local'] == true)
		{
			$fname = $this->_settings['nzb_local_queue_dir'] . urlencode($fullSpot['title']) . ".nzb";
			
			if (file_put_contents($fname, $nzb) === false) {
				throw new Exception("Unable to write NZB file");
			} # if
		} else {
			Header("Content-Type: application/x-nzb");
			Header("Content-Disposition: attachment; filename=\"" . urlencode($fullSpot['title']) . ".nzb\"");
			echo $nzb;
		} # else

	} # render
	
} # SpotPage_getnzb
