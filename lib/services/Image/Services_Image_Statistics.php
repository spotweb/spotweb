<?php

class Services_Image_Statistics {
	private $_spotStatistics;
	private $_oldestSpotAge;

	/*
	 * Constructor
	 */
	function __construct(SpotStatistics $spotStatistics, $oldestSpotAge) {
		SpotTranslation::initialize($language);

		$this->_spotStatistics = $spotStatistics; 
		$this->_oldestSpotAge = $oldestSpotAge;
	} # ctor

	/*
	 * Create several statistics images
	 */
	function createStatistics($graph, $limit, $lastUpdate, $language) {
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
			case 'spotsperhour'		: $prepData = $this->prepareData($this->_spotStatistics->getSpotCountPerHour($limit, $lastUpdate));
									  $legend = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23');
									  for ($x=0; $x<=23; $x++) { $dataSet[] = @$prepData[$x]; }
									  $graphicType = "bar";
									  break;
			case 'spotsperweekday'	: $prepData = $this->prepareData($this->_spotStatistics->getSpotCountPerWeekday($limit, $lastUpdate));
									  $legend = array(_("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"),_("Saturday"),_("Sunday"));
									  $dataSet = array(@$prepData[1],@$prepData[2],@$prepData[3],@$prepData[4],@$prepData[5],@$prepData[6],@$prepData[0]);
									  $graphicType = "bar";
									  break;
			case 'spotspermonth'	: $prepData = $this->prepareData($this->_spotStatistics->getSpotCountPerMonth($limit, $lastUpdate));
									  $legend = array(_("January"),_("February"),_("March"),_("April"),_("May"),_("June"),_("July"),_("August"),_("September"),_("October"),_("November"),_("December"));
									  for ($x=1; $x<=12; $x++) { $dataSet[] = @$prepData[$x]; }
									  $graphicType = "bar";
									  break;
			case 'spotspercategory'	: $prepData = $this->prepareData($this->_spotStatistics->getSpotCountPerCategory($limit, $lastUpdate));
									  $legend = array(_(SpotCategories::HeadCat2Desc(0)),_(SpotCategories::HeadCat2Desc(1)),_(SpotCategories::HeadCat2Desc(2)),_(SpotCategories::HeadCat2Desc(3)));
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

			$svc_ImageUtil = new Services_Image_Util();
			$dimensions = $svc_ImageUtil->getImageDimensions($imageString);
			return array('metadata' => $dimensions, 'content' => $imageString);
		} # img
	} # createStatistics


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

	
} # Services_Image_Statistics
