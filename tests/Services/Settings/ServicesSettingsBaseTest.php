<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/Bootstrap.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelineDepth.php';

class ServicesSettingsBaseTestContainer implements Services_Settings_IContainer
{
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function initialize(array $cfg)
    {
    }

    public function getAllSettings()
    {
        return $this->settings;
    }

    public function remove($name)
    {
        unset($this->settings[$name]);
    }

    public function set($name, $value)
    {
        $this->settings[$name] = $value;
    }
}

class ServicesSettingsBaseTestBlackWhiteListDao implements Dao_BlackWhiteList
{
    public function removeOldList($listUrl, $idtype)
    {
    }

    public function updateExternalList($newlist, $idtype)
    {
    }

    public function addSpotterToList($spotterId, $ourUserId, $origin, $idType)
    {
    }

    public function removeSpotterFromList($spotterId, $ourUserId)
    {
    }

    public function getSpotterList($ourUserId)
    {
    }

    public function getBlacklistForSpotterId($userId, $spotterId)
    {
    }
}

class ServicesSettingsBaseTest extends TestCase
{
    public function testSchemaVersionIsBumpedForSpotsfullColumnUpgrade()
    {
        $this->assertSame('0.71', SPOTDB_SCHEMA_VERSION);
    }

    public function testSchemaVersionGateRequiresUpgradeFromUtf8mb4Schema()
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new ServicesSettingsBaseTestContainer([
            'schemaversion' => '0.70',
        ]));

        $service = new Services_Settings_Base($settings, new ServicesSettingsBaseTestBlackWhiteListDao());

        $this->assertFalse($service->schemaValid());
    }

    public function testSchemaVersionBumpRequiresUpgradeFromPreviousSchema()
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new ServicesSettingsBaseTestContainer([
            'schemaversion' => '0.69',
        ]));

        $service = new Services_Settings_Base($settings, new ServicesSettingsBaseTestBlackWhiteListDao());

        $this->assertSame('0.71', SPOTDB_SCHEMA_VERSION);
        $this->assertFalse($service->schemaValid());

        $settings->set('schemaversion', SPOTDB_SCHEMA_VERSION);

        $this->assertTrue($service->schemaValid());
    }

    public function testNntpPipelineDepthDefaultsWhenMissing()
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new ServicesSettingsBaseTestContainer([]));
        $service = new Services_Settings_Base($settings, new ServicesSettingsBaseTestBlackWhiteListDao());

        $result = $service->validateSettings($this->validSettingsWithoutPipelineDepth());

        $this->assertTrue($result->isSuccess());
        $validated = $result->getData('settings');
        $this->assertSame(Services_Nntp_PipelineDepth::DefaultDepth, $validated['nntp_nzb']['article_pipeline_depth']);
        $this->assertSame(Services_Nntp_PipelineDepth::DefaultDepth, $validated['nntp_hdr']['article_pipeline_depth']);
        $this->assertSame(Services_Nntp_PipelineDepth::DefaultDepth, $validated['nntp_post']['article_pipeline_depth']);
    }

    public function testNntpPipelineDepthAcceptsSafeBoundedInteger()
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new ServicesSettingsBaseTestContainer([]));
        $service = new Services_Settings_Base($settings, new ServicesSettingsBaseTestBlackWhiteListDao());
        $form = $this->validSettingsWithoutPipelineDepth();
        $form['nntp_nzb']['article_pipeline_depth'] = '1';
        $form['nntp_hdr']['article_pipeline_depth'] = '32';
        $form['nntp_post']['article_pipeline_depth'] = '128';

        $result = $service->validateSettings($form);

        $this->assertTrue($result->isSuccess());
        $validated = $result->getData('settings');
        $this->assertSame(1, $validated['nntp_nzb']['article_pipeline_depth']);
        $this->assertSame(32, $validated['nntp_hdr']['article_pipeline_depth']);
        $this->assertSame(128, $validated['nntp_post']['article_pipeline_depth']);
    }

    public function testNntpPipelineDepthRejectsInvalidValues()
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new ServicesSettingsBaseTestContainer([]));
        $service = new Services_Settings_Base($settings, new ServicesSettingsBaseTestBlackWhiteListDao());
        $form = $this->validSettingsWithoutPipelineDepth();
        $form['nntp_hdr']['article_pipeline_depth'] = '129';

        $result = $service->validateSettings($form);

        $this->assertFalse($result->isSuccess());
    }

    private function validSettingsWithoutPipelineDepth()
    {
        $server = ['host' => 'news.example', 'user' => '', 'pass' => '', 'enc' => false, 'port' => 119, 'buggy' => false, 'verifyname' => true];

        return [
            'nntp_nzb' => $server,
            'nntp_hdr' => $server + ['use' => 'on'],
            'nntp_post' => $server + ['use' => 'on'],
            'spot_moderation' => 'act',
            'retentiontype' => 'fullonly',
            'cookie_expires' => 30,
            'retention' => 0,
            'retrieve_newer_than' => '2009-11-01',
            'retrieve_increment' => 1000,
            'systemfrommail' => 'spotweb@example.com',
            'highcount' => 10,
            'customcss' => '',
            'smtp' => ['host' => '', 'user' => '', 'pass' => '', 'port' => 587],
            'blacklist_url' => '',
            'whitelist_url' => '',
            'ms_translator_subscriptionkey' => '',
        ];
    }
}
