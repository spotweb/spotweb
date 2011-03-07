<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_getnzb extends SpotPage_Abs {
	private $_messageid;
	
	function __construct($db, $settings, $prefs, $messageid) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $messageid;
	} # ctor

	
	function render() {
		$hdr_spotnntp = new SpotNntp($this->_settings['nntp_hdr']);
		$hdr_spotnntp->connect(); 

		/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
		if ($this->_settings['nntp_hdr']['host'] == $this->_settings['nntp_nzb']['host']) {
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings['nntp_nzb']);
			$nzb_spotnntp->connect(); 
		} # else
	
		# Haal de spot op en gebruik de informatie daarin om de NZB file op te halen
		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db);
		$fullSpot = $spotsOverview->getFullSpot($this->_messageid, $hdr_spotnntp);
		$nzb = $spotsOverview->getNzb($fullSpot['segment'], $nzb_spotnntp);
		
		# afhankelijk van de NZB actie die er gekozen is schrijven we het op het filesysteem
		# weg, of geven we de inhoud van de nzb gewoon terug
		if ($this->_settings['nzb_download_local'] == true)
		{
			$fname = $this->_settings['nzb_local_queue_dir'] . urlencode($fullSpot['title']) . ".nzb";
			
			if (file_put_contents($fname, $nzb) === false) {
				throw new Exception("Unable to write NZB file");
			} # if

			# Moeten we een script draaien nadat de file er gezet is?
			if (!empty($settings['nzb_local_queue_command'])){ }
				$saveOutput = array();
                $status = 0;
				$cmdToRun = str_replace(array('$SPOTTITLE'), array($fullSpot['title']), $settings['nzb_local_queue_command']);
				
                exec($cmdToRun, $saveOutput, $status);
				
				if ($status != 0) {
					throw new Exception("Unable to execute program: " . $cmdToRun);
				} # if
			# if
		} else {
			Header("Content-Type: application/x-nzb");
			Header("Content-Disposition: attachment; filename=\"" . urlencode($fullSpot['title']) . ".nzb\"");
			echo $nzb;
		} # else
		
		# en voeg hem toe aan de lijst met downloads
		$this->_db->addDownload($fullSpot['messageid']);
	} # render
	
} # SpotPage_getnzb
