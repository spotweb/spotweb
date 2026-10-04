<?php

require_once __DIR__.'/../../../lib/Bootstrap.php';

use PHPUnit\Framework\TestCase;

class ServicesSettingsBaseTest extends TestCase
{
    protected function setUp(): void
    {
        /*
         * The settings container intentionally keeps application state in
         * static properties. Reset it per test so schema sources cannot leak
         * from one assertion to the next.
         */
        $container = new ReflectionClass(Services_Settings_Container::class);
        foreach (['_instance' => null, '_settings' => [], '_sources' => []] as $propertyName => $value) {
            $property = $container->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, $value);
        }
    }

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
