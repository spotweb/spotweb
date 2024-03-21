<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Entity;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile;
use PhpCoveralls\Tests\ProjectTestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 *
 * @internal
 */
final class SourceFileTest extends ProjectTestCase
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var SourceFile
     */
    private $object;

    // getName()

    /**
     * @test
     */
    public function shouldHaveNameOnConstruction()
    {
        self::assertSame($this->filename, $this->object->getName());
    }

    // getSource()

    /**
     * @test
     */
    public function shouldHaveSourceOnConstruction()
    {
        $expected = trim(file_get_contents($this->path));

        self::assertSame($expected, $this->object->getSource());
    }

    // getCoverage()

    /**
     * @test
     */
    public function shouldHaveNullCoverageOnConstruction()
    {
        $expected = array_fill(0, 9, null);

        self::assertSame($expected, $this->object->getCoverage());
    }

    // getPath()

    /**
     * @test
     */
    public function shouldHavePathOnConstruction()
    {
        $this->assertSamePath($this->path, $this->object->getPath());
    }

    // getFileLines()

    /**
     * @test
     */
    public function shouldHaveFileLinesOnConstruction()
    {
        self::assertSame(9, $this->object->getFileLines());
    }

    // toArray()

    /**
     * @test
     */
    public function shouldConvertToArray()
    {
        $expected = [
            'name' => $this->filename,
            'source' => trim(file_get_contents($this->path)),
            'coverage' => array_fill(0, 9, null),
        ];

        self::assertSame($expected, $this->object->toArray());
        self::assertSame(json_encode($expected), (string) $this->object);
    }

    // addCoverage()

    /**
     * @test
     */
    public function shouldAddCoverage()
    {
        $this->object->addCoverage(5, 1);

        $expected = array_fill(0, 9, null);
        $expected[5] = 1;

        self::assertSame($expected, $this->object->getCoverage());
    }

    // getMetrics()
    // reportLineCoverage()

    /**
     * @test
     */
    public function shouldReportLineCoverage0PercentWithoutAddingCoverage()
    {
        $metrics = $this->object->getMetrics();

        self::assertSame(0, $metrics->getStatements());
        self::assertSame(0, $metrics->getCoveredStatements());
        self::assertSame(0, $metrics->getLineCoverage());
        self::assertSame(0, $this->object->reportLineCoverage());
    }

    /**
     * @test
     */
    public function shouldReportLineCoverage100PercentAfterAddingCoverage()
    {
        $this->object->addCoverage(6, 1);

        $metrics = $this->object->getMetrics();

        self::assertSame(1, $metrics->getStatements());
        self::assertSame(1, $metrics->getCoveredStatements());
        self::assertSame(100, $metrics->getLineCoverage());
        self::assertSame(100, $this->object->reportLineCoverage());
    }

    protected function legacySetUp()
    {
        $this->setUpDir(realpath(__DIR__ . '/../../..'));

        $this->filename = 'test.php';
        $this->path = $this->srcDir . \DIRECTORY_SEPARATOR . $this->filename;

        $this->object = new SourceFile($this->path, $this->filename);
    }
}
