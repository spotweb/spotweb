<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Collector;

use PhpCoveralls\Bundle\CoverallsBundle\Collector\CiEnvVarsCollector;
use PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration;
use PhpCoveralls\Tests\ProjectTestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Collector\CiEnvVarsCollector
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 *
 * @internal
 */
final class CiEnvVarsCollectorTest extends ProjectTestCase
{
    // collect()

    /**
     * @test
     */
    public function shouldCollectTravisCiEnvVars()
    {
        $serviceName = 'travis-ci';
        $serviceJobId = '1.1';
        $serviceBuildNumber = '123456';

        $env = [];
        $env['TRAVIS'] = true;
        $env['TRAVIS_JOB_ID'] = $serviceJobId;
        $env['TRAVIS_BUILD_NUMBER'] = $serviceBuildNumber;

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_JOB_ID', $actual);
        self::assertSame($serviceJobId, $actual['CI_JOB_ID']);

        self::assertArrayHasKey('CI_BUILD_NUMBER', $actual);
        self::assertSame($serviceBuildNumber, $actual['CI_BUILD_NUMBER']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectTravisProEnvVars()
    {
        $serviceName = 'travis-pro';
        $serviceJobId = '1.2';
        $serviceBuildNumber = '12345';
        $repoToken = 'your_token';

        $env = [];
        $env['TRAVIS'] = true;
        $env['TRAVIS_JOB_ID'] = $serviceJobId;
        $env['TRAVIS_BUILD_NUMBER'] = $serviceBuildNumber;
        $env['COVERALLS_REPO_TOKEN'] = $repoToken;

        $config = $this->createConfiguration();
        $config->setServiceName($serviceName);

        $object = $this->createCiEnvVarsCollector($config);

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_JOB_ID', $actual);
        self::assertSame($serviceJobId, $actual['CI_JOB_ID']);

        self::assertArrayHasKey('CI_BUILD_NUMBER', $actual);
        self::assertSame($serviceBuildNumber, $actual['CI_BUILD_NUMBER']);

        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $actual);
        self::assertSame($repoToken, $actual['COVERALLS_REPO_TOKEN']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectCircleCiEnvVars()
    {
        $serviceName = 'circleci';
        $serviceNumber = '123';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = 'token';
        $env['CIRCLECI'] = 'true';
        $env['CIRCLE_WORKFLOW_ID'] = $serviceNumber;

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_BUILD_NUMBER', $actual);
        self::assertSame($serviceNumber, $actual['CI_BUILD_NUMBER']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectJenkinsEnvVars()
    {
        $serviceName = 'jenkins';
        $serviceNumber = '123';
        $buildUrl = 'http://localhost:8080';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = 'token';
        $env['JENKINS_URL'] = $buildUrl;
        $env['BUILD_NUMBER'] = $serviceNumber;

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_BUILD_NUMBER', $actual);
        self::assertSame($serviceNumber, $actual['CI_BUILD_NUMBER']);

        self::assertArrayHasKey('CI_BUILD_URL', $actual);
        self::assertSame($buildUrl, $actual['CI_BUILD_URL']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectGithubActionsEnvVars()
    {
        $serviceName = 'github';
        $jobId = '275038505';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = 'token';
        $env['GITHUB_ACTIONS'] = true;
        $env['GITHUB_EVENT_NAME'] = 'push';
        $env['GITHUB_REF'] = 'refs/heads/master';
        $env['GITHUB_RUN_ID'] = '275038505';

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_JOB_ID', $actual);
        self::assertSame($jobId, $actual['CI_JOB_ID']);

        self::assertArrayHasKey('CI_BRANCH', $actual);
        self::assertSame('master', $actual['CI_BRANCH']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectGithubActionsEnvVarsForPullRequest()
    {
        $serviceName = 'github';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = 'token';
        $env['GITHUB_ACTIONS'] = true;
        $env['GITHUB_EVENT_NAME'] = 'pull_request';
        $env['GITHUB_REF'] = 'refs/pull/1/merge';
        $env['GITHUB_RUN_ID'] = '275038505';

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_JOB_ID', $actual);
        self::assertSame('275038505', $actual['CI_JOB_ID']);

        self::assertArrayHasKey('CI_BRANCH', $actual);
        self::assertSame('refs/pull/1/merge', $actual['CI_BRANCH']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectGithubActionsEnvVarsForTag()
    {
        $serviceName = 'github';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = 'token';
        $env['GITHUB_ACTIONS'] = true;
        $env['GITHUB_EVENT_NAME'] = 'push';
        $env['GITHUB_REF'] = 'refs/tags/v123.456.789';
        $env['GITHUB_RUN_ID'] = '275038505';

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('CI_JOB_ID', $actual);
        self::assertSame('275038505', $actual['CI_JOB_ID']);

        self::assertArrayHasKey('CI_BRANCH', $actual);
        self::assertSame('v123.456.789', $actual['CI_BRANCH']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectLocalEnvVars()
    {
        $serviceName = 'php-coveralls';
        $serviceEventType = 'manual';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = 'token';
        $env['COVERALLS_RUN_LOCALLY'] = '1';

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('CI_NAME', $actual);
        self::assertSame($serviceName, $actual['CI_NAME']);

        self::assertArrayHasKey('COVERALLS_EVENT_TYPE', $actual);
        self::assertSame($serviceEventType, $actual['COVERALLS_EVENT_TYPE']);

        self::assertArrayHasKey('CI_JOB_ID', $actual);
        self::assertNull($actual['CI_JOB_ID']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectUnsupportedConfig()
    {
        $repoToken = 'token';

        $env = [];

        $config = $this->createConfiguration();
        $config->setRepoToken($repoToken);

        $object = $this->createCiEnvVarsCollector($config);

        $actual = $object->collect($env);

        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $actual);
        self::assertSame($repoToken, $actual['COVERALLS_REPO_TOKEN']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectUnsupportedEnvVars()
    {
        $repoToken = 'token';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = $repoToken;

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $actual);
        self::assertSame($repoToken, $actual['COVERALLS_REPO_TOKEN']);

        return $object;
    }

    /**
     * @test
     */
    public function shouldCollectParallel()
    {
        $parallel = true;
        $flagName = 'php-7.4';

        $env = [];
        $env['COVERALLS_PARALLEL'] = $parallel;
        $env['COVERALLS_FLAG_NAME'] = $flagName;

        $object = $this->createCiEnvVarsCollector();

        $actual = $object->collect($env);

        self::assertArrayHasKey('COVERALLS_PARALLEL', $actual);
        self::assertSame($parallel, $actual['COVERALLS_PARALLEL']);

        self::assertArrayHasKey('COVERALLS_FLAG_NAME', $actual);
        self::assertSame($flagName, $actual['COVERALLS_FLAG_NAME']);

        return $object;
    }

    // getReadEnv()

    /**
     * @test
     */
    public function shouldNotHaveReadEnvOnConstruction()
    {
        $object = $this->createCiEnvVarsCollector();

        self::assertNull($object->getReadEnv());
    }

    /**
     * @test
     *
     * @depends shouldCollectTravisCiEnvVars
     */
    public function shouldHaveReadEnvAfterCollectTravisCiEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(4, $readEnv);
        self::assertArrayHasKey('TRAVIS', $readEnv);
        self::assertArrayHasKey('TRAVIS_JOB_ID', $readEnv);
        self::assertArrayHasKey('CI_NAME', $readEnv);
        self::assertArrayHasKey('CI_BUILD_NUMBER', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectGithubActionsEnvVars
     */
    public function shouldHaveReadEnvAfterCollectGithubActionsEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(6, $readEnv);
        self::assertArrayHasKey('GITHUB_REF', $readEnv);
        self::assertArrayHasKey('CI_NAME', $readEnv);
        self::assertArrayHasKey('CI_JOB_ID', $readEnv);
        self::assertArrayHasKey('GITHUB_ACTIONS', $readEnv);
        self::assertArrayHasKey('CI_BRANCH', $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectTravisProEnvVars
     */
    public function shouldHaveReadEnvAfterCollectTravisProEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(5, $readEnv);
        self::assertArrayHasKey('TRAVIS', $readEnv);
        self::assertArrayHasKey('TRAVIS_JOB_ID', $readEnv);
        self::assertArrayHasKey('CI_NAME', $readEnv);
        self::assertArrayHasKey('CI_BUILD_NUMBER', $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectCircleCiEnvVars
     */
    public function shouldHaveReadEnvAfterCollectCircleCiEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(4, $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
        self::assertArrayHasKey('CIRCLECI', $readEnv);
        self::assertArrayHasKey('CIRCLE_WORKFLOW_ID', $readEnv);
        self::assertArrayHasKey('CI_NAME', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectJenkinsEnvVars
     */
    public function shouldHaveReadEnvAfterCollectJenkinsEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(4, $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
        self::assertArrayHasKey('JENKINS_URL', $readEnv);
        self::assertArrayHasKey('BUILD_NUMBER', $readEnv);
        self::assertArrayHasKey('CI_NAME', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectLocalEnvVars
     */
    public function shouldHaveReadEnvAfterCollectLocalEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(4, $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
        self::assertArrayHasKey('COVERALLS_RUN_LOCALLY', $readEnv);
        self::assertArrayHasKey('COVERALLS_EVENT_TYPE', $readEnv);
        self::assertArrayHasKey('CI_NAME', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectUnsupportedConfig
     */
    public function shouldHaveReadEnvAfterCollectUnsupportedConfig(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(1, $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
    }

    /**
     * @test
     *
     * @depends shouldCollectUnsupportedEnvVars
     */
    public function shouldHaveReadEnvAfterCollectUnsupportedEnvVars(CiEnvVarsCollector $object)
    {
        $readEnv = $object->getReadEnv();

        self::assertCount(1, $readEnv);
        self::assertArrayHasKey('COVERALLS_REPO_TOKEN', $readEnv);
    }

    protected function legacySetUp()
    {
        $this->setUpDir(realpath(__DIR__ . '/../../..'));
    }

    /**
     * @return Configuration
     */
    protected function createConfiguration()
    {
        $config = new Configuration();

        return $config
        ->addCloverXmlPath($this->cloverXmlPath)
        ;
    }

    /**
     * @param null|Configuration $config
     *
     * @return CiEnvVarsCollector
     */
    protected function createCiEnvVarsCollector($config = null)
    {
        if ($config === null) {
            $config = $this->createConfiguration();
        }

        return new CiEnvVarsCollector($config);
    }
}
