<?php
class SpotPage_speeddial extends SpotPage_Abs {

	function render() {
		$backgroundImage = "images/spotnet.gif";
		$backgroundColor = "123456";
		$text_color = "ffffff";
		$ttfFont = "images/ttf/Arialbd.TTF";
		$fontSize = 24;
		
		$tplHelper = $this->getTplHelper(array());
		
		// Create image
		$img = imagecreatetruecolor(512, 320);

		// Set alphablending to on
		imagealphablending($img, true);

		// Draw a square
		imagefilledrectangle($img, 8, 8, 504, 312, $this->colorHex($img, $backgroundColor));

		// Load and show the background image
		$bg = imagecreatefromgif($backgroundImage);
		list($width, $height, $type, $attr) = getimagesize($backgroundImage);
		imagecopymerge($img, $bg, 256-($width/2), 160-($height/2), 0, 0, $width, $height, 30);
		imagedestroy($bg);
		
		// Add some usefull text
		$text = "Totaal aantal spots: " . $this->_db->getSpotCount('');
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 50, $this->colorHex($img, $text_color), $ttfFont, $text);

		$count = $tplHelper->getNewCountForFilter('');
		if (!$count) { $count = 0; }
		$text = "Aantal nieuwe spots: " . $count;
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 90, $this->colorHex($img, $text_color), $ttfFont, $text);
		
		$text = "Laatste update:";
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 230+$fontSize, $this->colorHex($img, $text_color), $ttfFont, $text);
		
		$nntp_hdr_settings = $this->_settings->get('nntp_hdr');
		$text = $tplHelper->formatDate($this->_db->getLastUpdate($nntp_hdr_settings['host']), 'lastupdate');
		if (!$text) { $text = "onbekend"; }
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 270+$fontSize, $this->colorHex($img, $text_color), $ttfFont, $text);

		// Headers
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Expires: Mon, 15 Apr 2006 12:26:00 GMT');
		header('Pragma: no-cache');
		header('Content-Type: image/png');
		header('refresh:60'); 
		
		// Image output
		imagepng($img);
		imagedestroy($img);
	} # render

	function colorHex($img, $HexColorString) {
		$R = hexdec(substr($HexColorString, 0, 2));
		$G = hexdec(substr($HexColorString, 2, 2));
		$B = hexdec(substr($HexColorString, 4, 2));
		return ImageColorAllocate($img, $R, $G, $B);
	} # colorHex

} # class SpotPage_sabapi