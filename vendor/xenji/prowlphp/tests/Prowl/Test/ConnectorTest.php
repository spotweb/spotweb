<?php
namespace Prowl\Test;

class TestConnector extends \PHPUnit_Framework_TestCase {

	private $aConfig;

	public function setUp()
	{
		$this->aConfig = include dirname(__FILE__) . '/../../config.php';
		
	}

	public function testDefaultMessageWithFilterInstance() {
		
		$oConnector = new \Prowl\Connector();
		$oConnector->setProviderKey($this->aConfig['providerkey']);

		$oMessage = new \Prowl\Message();
		$oMessage->addApiKey($this->aConfig['apikey']);
		$oMessage->setApplication("Unit Test");
		$oMessage->setPriority(0);
		$oMessage->setEvent("Unit Test");
		$oMessage->setDescription("Unit Test testDefaultMessageWithFilterInstance");

		$oMessage->setFilter(new \Prowl\Security\PassthroughFilterImpl());

		$oResponse = $oConnector->push($oMessage);
		$this->assertFalse($oResponse->isError());
	}

	public function testDefaultMessageWithClosure() {

		$oConnector = new \Prowl\Connector();
		$oConnector->setProviderKey($this->aConfig['providerkey']);

		$oMessage = new \Prowl\Message();
		$oMessage->addApiKey($this->aConfig['apikey']);
		$oMessage->setApplication("Unit Test");
		$oMessage->setPriority(0);
		$oMessage->setEvent("Unit Test");
		$oMessage->setDescription("Unit Test testDefaultMessageWithClosure");

		$oMessage->setFilterCallback(function($sContent) {
			return $sContent;
		});

		$oResponse = $oConnector->push($oMessage);
		$this->assertFalse($oResponse->isError());
	}

	public function testRetrieveToken() {
		
		$oConnector = new \Prowl\Connector();
		$oConnector->setProviderKey($this->aConfig['providerkey']);

		$oTokenResponse = $oConnector->retrieveToken();

		$this->assertTrue(filter_var($oTokenResponse->getTokenUrl(), FILTER_VALIDATE_URL) !== false);
		$this->assertNotNull($oTokenResponse->getToken());
	}

	public function testApiToken() {
		$this->markTestSkipped("This cannot be tested automatically as it requires user interaction.");
	}
}