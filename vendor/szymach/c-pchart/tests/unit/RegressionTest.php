<?php

namespace Test\CpChart;

use Codeception\Test\Unit;
use CpChart\Chart\Radar;
use CpChart\Data;
use CpChart\Image;
use Test\CpChart\UnitTester;

use const RADAR_LAYOUT_CIRCLE;

final class RegressionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testNoDeprecationThrownOnFractionalValues()
    {
        $values = [9, 9.29, 10, 10, 8.5];

        $width = 400;
        $height = 400;

        $data = new Data();
        $data->addPoints($values, 'Score');
        $data->setSerieDescription('Score', '222');

        $data->addPoints(['111', '222', '333', '444', '555'], 'Labels');
        $data->setAbscissa('Labels');
        $data->setPalette('Score', ['R' => 157, 'G' => 96, 'B' => 22]);

        $image = new Image($width, $height, $data);
        $image->setFontProperties(['FontSize' => 10, 'R' => 80, 'G' => 80, 'B' => 80]);

        $image->setShadow(
            true,
            ['X' => 2, 'Y' => 2, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]
        );

        $radar = new Radar;
        $image->setGraphArea(0, 0, $width, $height);
        $options = [
            'DrawPoly' => true,
            'WriteValues' => true,
            'ValueFontSize' => 7,
            'Layout' => RADAR_LAYOUT_CIRCLE,
            'BackgroundGradient' => [
                'StartR' => 255,
                'StartG' => 255,
                'StartB' => 255,
                'StartAlpha' => 100,
                'EndR' => 207,
                'EndG' => 227,
                'EndB' => 125,
                'EndAlpha' => 50
            ]
        ];

        $radar->drawRadar($image, $data, $options);
        $filename = $this->tester->getOutputPathForChart('drawRadarFractional.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
