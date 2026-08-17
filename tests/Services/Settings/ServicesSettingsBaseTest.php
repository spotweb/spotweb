<?php

use PHPUnit\Framework\TestCase;

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
}
