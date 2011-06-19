<?php
/**
 * Prowl Message
 * 
 * This class represents a single message
 * to be send by the connector.
 * 
 * @author Mario Mueller <mario.mueller.mac@me.com>
 * @version 0.3.1
 * @package Prowl
 * @subpackage Message
 * @since 0.3.1
 */
class ProwlMessage
{
	/**
	 * Your API keys. Please use the
	 * setter to modify this.
	 * @var array
	 */
	protected $aApiKeys 		= array();
	
	/**
	 * A priority value from -2 to 2
	 * @var integer
	 */
	protected $iPriority 		= 0;
	
	/**
	 * The application identifier.
	 * @var string
	 */
	protected $sApplication		= 'ProwlPHP';
	
	/**
	 * The event title.
	 * @var string
	 */
	protected $sEvent			= null;
	
	/**
	 * The event description.
	 * @var string
	 */
	protected $sDescription		= null;
	
	/**
	 * Sets the event.
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws InvalidArgumentException
	 * @param string $sEvent The event.
	 * @return ProwlMessage
	 */
	public function setEvent($sEvent)
	{
		$iStrlen 	= mb_strlen($sEvent, 'utf-8');
		
		if ($iStrlen > 1024)
			throw new InvalidArgumentException(
				'Event length is limited to 1024 chars. Yours is ' . $iStrlen);
		
		$this->sEvent = (string) $sEvent;
		return $this;
	} // function
	
	/**
	 * Returns the event.
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return string
	 */
	public function getEvent()
	{
		return $this->sEvent;
	} // function
	
	/**
	 * Sets the application.
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws InvalidArgumentException
	 * @param string $sApp The name of the sending application.
	 * @return ProwlMessage
	 */
	public function setApplication($sApp)
	{
		$iStrlen 	= mb_strlen($sApp, 'utf-8');
		
		if ($iStrlen > 254)
			throw new InvalidArgumentException(
				'Application length is limited to 254 chars. Yours is ' . $iStrlen);
			
		$this->sApplication = (string) $sApp;
		return $this;
	} // function
	
	/**
	 * Returns the application string.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return string
	 */
	public function getApplication()
	{
		return $this->sApplication;
	} // function
	
	/**
	 * Sets the event description.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws InvalidArgumentException
	 * @param string $sDescr The event description.
	 * @return ProwlMessage
	 */
	public function setDescription($sDescr)
	{
		$iStrlen	= mb_strlen($sDescr, 'utf-8');
		
		if ($iStrlen > 10000)
			throw new InvalidArgumentException(
					'Description is too long. Limit is 10.000, yours is ' . $iStrlen);
			
		$this->sDescription = (string) $sDescr;
		return $this;
	} // function
	
	/**
	 * Returns the description.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return string
	 */
	public function getDescription()
	{
		return $this->sDescription;
	} // function
	
	/**
	 * Sets the api key.
	 * This method uses a fluent interface.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws InvalidArgumentException
	 * @param string $sKey An valid api key.
	 * @return ProwlMessage
	 */
	public function addApiKey($sKey)
	{
		if (is_string($sKey) && sizeof($this->aApiKeys) < 6)
			$this->aApiKeys[] = (string) $sKey;
		else 
			throw new InvalidArgumentException(
				'The param was not a string or the limit of 5 keys is reached.');
			
		return $this;
	} // function
	
	/**
	 * Removes an api key from the receiver list.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws OutOfRangeException
	 * @param string $sKey
	 * @return ProwlMessage
	 */
	public function removeApiKey($sKey)
	{
		$iIndex = array_search($sKey, $this->aApiKeys);
		if ($iIndex === false)
			throw new OutOfRangeException('This API key does not exist in list.');
		else
			unset($this->aApiKeys[$iIndex]);
			
		return $this;
	} // function
	
	/**
	 * Returns all actual api keys as array.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return array
	 */
	public function getApiKeysAsArray()
	{
		return $this->aApiKeys;
	} // function
	
	/**
	 * Returns all actual api keys as string
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return string
	 */
	public function getApiKeysAsString()
	{
		return implode(',',$this->getApiKeysAsArray());
	} // function
	
	/**
	 * Sets the proirity (-2 to 2)
	 * This method uses a fluent interface.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws InvalidArgumentException
	 * @param integer $iPrio An signed integer from -2 to 2
	 * @return Prowl
	 */
	public function setPriority($iPrio)
	{
		$mVal = filter_var($iPrio, FILTER_VALIDATE_INT);
		
		if (($mVal !== false) && ($mVal >= -2) && ($mVal <= 2))
			$this->iPriority = $mVal;
		else 
			throw new InvalidArgumentException(
				'The param was not between -2 and 2 or even an integer.');
			
		return $this;
	} // function
	
	/**
	 * Returns the priority as signed integer
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return integer
	 */
	public function getPriority()
	{
		return $this->iPriority;
	} // function
	
	/**
	 * Validates the basic needs of the prowl api.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @throws InvalidArgumentException
	 * @return boolean
	 */
	public function validate()
	{
		if (is_null($this->sEvent))
			throw new InvalidArgumentException('Validation Error: Event is missing');
			
		if (sizeof($this->aApiKeys) == 0)
			throw new InvalidArgumentException('Validation Error: No api keys present.');
		
		return true;
	}
} // class