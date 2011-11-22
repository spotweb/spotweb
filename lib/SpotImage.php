<?php
class SpotImage {
	protected $_db;
	private $_oldestSpotAge = null;

	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

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
			case 200:	$text = _('Remote host sent bad data'); break;
			case 400:	$text = _('Bad request'); break;
			case 403:	$text = _('Permission denied from remote host'); break;
			case 404:	$text = _('File not found'); break;
			case 430:	$text = _('Article not found'); break;
			case 700:	$text = _('No response from remote host'); break;
			case 900:	$text = _('XML parse error'); break;
			case 901:	$text = _('No image provided'); break;
			default:	$text = _('Unknown error');
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
		return array('metadata' => $data['metadata'], 'expire' => true, 'content' => $imageString);
	} # createErrorImage

	function createStatistics($graph, $limit, $lastUpdate, $language) {
		SpotTranslation::initialize($language);
		$spotStatistics = new SpotStatistics($this->_db);
		include_once("images/pchart/pData.class.php");
		include_once("images/pchart/pDraw.class.php");
		include_once("images/pchart/pImage.class.php");

		$width = 800;
		$height = 500;
		$titleHeight = 20;
		$dataSet = array();
		$graphs = $this->getValidStatisticsGraphs();
		$limits = $this->getValidStatisticsLimits();

		switch ($graph) {
			case 'spotsperhour'		: $prepData = $this->prepareData($spotStatistics->getSpotCountPerHour($limit, $lastUpdate));
									  $legend = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23');
									  for ($x=0; $x<=23; $x++) { $dataSet[] = @$prepData[$x]; }
									  $graphicType = "bar";
									  break;
			case 'spotsperweekday'	: $prepData = $this->prepareData($spotStatistics->getSpotCountPerWeekday($limit, $lastUpdate));
									  $legend = array(_("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"),_("Saturday"),_("Sunday"));
									  $dataSet = array(@$prepData[1],@$prepData[2],@$prepData[3],@$prepData[4],@$prepData[5],@$prepData[6],@$prepData[0]);
									  $graphicType = "bar";
									  break;
			case 'spotspermonth'	: $prepData = $this->prepareData($spotStatistics->getSpotCountPerMonth($limit, $lastUpdate));
									  $legend = array(_("January"),_("February"),_("March"),_("April"),_("May"),_("June"),_("July"),_("August"),_("September"),_("October"),_("November"),_("December"));
									  for ($x=1; $x<=12; $x++) { $dataSet[] = @$prepData[$x]; }
									  $graphicType = "bar";
									  break;
			case 'spotspercategory'	: $prepData = $this->prepareData($spotStatistics->getSpotCountPerCategory($limit, $lastUpdate));
									  $legend = array(SpotCategories::HeadCat2Desc(0),SpotCategories::HeadCat2Desc(1),SpotCategories::HeadCat2Desc(2),SpotCategories::HeadCat2Desc(3));
									  for ($x=0; $x<=3; $x++) { $dataSet[] = @$prepData[$x]; }
									  $graphicType = "3Dpie";
									  break;
		} # switch
		array_walk($dataSet, create_function('& $item, $key', 'if ($item === NULL) $item = 0;'));

		$title = $graphs[$graph];
		if (!empty($limit)) {
			$title .= " (" . $limits[$limit] . ")";
		} # if

		$imgData = new pData();
		if ($graphicType == "bar") {
			$imgData->addPoints($dataSet,"data");
			$imgData->addPoints($legend,"legend");
			$imgData->setAbscissa("legend");
			$imgData->setPalette("data",array("R"=>0,"G"=>108,"B"=>171,"Alpha"=>100));

			$img = new pImage($width,$height,$imgData);

			$img->drawGradientArea(0,$titleHeight,$width,$height,DIRECTION_VERTICAL,array("StartR"=>200,"StartG"=>200,"StartB"=>200,"EndR"=>18,"EndG"=>52,"EndB"=>86,"Alpha"=>100));
			$img->drawGradientArea(0,0,$width,$titleHeight,DIRECTION_VERTICAL,array("StartR"=>18,"StartG"=>52,"StartB"=>86,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>100));

			$img->setFontProperties(array("FontName"=>"images/ttf/liberation-sans/LiberationSans-Bold.ttf","FontSize"=>10));
			$img->drawText($width/2,13,$title,array("Align"=>TEXT_ALIGN_MIDDLEMIDDLE,"R"=>255,"G"=>255,"B"=>255));

			$img->setFontProperties(array("R"=>255,"G"=>255,"B"=>255,"FontName"=>"images/ttf/liberation-sans/LiberationSans-Regular.ttf","FontSize"=>9));
			$img->setGraphArea(60,$titleHeight+20,$width-50,$height-30);
			$img->drawScale(array("GridR"=>200,"GridG"=>200,"GridB"=>200,"Mode"=>SCALE_MODE_START0));
			$img->drawBarChart(array("Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"Surrounding"=>10)); 
		} elseif ($graphicType == "3Dpie") {
			include_once("images/pchart/pPie.class.php");

			$imgData->addPoints($dataSet,"data");
			$imgData->addPoints($legend,"legend");
			$imgData->setAbscissa("legend");

			$img = new pImage($width,$height,$imgData,TRUE);
			$PieChart = new pPie($img,$imgData);

			$img->drawGradientArea(0,$titleHeight,$width,$height,DIRECTION_VERTICAL,array("StartR"=>200,"StartG"=>200,"StartB"=>200,"EndR"=>18,"EndG"=>52,"EndB"=>86,"Alpha"=>100));
			$img->drawGradientArea(0,0,$width,$titleHeight,DIRECTION_VERTICAL,array("StartR"=>18,"StartG"=>52,"StartB"=>86,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>100));

			$img->setFontProperties(array("FontName"=>"images/ttf/liberation-sans/LiberationSans-Bold.ttf","FontSize"=>10));
			$img->drawText($width/2,13,$title,array("Align"=>TEXT_ALIGN_MIDDLEMIDDLE,"R"=>255,"G"=>255,"B"=>255));

			$PieChart->setSliceColor(0,array("R"=>0,"G"=>108,"B"=>171));
			$PieChart->setSliceColor(1,array("R"=>205,"G"=>159,"B"=>0));
			$PieChart->setSliceColor(2,array("R"=>0,"G"=>171,"B"=>0));
			$PieChart->setSliceColor(3,array("R"=>171,"G"=>28,"B"=>0));

			$img->setFontProperties(array("FontName"=>"images/ttf/liberation-sans/LiberationSans-Regular.ttf","FontSize"=>9));
			$PieChart->draw3DPie($width/2,($height/2)+$titleHeight,array("Radius"=>($width/2)-100,"SecondPass"=>TRUE,"DrawLabels"=>TRUE,"WriteValues"=>TRUE,"Precision"=>2,"ValueR"=>0,"ValueG"=>0,"ValueB"=>0,"ValueAlpha"=>100,"SkewFactor"=>0.6,"LabelR"=>255,"LabelG"=>255,"LabelB"=>255,"LabelAlpha"=>100));
		} # if

		if (isset($img)) {
			ob_start();
			$img->render(NULL);
			$imageString = ob_get_clean();

			$data = $this->getImageInfoFromString($imageString);
			return array('metadata' => $data['metadata'], 'content' => $imageString);
		} # img
	} # createStatistics

	function createSpeedDial($totalSpots, $newSpots, $lastUpdate) {
		$img = $this->createDefaultSpotwebImage();
		$fontSize = 24;
		$angle = 0;

		$text = sprintf(_('Total spots: %d'), $totalSpots);
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 50, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		if (!$newSpots) { $newSpots = 0; }
		$text = sprintf(_('Total new spots: %d'), $newSpots);
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 90, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		$text = _('Last update:');
		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $text); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 230+$fontSize, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

		$bbox = imagettfbbox($fontSize, $angle, $img['font'], $lastUpdate); $width = abs($bbox[2]);
		imagettftext($img['resource'], $fontSize, $angle, 256-($width/2), 270+$fontSize, $this->colorHex($img['resource'], $img['fontColor']), $img['font'], $lastUpdate);

		ob_start();
		imagejpeg($img['resource']);
		$imageString = ob_get_clean();
		imagedestroy($img['resource']);

		$data = $this->getImageInfoFromString($imageString);
		return array('metadata' => $data['metadata'], 'expire' => true, 'content' => $imageString);
	} # createSpeedDial

	function createDefaultSpotwebImage() {
		$imageFile = 'images/spotnet.gif';
		$ttfFont = 'images/ttf/liberation-sans/LiberationSans-Bold.ttf';
		$fontColor = 'ffffff';

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

	function prepareData($data) {
		$preparedData = array();
		foreach ($data as $tmp) {
			$preparedData[(int) $tmp['data']] = (float) $tmp['amount'];
		} # foreach
		return $preparedData;
	} # prepareData

	function getValidStatisticsGraphs() {
		$graphs = array();
												$graphs['spotspercategory']	= _("Spots per category");
												$graphs['spotsperhour']		= _("Spots per hour");
												$graphs['spotsperweekday']	= _("Spots per weekday");
		if ($this->getOldestSpotAge() > 31) {	$graphs['spotspermonth']	= _("Spots per month"); }
		return $graphs;
	} # getValidStatisticsGraphs

	function getValidStatisticsLimits() {
		$limits = array();
		if ($this->getOldestSpotAge() > 365) {	$limits['']			= _("Everything"); }
		if ($this->getOldestSpotAge() > 31) {	$limits['year']		= _("last year"); }
		if ($this->getOldestSpotAge() > 7) {	$limits['month']	= _("last month"); }
		if ($this->getOldestSpotAge() > 1) {	$limits['week']		= _("last week"); }
												$limits['day']		= _("last 24 hours");
		return $limits;
	} # getValidStatisticsLimits

	function getOldestSpotAge() {
		if ($this->_oldestSpotAge == null) {
			$this->_oldestSpotAge = round((time()- $this->_db->getOldestSpotTimestamp()) / 60 / 60 / 24);
		} # if
		return $this->_oldestSpotAge;
	} # getOldestSpotAge

} # class SpotImage
