<?php
/**
 * Prowl Connector
 * 
 * This class provides a response of the connector.
 * 
 * @author Mario Mueller <mario.mueller.mac@me.com>
 * @version 0.3.2
 * @package Prowl
 * @subpackage Response
 */
class ProwlResponse
{
	/**
	 * The raw response.
	 * @since  0.3.1
	 * @var string
	 */
	private $sRawResponse 	= null;
	
	/**
	 * The return code of the app.
	 * @since  0.3.1
	 * @var integer
	 */
	private $iReturnCode 		= null;
	
	/**
	 * Constant to indicate a succuessfull
	 * response.
	 * @since  0.3.1
	 * @var integer
	 */
	const RESPONSE_OK 			= 200;
	
	/**
	 * Constant to indicate an unsuccessful
	 * response.
	 * @since  0.3.1
	 * @var integer
	 */
	const RESPONSE_NOK 			= -1;
	
	/**
	 * The count of remaining requests
	 * @since  0.3.1
	 * @var integer
	 */
	private $iRemaining 		= null;
	
	/**
	 * The date for the remaining to be
	 * resetted.
	 * @since  0.3.1
	 * @var integer
	 */
	private $iResetDate		= null;
	
	/**
	 * Constructor made protected.
	 * Use ProwlResponse::fromResponseXml().
	 * 
	 * @since  0.3.1
	 * @see ProwlResponse::fromResponseXml()
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 */
	private function __construct(){}
	
	/**
	 * Takes the raw api response.
	 * 
	 * @since  0.3.1
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @param string $sXml
	 * @return ProwlResponse
	 */
	public static function fromResponseXml($sXml)
	{
		$oResponse = new self();
		$oResponse->sRawResponse = $sXml;
		$oResponse->parseRawResponse();
		return $oResponse;
	} // function
	
	/**
	 * Parses the raw xml data.
	 * 
	 * @since  0.3.1
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return void
	 */
	private function parseRawResponse()
	{
		try 
		{
			$oSxmlResponse = new SimpleXMLElement($this->sRawResponse);
		} // try
		catch (Exception $oException)
		{
			$this->iReturnCode = 500;
			return self::RESPONSE_NOK;
		} // catch
		
		
		/* @var $oSxmlResponse SimpleXMLElement */
		if ($oSxmlResponse->success['code'] != null)
		{
			$this->iReturnCode 	= (int) $oSxmlResponse->success['code'];
			$this->iRemaining 	= (int) $oSxmlResponse->success['remaining'];
			$this->iResetDate 	= (int) $oSxmlResponse->success['resetdate'];
			return self::RESPONSE_OK;
		} // if successful response
		else
		{
			$this->iReturnCode 	= (int) $oSxmlResponse->error['code'];
			return self::RESPONSE_NOK;
		} // else not successfull response
	} // function
	
	/**
	 * Returns a boolean value indicating
	 * if the response was an error or not.
	 * 
	 * @since  0.3.1
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return boolean
	 */
	public function isError()
	{
		if ($this->iReturnCode === self::RESPONSE_OK)
			return false;
		else
			return true;
	} // function

	/**
	 * Returns the corresponding error
	 * message.
	 * 
	 * @since  0.3.1
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return string
	 */
	public function getErrorAsString()
	{
		return $this->getErrorByCode($this->iReturnCode);
	} // function
	
	/**
	 * The remaining requests.
	 * 
	 * @since  0.3.1
	 * @return integer
	 */
	public function getRemaining()
	{
		return $this->iRemaining;
	}
	
	/**
	 * The reset date.
	 * 
	 * @since  0.3.1
	 * @return integer
	 */
	public function getResetDate()
	{
		return $this->iResetDate;
	}
	
	/**
	 * Returns the error message to a given code.
	 * 
	 * @since  0.3.1
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @param integer $code
	 * @return string
	 */
	private function getErrorByCode($iCode)
	{
		//TODO: Find a better way to implement error messages. 
		switch($iCode)
		{
			case 200: 	return 'Request Successful.';	break;
			case 400:	return 'Bad request, the parameters you provided did not validate.';	break;
			case 401: 	return 'The API key given is not valid, and does not correspond to a user.';	break;
			case 405:	return 'Method not allowed, you attempted to use a non-SSL connection to Prowl.';	break;
			case 406:	return 'Your IP address has exceeded the API limit.';	break;
			case 500:	return 'Internal server error, something failed to execute properly on the Prowl side.';	break;
			case 10000:	return 'cURL library missing vital functions or does not support SSL. cURL w/SSL is required to execute ProwlConnector.class.';	break;
			case 10001:	return 'Parameter value exceeds the maximum byte size.';	break;
			default:	return false;	break;
		} // switch response code
	} // function
} // class