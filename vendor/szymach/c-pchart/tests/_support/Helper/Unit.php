<?php

declare(strict_types=1);

namespace Tests\CpChart\Support\Helper;

use Codeception\Module;
use Codeception\Module\Filesystem;

final class Unit extends Module
{
    /**
     * @param array<string, mixed> $settings
     */
    public function _beforeSuite(array $settings = []): void
    {
        $chartDir = $this->getChartDirectoryPath();
        if (is_dir($chartDir) === false) {
            mkdir($chartDir);
        }

        $this->clearOutputDirectory();
    }

    public function _afterSuite(): void
    {
        $this->clearOutputDirectory();
    }

    private function clearOutputDirectory(): void
    {
        $this->getFileSystem()->cleanDir($this->getChartDirectoryPath());
    }

    private function getChartDirectoryPath(): string
    {
        return sprintf(__DIR__ . '/../../_output/charts');
    }

    private function getFileSystem(): Filesystem
    {
        /** @var Filesystem $module */
        $module = $this->getModule('Filesystem');
        return $module;
    }
}
