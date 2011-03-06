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
		if ($this->_settings['nntp_hdr']['host'] == $this->_settings['nntp_nzb']['host']) {
			$hdr_spotnntp->connect();
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings['nntp_nzb']['host'],
										$this->_settings['nntp_nzb']['enc'],
										$this->_settings['nntp_nzb']['port'],
										$this->_settings['nntp_nzb']['user'],
										$this->_settings['nntp_nzb']['pass']);
			$hdr_spotnntp->connect(); 
			$nzb_spotnntp->connect(); 
		} # else
	
		$xmlar = $hdr_spotnntp->getFullSpot($this->_messageid);
		$nzb = $nzb_spotnntp->getNzb($xmlar['info']['segment']);
		
		if ($this->_settings['nzb_download_local'] == true)
		{
			$myFile = $this->_settings['nzb_local_queue_dir'] .$xmlar['info']['title'] . ".nzb";
			$fh = fopen($myFile, 'w') or die("Unable to open file");
			fwrite($fh, $nzb);
			fclose($fh);
			echo "NZB toegevoegd aan queue : ".$myFile;
		} else {
			Header("Content-Type: application/x-nzb");
			Header("Content-Disposition: attachment; filename=\"" . $xmlar['info']['title'] . ".nzb\"");
			echo $nzb;
		}

	} # render
} # SpotPage_getnzb
