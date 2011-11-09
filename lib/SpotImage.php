<?php
class SpotImage {

	function createErrorImage($errcode) {
		$img = $this->createDefaultSpotwebImage();
		$fontSize = 30;
		$angle = 0;

		# Headertext
		$text = ($errcode < 900) ? "ERROR " . $errcode : "ERROR";
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text);
		$txtwidth = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($txtwidth/2), 50, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		# error info
		switch ($errcode) {
			case 403:	$text = "URL mag niet worden geopend"; break;
			case 404:	$text = "Bestand niet gevonden"; break;
			case 430:	$text = "Artikel niet gevonden"; break;
			case 900:	$text = "XML parse error"; break;
			case 901:	$text = "Image is corrupt"; break;
			default:	$text = "Onbekende fout";
		} # switch

		$fontSize = 20;
		$bbox = imagettfbbox ($fontSize, $angle, $img['font'], $text);
		$txtwidth = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($txtwidth/2), 300, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);		

		ob_start();
		imagejpeg($img['resource']);
		$imageString = ob_get_clean();
		imagedestroy($img['resource']);

		$data = $this->getImageInfoFromString($imageString);
		return array('metadata' => $data['metadata'], 'isErrorImage' => true, 'content' => $imageString);
	} # createErrorImage

	function createSpeedDial($totalSpots, $newSpots, $lastUpdate) {
		$img = $this->createDefaultSpotwebImage();
		$fontSize = 24;
		$angle = 0;

		$text = "Totaal aantal spots: " . $totalSpots;
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 50, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		if (!$newSpots) { $newSpots = 0; }
		$text = "Aantal nieuwe spots: " . $newSpots;
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 90, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		$text = "Laatste update:";
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 230+$fontSize, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $lastUpdate); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 270+$fontSize, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $lastUpdate);

		ob_start();
		imagejpeg($img['resource']);
		$imageString = ob_get_clean();
		imagedestroy($img['resource']);

		$data = $this->getImageInfoFromString($imageString);
		return array('metadata' => $data['metadata'], 'isErrorImage' => true, 'content' => $imageString);
	} # createSpeedDial

	function createDefaultSpotwebImage() {
		$imageFile = 'images/spotnet.gif';
		$ttfFont = 'images/ttf/Arialbd.TTF';
		$fontColor = "ffffff";

		// Create image
		$img = imagecreatetruecolor(512, 320);

		// Set alphablending to on
		imagealphablending($img, true);

		// Draw a square
		imagefilledrectangle($img, 8, 8, 504, 312, $this->colorHex($img, '123456'));

		// Load and show the background image
		$bg = imagecreatefromgif($imageFile);
		list($width, $height, $type, $attr) = getimagesize($imageFile);
		imagecopymerge($img, $bg, 256-($width/2), 160-($height/2), 0, 0, $width, $height, 30);
		imagedestroy($bg);

		return array('resource' => $img, 'font' => $ttfFont, 'fontColor' => $fontColor);
	} # createDefaultSpotwebImage

	function getImageInfoFromString($imageString) {
		# PHP image functies willen bestanden inlezen
		$temp_file = tempnam(sys_get_temp_dir(), 'SpotWeb_');
		$fileHandler = fopen($temp_file, 'w');
		fwrite($fileHandler, $imageString);
		fclose($fileHandler);

		# metadata uitlezen
		if (filesize($temp_file) < 15 || !list($imgwidth, $imgheight, $imgtype, $attr) = getimagesize($temp_file)) {
			unlink($temp_file);
			return false;
		} # if

		# BMP omzetten naar JPEG
		if ($imgtype == 6) {
			if (!is_resource($tmpimg = $this->imagecreatefrombmp($temp_file))) {
				unlink($temp_file);
				return false;
			} # if
			ob_start();
			imagejpeg($tmpimg);
			$imageString = ob_get_clean();
			imagedestroy($tmpimg);
			$imgtype = 2;
		} # if

		# temp bestand verwijderen
		unlink($temp_file);

		# Sommige plaatjes komen door bovenstaande tests en zijn toch corrupt
		if ($imgwidth < 0 || $imgheight < 0) {
			return false;
		} # if

		$metadata = array('width' => $imgwidth,
						  'height' => $imgheight,
						  'imagetype' => $imgtype);
		return array('metadata' => $metadata, 'content' => $imageString);
	} # getImageInfoFromString

	function imagecreatefrombmp($filename) {
		$tmp = tempnam(sys_get_temp_dir(), 'SpotWeb_');

		# converteer naar GD
		if($this->bmp2gd($filename, $tmp)) {
			$img = imagecreatefromgd($tmp);
			unlink($tmp);
			return $img;
		} # if

		unlink($tmp);
		return false;
	} # imagecreatefrombmp

	# GD kan standaard niet met BMP files omgaan
	# tot die tijd moeten we deze functie toepassen
	# http://www.phpro.org/examples/Convert-BMP-to-JPG.html
	function bmp2gd($src, $dest = false) {
		/*** try to open the file for reading ***/
		if(!($src_f = fopen($src, "rb"))) {
			return false;
		} # if

		/*** try to open the destination file for writing ***/
		if(!($dest_f = fopen($dest, "wb"))) {
			return false;
		} # if

		/*** grab the header ***/
		$header = unpack("vtype/Vsize/v2reserved/Voffset", fread( $src_f, 14));

		/*** grab the rest of the image ***/
		$info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant",
		fread($src_f, 40));

		/*** extract the header and info into variables ***/
		extract($info);
		extract($header);

		/*** check for BMP signature ***/
		if($type != 0x4D42) {
			return false;
		} # if

		/*** set the pallete ***/
		$palette_size = $offset - 54;
		$ncolor = $palette_size / 4;
		$gd_header = "";

		/*** true-color vs. palette ***/
		$gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
		$gd_header .= pack("n2", $width, $height);
		$gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
		if($palette_size) {
			$gd_header .= pack("n", $ncolor);
		} # if

		/*** we do not allow transparency ***/
		$gd_header .= "\xFF\xFF\xFF\xFF";

		/*** write the destination headers ***/
		fwrite($dest_f, $gd_header);

		/*** if we have a valid palette ***/
		if($palette_size) {
			/*** read the palette ***/
			$palette = fread($src_f, $palette_size);
			/*** begin the gd palette ***/
			$gd_palette = "";
			$j = 0;
			/*** loop of the palette ***/
			while($j < $palette_size) {
				$b = $palette{$j++};
				$g = $palette{$j++};
				$r = $palette{$j++};
				$a = $palette{$j++};
				/*** assemble the gd palette ***/
				$gd_palette .= "$r$g$b$a";
			}
			/*** finish the palette ***/
			$gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
			/*** write the gd palette ***/
			fwrite($dest_f, $gd_palette);
		} # if

		/*** scan line size and alignment ***/
		$scan_line_size = (($bits * $width) + 7) >> 3;
		$scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;

		/*** this is where the work is done ***/
		for($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
			/*** create scan lines starting from bottom ***/
			fseek($src_f, $offset + (($scan_line_size + $scan_line_align) * $l));
			$scan_line = fread($src_f, $scan_line_size);
			if($bits == 24) {
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$b = $scan_line{$j++};
					$g = $scan_line{$j++};
					$r = $scan_line{$j++};
					$gd_scan_line .= "\x00$r$g$b";
				}
			} elseif($bits == 8) {
				$gd_scan_line = $scan_line;
			} elseif($bits == 4) {
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$byte = ord($scan_line{$j++});
					$p1 = chr($byte >> 4);
					$p2 = chr($byte & 0x0F);
					$gd_scan_line .= "$p1$p2";
				} # while
				$gd_scan_line = substr($gd_scan_line, 0, $width);
			} elseif($bits == 1) {
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$byte = ord($scan_line{$j++});
					$p1 = chr((int) (($byte & 0x80) != 0));
					$p2 = chr((int) (($byte & 0x40) != 0));
					$p3 = chr((int) (($byte & 0x20) != 0));
					$p4 = chr((int) (($byte & 0x10) != 0));
					$p5 = chr((int) (($byte & 0x08) != 0));
					$p6 = chr((int) (($byte & 0x04) != 0));
					$p7 = chr((int) (($byte & 0x02) != 0));
					$p8 = chr((int) (($byte & 0x01) != 0));
					$gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
				}
				/*** put the gd scan lines together ***/
				$gd_scan_line = substr($gd_scan_line, 0, $width);
			} else {
				return false;
			} # else
			/*** write the gd scan lines ***/
			fwrite($dest_f, $gd_scan_line);
		} # for

		/*** close the source file ***/
		fclose($src_f);
		/*** close the destination file ***/
		fclose($dest_f);

		return true;
	} # bmp2gd

	function colorHex($img, $hexColorString) {
		$r = hexdec(substr($hexColorString, 0, 2));
		$g = hexdec(substr($hexColorString, 2, 2));
		$b = hexdec(substr($hexColorString, 4, 2));
		return ImageColorAllocate($img, $r, $g, $b);
	} # colorHex

} # class SpotImage
