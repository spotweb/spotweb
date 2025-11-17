<?php

declare(strict_types=1);

namespace Tests\CpChart\Unit;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use Tests\CpChart\Support\UnitTester;

final class SplineTest extends Unit
{
    protected UnitTester $tester;

    public function testChartRender(): void
    {
        $data = new Data();
        $data->addPoints([], 'Serie1');

        $image = new Image(700, 230, $data);
        $image->setShadow(
            true,
            ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 20]
        );
        $firstCoordinates = [[40, 80], [280, 60], [340, 166], [590, 120]];
        $fistSplineSettings = ['R' => 255, 'G' => 255, 'B' => 255, 'ShowControl' => true];
        $image->drawSpline($firstCoordinates, $fistSplineSettings);
        $secondCoordinates = [[250, 50], [250, 180], [350, 180], [350, 50]];
        $secondSplineSettings = [
            'R' => 255,
            'G' => 255,
            'B' => 255,
            'ShowControl' => true,
            'Ticks' => 4
        ];
        $image->drawSpline($secondCoordinates, $secondSplineSettings);
        $filename = $this->tester->getOutputPathForChart('drawSpline.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
