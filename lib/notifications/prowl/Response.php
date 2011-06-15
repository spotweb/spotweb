<?php
/**
 * Copyright [2011] [Mario Mueller]
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace Prowl;
/**
 * Prowl Connector
 *
 * This class provides a response of the connector.
 *
 * @author Mario Mueller <mario.mueller.work at gmail.com>
 * @version 1.0.0
 * @package Prowl
 * @subpackage Response
 */
class Response {
	
	/**
	 * The raw response.
	 * @since  0.3.1
	 * @var string
	 */
	private $sRawResponse = null;

	/**
	 * The return code of the app.
	 * @since  0.3.1
	 * @var integer
	 */
	private $iReturnCode = null;

	/**
	 * Constant to indicate a succuessfull
	 * response.
	 * @since  0.3.1
	 * @var integer
	 */
	const RESPONSE_OK = 200;

	/**
	 * Constant to indicate an unsuccessful
	 * response.
	 * @since  0.3.1
	 * @var integer
	 */
	const RESPONSE_NOK = -1;

	/**
	 * The count of remaining requests
	 * @since  0.3.1
	 * @var integer
	 */
	private $iRemaining = null;

	/**
	 * The date for the remaining to be
	 * resetted.
	 * @since  0.3.1
	 * @var integer
	 */
	private $iResetDate = null;

	/**
	 * Token from the retrieve/token request
	 * @var string
	 */
	private $sToken = null;

	/**
	 * Url from the retrieve/token request
	 * @var string
	 */
	private $sTokenUrl = null;

	/**
	 * Api key from a retrieve/apikey request
	 * @var string
	 */
	private $sApiKey = null;

	/**
	 * Constructor made protected.
	 * Use \Prowl\Response::fromResponseXml().
	 *
	 * @since  0.3.1
	 * @see \Prowl\Response::fromResponseXml()
	 */
	private function __construct() {
	}

	/**
	 * Takes the raw api response.
	 *
	 * @since  0.3.1
	 * @param string $sXml
	 * @return \Prowl\Response
	 */
	public static function fromResponseXml($sXml) {
		$oResponse = new self();
		$oResponse->sRawResponse = $sXml;
		$oResponse->parseRawResponse();
		return $oResponse;
	}

	/**
	 * Parses the raw xml data.
	 *
	 * @since  0.3.1
	 * @return void
	 */
	private function parseRawResponse() {
		try {
			$oSxmlResponse = new \SimpleXMLElement($this->sRawResponse);
		} catch (\Exception $oException) {
			$this->iReturnCode = 500;
			return self::RESPONSE_NOK;
		} // catch


		/* @var $oSxmlResponse SimpleXMLElement */
		if ($oSxmlResponse->success['code'] != null) {
			$this->iReturnCode = (int)$oSxmlResponse->success['code'];
			$this->iRemaining = (int)$oSxmlResponse->success['remaining'];
			$this->iResetDate = (int)$oSxmlResponse->success['resetdate'];

			if ($oSxmlResponse->retrieve != null) {
				if ($oSxmlResponse->retrieve['token'] != null) {
					$this->sToken =	$oSxmlResponse->retrieve['token'];
					$this->sTokenUrl = $oSxmlResponse->retrieve['url'];
				} elseif ($oSxmlResponse->retrieve['apikey'] != null) {
					$this->sApiKey = (string)$oSxmlResponse->retrieve['apikey'];
				}
			}
			return self::RESPONSE_OK;
		} else {
			$this->iReturnCode = (int)$oSxmlResponse->error['code'];
			return self::RESPONSE_NOK;
		}
	}

	/**
	 * Returns a boolean value indicating
	 * if the response was an error or not.
	 *
	 * @since  0.3.1
	 * @return boolean
	 */
	public function isError() {
		if ($this->iReturnCode === self::RESPONSE_OK) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Returns the corresponding error
	 * message.
	 *
	 * @since  0.3.1
	 * @return string
	 */
	public function getErrorAsString() {
		return $this->getErrorByCode($this->iReturnCode);
	}

	/**
	 * The remaining requests.
	 *
	 * @since  0.3.1
	 * @return integer
	 */
	public function getRemaining() {
		return $this->iRemaining;
	}

	/**
	 * The reset date.
	 *
	 * @since  0.3.1
	 * @return integer
	 */
	public function getResetDate() {
		return $this->iResetDate;
	}

	/**
	 * The API key. This one is filled only if you
	 * requested a token and api key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function getApiKey() {
		return $this->sApiKey;
	}

	/**
	 * Returns the token from a retrieve request. This one
	 * is null when you just sent a message.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function getToken() {
		return $this->sToken;
	}

	/**
	 * The token url to redirect the user to.
	 * This one is filled only if you made a retrieve/token
	 * request.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function getTokenUrl() {
		return $this->sTokenUrl;
	}
	
	/**
	 * Returns the error message to a given code.
	 *
	 * @since  0.3.1
	 * @param integer $iCode
	 * @return string
	 */
	private function getErrorByCode($iCode) {
		//TODO: Find a better way to implement error messages. 
		switch ($iCode) {
			case 200:
				return 'Request Successful.';
			case 400:
				return 'Bad request, the parameters you provided did not validate.';
			case 401:
				return 'The API key given is not valid and does not correspond to a user.';
			case 405:
				return 'Method not allowed, you attempted to use a non-SSL connection to Prowl.';
			case 406:
				return 'Your IP address has exceeded the API limit.';
			case 500:
				return 'Internal server error, something failed to execute properly on the Prowl side.';
			case 10000:
				return 'cURL library missing vital functions or does not support SSL. cURL w/SSL is required to execute requests.';
			case 10001:
				return 'Parameter value exceeds the maximum byte size.';
			default:
				return false;
		}
	}
}