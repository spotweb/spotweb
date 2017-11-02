<?php

class Basics
	extends PHPUnit\Framework\TestCase
{
	static $nntp;
	
	static $groups;
	static $groupDescriptions;
	
	static $group;
	static $groupDescription;
	
	static $article;

    static function tearDownAfterClass()
    {
		self::$nntp = null;
    }

    /**
     * 
     */
    function test_Create()
    {
		self::$nntp = new \Net_NNTP_Client();
		$this->assertTrue(self::$nntp instanceof \Net_NNTP_Client);
    }

	/**
	 *
     */
    function test_Connect_Timeout()
    {
		$this->assertFalse(@self::$nntp->isConnected());
		self::$nntp->connect('non-existing-host.example.net', null, null, 1);
		$this->assertFalse(@self::$nntp->isConnected());
    }

	/**
     * @depends test_Create
     */
    function test_Connect()
    {
		$posting = self::$nntp->connect('news.php.net');
		$this->assertTrue(@self::$nntp->isConnected());
    }

    /**
     * @depends test_Connect
     */
    function test_GetGroups()
    {
		self::$groups = self::$nntp->getGroups();
		$this->assertTrue(is_array(self::$groups));
    }

    /**
     * @depends test_GetGroups
     */
    function test_GetGroupsWildcard()
    {
// TODO: Do _not_ set self::$groups
		self::$nntp->getGroups('php.pear*');
		$this->assertTrue(is_array(self::$groups));
    }

    /**
     * @depends test_Connect
     */
    function test_GetGroupDescriptions()
    {
// TODO: Do _not_ use wildcard here. Create another test with wildcard...
		$descriptions = self::$nntp->getDescriptions('php.pear*');
		$this->assertTrue(is_array($descriptions));

		// Test if at least one description
		$this->assertTrue(count($descriptions) > 0);

		// Test first description
		$this->assertTrue(is_string(reset($descriptions)));
		$this->assertTrue(is_string(key($descriptions)));

		// Test last description
		$this->assertTrue(is_string(end($descriptions)));
		$this->assertTrue(is_string(key($descriptions)));

		self::$groupDescriptions = $descriptions;
    }
	
    /**
     * @depends test_GetGroups
     */
    function test_SelectGroup()
    {
		// Use the current group in self::$groups
		self::$group = $summary = self::$nntp->selectGroup( key(self::$groups) );
 		$this->assertTrue(is_array($summary));
 
		$this->assertTrue(isset($summary['group']));
 		$this->assertTrue(isset($summary['first']));
 		$this->assertTrue(isset($summary['last']));
 		$this->assertTrue(isset($summary['count']));

		$this->assertTrue(is_string($summary['group']));
 		$this->assertTrue(is_string($summary['first']));
 		$this->assertTrue(is_string($summary['last']));
 		$this->assertTrue(is_string($summary['count']));
 
		$this->assertTrue(is_numeric($summary['first']));
 		$this->assertTrue(is_numeric($summary['last']));
 		$this->assertTrue(is_numeric($summary['count']));
    }
	
    /**
     * @depends test_SelectGroup
     */
    function test_GetSelectedGroupDescription()
    {
		$group = self::$group['group'];
		
		$descriptions = self::$nntp->getDescriptions($group);
		$this->assertTrue(is_array($descriptions));

		// Test if excatly one description
		$this->assertTrue(count($descriptions) == 1);

		// Test if group is as expected
		$this->assertTrue(isset($descriptions[$group]));

		// Test if description is a string
		$this->assertTrue(is_string($descriptions[$group]));
		
		self::$groupDescription = $descriptions[$group];
    }
	
    /**
     * @depends test_SelectGroup
     */
    function test_SelectFirstArticle()
    {
		self::$article = self::$nntp->selectArticle(self::$nntp->first());
		$this->assertTrue(is_int(self::$article));
    }
	
    /**
     * @depends test_SelectFirstArticle
     */
    function test_getFirstArticle()
    {
		$header = self::$nntp->getHeader();
		$this->assertTrue(is_array($header));

		$body = self::$nntp->getBody();
		$this->assertTrue(is_array($body));
    }

    /**
     * @depends test_SelectGroup
     */
    function test_SelectLastArticle()
    {
		self::$article = self::$nntp->selectArticle(self::$nntp->last());
		$this->assertTrue(is_int(self::$article));
    }
	
    /**
     * @depends test_SelectLastArticle
     */
    function test_getLastArticle()
    {
		$header = self::$nntp->getHeader();
		$this->assertTrue(is_array($header));

		$body = self::$nntp->getBody();
		$this->assertTrue(is_array($body));
    }

    /**
     * @depends test_Connect
     */
    function test_Disconnect()
    {
		self::$nntp->disconnect();
		$this->assertFalse(@self::$nntp->isConnected());
    }

	/**
     * @depends test_Disconnect
     */
    function test_Disconnect2()
    {
		$this->assertFalse(@self::$nntp->isConnected());
		self::$nntp->disconnect();
		$this->assertFalse(@self::$nntp->isConnected());
    }
}
