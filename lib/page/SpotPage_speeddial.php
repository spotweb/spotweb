<?php
class SpotPage_speeddial extends SpotPage_Abs {

	function render() {
		$backgroundImage = "images/spotnet.gif";
		$backgroundColor = "123456";
		$text_color = "ffffff";
		$ttfFont = "images/ttf/Arialbd.TTF";
		$fontSize = 24;

		$tplHelper = $this->getTplHelper(array());
		$spotImage = new SpotImage();

		// Create image
		$img = imagecreatetruecolor(512, 320);

		// Set alphablending to on
		imagealphablending($img, true);

		// Draw a square
		imagefilledrectangle($img, 8, 8, 504, 312, $spotImage->colorHex($img, $backgroundColor));

		// Load and show the background image
		$bg = imagecreatefromgif($backgroundImage);
		list($width, $height, $type, $attr) = getimagesize($backgroundImage);
		imagecopymerge($img, $bg, 256-($width/2), 160-($height/2), 0, 0, $width, $height, 30);
		imagedestroy($bg);

		// Add some usefull text
		$text = "Totaal aantal spots: " . $this->_db->getSpotCount('');
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 50, $spotImage->colorHex($img, $text_color), $ttfFont, $text);

		$count = $tplHelper->getNewCountForFilter('');
		if (!$count) { $count = 0; }
		$text = "Aantal nieuwe spots: " . $count;
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 90, $spotImage->colorHex($img, $text_color), $ttfFont, $text);

		$text = "Laatste update:";
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 230+$fontSize, $spotImage->colorHex($img, $text_color), $ttfFont, $text);

		$nntp_hdr_settings = $this->_settings->get('nntp_hdr');
		$text = $tplHelper->formatDate($this->_db->getLastUpdate($nntp_hdr_settings['host']), 'lastupdate');
		if (!$text) { $text = "onbekend"; }
		$bbox = imagettfbbox($fontSize, 0, $ttfFont, $text); $width = abs($bbox[2]);
		imagettftext($img, $fontSize, 0, 256-($width/2), 270+$fontSize, $spotImage->colorHex($img, $text_color), $ttfFont, $text);

		// Headers
		$this->sendExpireHeaders(true);
		header('Content-Type: image/jpeg');

		// Image output
		imagejpeg($img);
		imagedestroy($img);
	} # render

} # class SpotPage_speeddial