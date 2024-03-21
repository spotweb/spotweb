<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Config;

use PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration;
use PhpCoveralls\Tests\ProjectTestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 *
 * @internal
 */
final class ConfigurationTest extends ProjectTestCase
{
    /**
     * @var Configuration
     */
    private $object;

    // hasRepoToken()
    // getRepoToken()

    /**
     * @test
     */
    public function shouldNotHaveRepoTokenOnConstruction()
    {
        self::assertFalse($this->object->hasRepoToken());
        self::assertNull($this->object->getRepoToken());
    }

    // hasServiceName()
    // getServiceName()

    /**
     * @test
     */
    public function shouldNotHaveServiceNameOnConstruction()
    {
        self::assertFalse($this->object->hasServiceName());
        self::assertNull($this->object->getServiceName());
    }

    // getCloverXmlPaths()

    /**
     * @test
     */
    public function shouldHaveEmptyCloverXmlPathsOnConstruction()
    {
        self::assertEmpty($this->object->getCloverXmlPaths());
    }

    // getRootDir()

    /**
     * @test
     */
    public function shouldNotRootDirOnConstruction()
    {
        self::assertNull($this->object->getRootDir());
    }

    // getJsonPath()

    /**
     * @test
     */
    public function shouldNotHaveJsonPathOnConstruction()
    {
        self::assertNull($this->object->getJsonPath());
    }

    // isDryRun()

    /**
     * @test
     */
    public function shouldBeDryRunOnConstruction()
    {
        self::assertTrue($this->object->isDryRun());
    }

    // isExcludeNoStatements()

    /**
     * @test
     */
    public function shouldNotBeExcludeNotStatementsOnConstruction()
    {
        self::assertFalse($this->object->isExcludeNoStatements());
    }

    // isVerbose

    /**
     * @test
     */
    public function shouldNotBeVerboseOnConstruction()
    {
        self::assertFalse($this->object->isVerbose());
    }

    // getEnv()

    /**
     * @test
     */
    public function shouldBeProdEnvOnConstruction()
    {
        self::assertSame('prod', $this->object->getEnv());
    }

    // isTestEnv()

    /**
     * @test
     */
    public function shouldBeTestEnv()
    {
        $expected = 'test';

        $this->object->setEnv($expected);

        self::assertSame($expected, $this->object->getEnv());
        self::assertTrue($this->object->isTestEnv());
        self::assertFalse($this->object->isDevEnv());
        self::assertFalse($this->object->isProdEnv());
    }

    // isDevEnv()

    /**
     * @test
     */
    public function shouldBeDevEnv()
    {
        $expected = 'dev';

        $this->object->setEnv($expected);

        self::assertSame($expected, $this->object->getEnv());
        self::assertFalse($this->object->isTestEnv());
        self::assertTrue($this->object->isDevEnv());
        self::assertFalse($this->object->isProdEnv());
    }

    // isProdEnv()

    /**
     * @test
     */
    public function shouldBeProdEnv()
    {
        $expected = 'prod';

        $this->object->setEnv($expected);

        self::assertSame($expected, $this->object->getEnv());
        self::assertFalse($this->object->isTestEnv());
        self::assertFalse($this->object->isDevEnv());
        self::assertTrue($this->object->isProdEnv());
    }

    // setEntryPoint

    /**
     * @test
     */
    public function shouldSetEntryPoint()
    {
        $expected = 'http://entry_point';

        $same = $this->object->setEntryPoint($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getEntryPoint());
    }

    // setRootDir()

    /**
     * @test
     */
    public function shouldSetRootDir()
    {
        $expected = '/root';

        $same = $this->object->setRootDir($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getRootDir());
    }

    // setRepoToken()

    /**
     * @test
     */
    public function shouldSetRepoToken()
    {
        $expected = 'token';

        $same = $this->object->setRepoToken($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getRepoToken());
    }

    // setServiceName()

    /**
     * @test
     */
    public function shouldSetServiceName()
    {
        $expected = 'travis-ci';

        $same = $this->object->setServiceName($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getServiceName());
    }

    // setCloverXmlPaths()

    /**
     * @test
     */
    public function shouldSetCloverXmlPaths()
    {
        $expected = ['/path/to/clover.xml'];

        $same = $this->object->setCloverXmlPaths($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getCloverXmlPaths());
    }

    // addCloverXmlPath()

    /**
     * @test
     */
    public function shouldAddCloverXmlPath()
    {
        $expected = '/path/to/clover.xml';

        $same = $this->object->addCloverXmlPath($expected);

        self::assertSame($same, $this->object);
        self::assertSame([$expected], $this->object->getCloverXmlPaths());
    }

    // setJsonPath()

    /**
     * @test
     */
    public function shouldSetJsonPath()
    {
        $expected = '/path/to/coveralls-upload.json';

        $same = $this->object->setJsonPath($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getJsonPath());
    }

    // setDryRun()

    /**
     * @test
     */
    public function shouldSetDryRunFalse()
    {
        $expected = false;

        $same = $this->object->setDryRun($expected);

        self::assertSame($same, $this->object);
        self::assertFalse($this->object->isDryRun());
    }

    /**
     * @test
     */
    public function shouldSetDryRunTrue()
    {
        $expected = true;

        $same = $this->object->setDryRun($expected);

        self::assertSame($same, $this->object);
        self::assertTrue($this->object->isDryRun());
    }

    // setExcludeNoStatements()

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsFalse()
    {
        $expected = false;

        $same = $this->object->setExcludeNoStatements($expected);

        self::assertSame($same, $this->object);
        self::assertFalse($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrue()
    {
        $expected = true;

        $same = $this->object->setExcludeNoStatements($expected);

        self::assertSame($same, $this->object);
        self::assertTrue($this->object->isExcludeNoStatements());
    }

    // setExcludeNoStatementsUnlessFalse()

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsFalseUnlessFalse()
    {
        $expected = false;

        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        self::assertSame($same, $this->object);
        self::assertFalse($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrueUnlessFalse()
    {
        $expected = true;

        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        self::assertSame($same, $this->object);
        self::assertTrue($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrueIfFalsePassedAndIfTrueWasSet()
    {
        $expected = false;

        $same = $this->object->setExcludeNoStatements(true);
        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        self::assertSame($same, $this->object);
        self::assertTrue($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrueIfTruePassedAndIfTrueWasSet()
    {
        $expected = true;

        $same = $this->object->setExcludeNoStatements(true);
        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        self::assertSame($same, $this->object);
        self::assertTrue($this->object->isExcludeNoStatements());
    }

    // setVerbose()

    /**
     * @test
     */
    public function shouldSetVerboseFalse()
    {
        $expected = false;

        $same = $this->object->setVerbose($expected);

        self::assertSame($same, $this->object);
        self::assertFalse($this->object->isVerbose());
    }

    /**
     * @test
     */
    public function shouldSetVerboseTrue()
    {
        $expected = true;

        $same = $this->object->setVerbose($expected);

        self::assertSame($same, $this->object);
        self::assertTrue($this->object->isVerbose());
    }

    // setEnv()

    /**
     * @test
     */
    public function shouldSetEnv()
    {
        $expected = 'myenv';

        $same = $this->object->setEnv($expected);

        self::assertSame($same, $this->object);
        self::assertSame($expected, $this->object->getEnv());
    }

    protected function legacySetUp()
    {
        $this->object = new Configuration();
    }
}
