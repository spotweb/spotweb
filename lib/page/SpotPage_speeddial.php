<?php
class SpotPage_speeddial extends SpotPage_Abs {

	function render() {
		$backgroundImage = "images/spotnet.gif";
		$backgroundColor = "123456";
		$text_color = "ffffff";
		$ttfFont = "images/ttf/Arialbd.TTF";
		$fontSize = 12;
		
		$tplHelper = $this->getTplHelper(array());
		
		// Create image
		$img = imagecreatetruecolor(256, 160);

		// Set alphablending to on
		imagealphablending($img, true);

		// Draw a square
		imagefilledrectangle($img, 5, 5, 251, 155, $this->colorHex($img, $backgroundColor));

		// Load and show the background image
		$bg = imagecreatefromgif($backgroundImage);
		list($width, $height, $type, $attr) = getimagesize($backgroundImage);
		imagecopymerge($img, $bg, 128-($width/2), 80-($height/2), 0, 0, $width, $height, 30);
		imagedestroy($bg);
		
		// Add some usefull text
		$text = "Totaal aantal spots: " . $this->_db->getSpotCount('');
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 128-($width/2), 25, $this->colorHex($img, $text_color), $ttfFont, $text);

		$text = "Aantal nieuwe spots: " . $tplHelper->getNewCountForFilter('');
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 128-($width/2), 45, $this->colorHex($img, $text_color), $ttfFont, $text);
		
		$text = "Laatste update:";
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 128-($width/2), 115+$fontSize, $this->colorHex($img, $text_color), $ttfFont, $text);
		
		$nntp_hdr_settings = $this->_settings->get('nntp_hdr');
		$text = $tplHelper->formatDate($this->_db->getLastUpdate($nntp_hdr_settings['host']), 'lastupdate');
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 128-($width/2), 135+$fontSize, $this->colorHex($img, $text_color), $ttfFont, $text);

		// Headers
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