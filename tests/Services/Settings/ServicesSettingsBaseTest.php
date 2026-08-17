<?php

use PHPUnit\Framework\TestCase;

class ServicesSettingsBaseTest extends TestCase
{
    public function testSchemaVersionIsBumpedForSpotsfullColumnUpgrade()
    {
        $this->assertSame('0.70', SPOTDB_SCHEMA_VERSION);
    }

    public function testSchemaVersionGateRequiresCurrentSchema()
    {
        $settingsContainer = new Services_Settings_Container();
        $settingsContainer->addSource(new ServicesSettingsBaseTestSource(['schemaversion' => '0.69']));
        $settingsBase = new Services_Settings_Base($settingsContainer, new ServicesSettingsBaseTestBlackWhiteListDao());

        $this->assertFalse($settingsBase->schemaValid());
    }

    public function testSchemaVersionGateAcceptsBumpedSchema()
    {
        $settingsContainer = new Services_Settings_Container();
        $settingsContainer->addSource(new ServicesSettingsBaseTestSource(['schemaversion' => SPOTDB_SCHEMA_VERSION]));
        $settingsBase = new Services_Settings_Base($settingsContainer, new ServicesSettingsBaseTestBlackWhiteListDao());

        $this->assertTrue($settingsBase->schemaValid());
    }
}

class ServicesSettingsBaseTestSource implements Services_Settings_IContainer
{
    private $_settings;

    public function __construct(array $settings)
    {
        $this->_settings = $settings;
    }

    public function initialize(array $cfg)
    {
    }

    public function getAllSettings()
    {
        return $this->_settings;
    }

    public function remove($name)
    {
    }

    public function set($name, $value)
    {
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
