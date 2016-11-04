<?php
namespace Prowl\Test;

/**
 * Tests GitHub Issue #7
 */
class TestBugId7 extends \PHPUnit_Framework_TestCase {

	public function testMessageSetter() {
		$oMessage = new \Prowl\Message();
		$sUrl = "http://xenji.com";
		$oMessage->setUrl($sUrl);
		$this->assertEquals($sUrl, $oMessage->getUrl(), "Assertion of URL setter failed, maybe due bug #7?");
	}
}