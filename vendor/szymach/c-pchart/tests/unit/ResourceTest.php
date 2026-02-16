<?php

declare(strict_types=1);

namespace Tests\CpChart\Unit;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use Exception;
use Tests\CpChart\Support\UnitTester;

final class ResourceTest extends Unit
{
    protected UnitTester $tester;

    public function testInvalidResourceLoading(): void
    {
        $data = new Data();
        $this->tester->expectThrowable(
            Exception::class,
            function () use ($data) {
                $data->loadPalette('nonExistantPalette');
            }
        );

        $image = new Image(700, 230, $data);

        $this->tester->expectThrowable(
            Exception::class,
            function () use ($image) {
                $image->setResourcePath('nonExistantDirectory');
            }
        );
        $this->tester->expectThrowable(
            Exception::class,
            function () use ($image) {
                $image->setFontProperties(['FontName' => 'nonExistantFont']);
            }
        );
        $this->tester->expectThrowable(
            Exception::class,
            function () use ($image) {
                $image->getLegendSize(['Font' => 'nonExistantFont']);
            }
        );
    }

    public function testValidPaletteLoading(): void
    {
        $data = new Data();
        $data->loadPalette(sprintf('%s/../_data/test_palette.txt', __DIR__), true);

        $image = new Image(700, 230, $data);
        $firstCoordinates = [[40, 80], [280, 60], [340, 166], [590, 120]];
        $fistSplineSettings = ['R' => 255, 'G' => 255, 'B' => 255, 'ShowControl' => true];
        $image->drawSpline($firstCoordinates, $fistSplineSettings);
        $filename = $this->tester->getOutputPathForChart('drawSpline.png');
        $image->render($filename);
        $this->tester->seeFileFound($filename);
    }

    public function testInvalidPaletteLoading(): void
    {
        $data = new Data();
        $this->tester->expectThrowable(
            Exception::class,
            function () use ($data) {
                $data->loadPalette(sprintf('%s/../_data/non_existant_palette', __DIR__), true);
            }
        );
    }
}
