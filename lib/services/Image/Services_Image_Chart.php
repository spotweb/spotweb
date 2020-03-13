<?php

require __DIR__.'/../../../vendor/pchart/class/pData.class.php';
require __DIR__.'/../../../vendor/pchart/class/pDraw.class.php';
require __DIR__.'/../../../vendor/pchart/class/pImage.class.php';
require __DIR__.'/../../../vendor/pchart/class/pPie.class.php';

class Services_Image_Chart
{
    private $_svcImageUtil;
    private $_strTtfPath;

    /*
     * Constructor
     */
    public function __construct()
    {
        $this->_svcImageUtil = new Services_Image_Util();
        $this->_strTtfPath = __DIR__.'/../../../images/ttf';
    }

    // ctor

    /*
     * Render a chart images
     */
    public function renderChart($chartType, $title, $prepData, $legend)
    {
        $width = 800;
        $height = 500;
        $titleHeight = 20;

        /*
         * Create a dataset we can use
         */
        $dataSet = array_values($prepData);

        $imgData = new pData();
        if ($chartType == 'bar') {
            $imgData->addPoints($dataSet, 'data');
            $imgData->addPoints($legend, 'legend');
            $imgData->setAbscissa('legend');
            $imgData->setPalette('data', ['R'=>0, 'G'=>108, 'B'=>171, 'Alpha'=>100]);

            $img = new pImage($width, $height, $imgData);

            $img->drawGradientArea(0, $titleHeight, $width, $height, DIRECTION_VERTICAL, ['StartR'=>200, 'StartG'=>200, 'StartB'=>200, 'EndR'=>18, 'EndG'=>52, 'EndB'=>86, 'Alpha'=>100]);
            $img->drawGradientArea(0, 0, $width, $titleHeight, DIRECTION_VERTICAL, ['StartR'=>18, 'StartG'=>52, 'StartB'=>86, 'EndR'=>50, 'EndG'=>50, 'EndB'=>50, 'Alpha'=>100]);

            $img->setFontProperties(['FontName'=>$this->_strTtfPath.'/liberation-sans/LiberationSans-Bold.ttf', 'FontSize'=>10]);
            $img->drawText($width / 2, 13, $title, ['Align'=>TEXT_ALIGN_MIDDLEMIDDLE, 'R'=>255, 'G'=>255, 'B'=>255]);

            $img->setFontProperties(['R'=>255, 'G'=>255, 'B'=>255, 'FontName'=>$this->_strTtfPath.'/liberation-sans/LiberationSans-Regular.ttf', 'FontSize'=>9]);
            $img->setGraphArea(60, $titleHeight + 20, $width - 50, $height - 30);
            $img->drawScale(['GridR'=>200, 'GridG'=>200, 'GridB'=>200, 'Mode'=>SCALE_MODE_START0]);
            $img->drawBarChart(['Gradient'=>true, 'GradientMode'=>GRADIENT_EFFECT_CAN, 'DisplayPos'=>LABEL_POS_INSIDE, 'DisplayValues'=>true, 'Surrounding'=>10]);
        } elseif ($chartType == '3Dpie') {
            $imgData->addPoints($dataSet, 'data');
            $imgData->addPoints($legend, 'legend');
            $imgData->setAbscissa('legend');

            $img = new pImage($width, $height, $imgData, true);
            $PieChart = new pPie($img, $imgData);

            $img->drawGradientArea(0, $titleHeight, $width, $height, DIRECTION_VERTICAL, ['StartR'=>200, 'StartG'=>200, 'StartB'=>200, 'EndR'=>18, 'EndG'=>52, 'EndB'=>86, 'Alpha'=>100]);
            $img->drawGradientArea(0, 0, $width, $titleHeight, DIRECTION_VERTICAL, ['StartR'=>18, 'StartG'=>52, 'StartB'=>86, 'EndR'=>50, 'EndG'=>50, 'EndB'=>50, 'Alpha'=>100]);

            $img->setFontProperties(['FontName'=>$this->_strTtfPath.'/liberation-sans/LiberationSans-Bold.ttf', 'FontSize'=>10]);
            $img->drawText($width / 2, 13, $title, ['Align'=>TEXT_ALIGN_MIDDLEMIDDLE, 'R'=>255, 'G'=>255, 'B'=>255]);

            $PieChart->setSliceColor(0, ['R'=>0, 'G'=>108, 'B'=>171]);
            $PieChart->setSliceColor(1, ['R'=>205, 'G'=>159, 'B'=>0]);
            $PieChart->setSliceColor(2, ['R'=>0, 'G'=>171, 'B'=>0]);
            $PieChart->setSliceColor(3, ['R'=>171, 'G'=>28, 'B'=>0]);

            $img->setFontProperties(['FontName'=>$this->_strTtfPath.'/liberation-sans/LiberationSans-Regular.ttf', 'FontSize'=>9]);
            $PieChart->draw3DPie($width / 2, ($height / 2) + $titleHeight, ['Radius'=>($width / 2) - 100, 'SecondPass'=>true, 'DrawLabels'=>true, 'WriteValues'=>true, 'Precision'=>2, 'ValueR'=>0, 'ValueG'=>0, 'ValueB'=>0, 'ValueAlpha'=>100, 'SkewFactor'=>0.6, 'LabelR'=>255, 'LabelG'=>255, 'LabelB'=>255, 'LabelAlpha'=>100]);
        } // if

        if (isset($img)) {
            ob_start();
            $img->render(null);
            $imageString = ob_get_clean();

            $dimensions = $this->_svcImageUtil->getImageDimensions($imageString);

            return ['metadata' => $dimensions, 'content' => $imageString];
        } else {
            return false;
        } // else
    }

    // renderChart
} // Services_Image_Chart
