<?php
/**
 * Copyright [2011] [Mario Mueller]
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *	  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace Prowl;
/**
 * Prowl Message
 *
 * This class represents a single message
 * to be send by the connector.
 *
 * @author Mario Mueller <mario.mueller.work at gmail.com>
 * @version 1.0.0
 * @package Prowl
 * @subpackage Message
 */
class Message {

	/**
	 * Your API keys. Please use the
	 * setter to modify this.
	 * @var array
	 */
	private $aApiKeys = array();

	/**
	 * A priority value from -2 to 2
	 * @var integer
	 */
	private $iPriority = 0;

	/**
	 * The application identifier.
	 * @var string
	 */
	private $sApplication = 'ProwlPHP';

	/**
	 * The event title.
	 * @var string
	 */
	private $sEvent = null;

	/**
	 * The event description.
	 * @var string
	 */
	private $sDescription = null;

	/**
	 * Filter instance. This one is
	 * passed from the connection on push, if the message
	 * has no filter set.
	 * @var \Prowl\Security\Secureable
	 */
	private $oFilterIntance = null;

	/**
	 * An alternative way to filter. You can set a
	 * closure instead of a filter instance. If both are
	 * set, the closure will be preferred.
	 *
	 * @var \Closure
	 */
	private $cFilterCallback = null;

	/**
	 * An Url to send with the message for redirecting.
	 */
	private $sUrl = null;

	/**
	 * Sets an Url to be sent with the message.
	 * @throws \InvalidArgumentException
	 * @param string $sUrl
	 * @return void
	 */
	public function setUrl($sUrl) {
		$sUrl = filter_var($sUrl, FILTER_VALIDATE_URL);

		if (!$sUrl) {
			throw new \InvalidArgumentException("Given url [$sUrl] did not pass the validation.");
		}

		$this->sUrl;
	}

	/**
	 * Returns the Url that should be sent with the message
	 * @return string
	 */
	public function getUrl() {
		return $this->sUrl;
	}

	/**
	 * Set a filter instance. If you do not need a filter, use the
	 * Passthrough filter.
	 *
	 * @param \Prowl\Security\Secureable $oFilterInstance
	 * @return \Prowl\Message
	 */
	public function setFilter(\Prowl\Security\Secureable $oFilterInstance) {
		$this->oFilterIntance = $oFilterInstance;
		return $this;
	}

	/**
	 * Returns the filter instance, if set. It might return null
	 * when no filter is set.
	 *
	 * @return \Prowl\Security\Secureable
	 */
	public function getFilter() {
		return $this->oFilterIntance;
	}

	/**
	 * An alternative way to filter. You can set a
	 * closure instead of a filter instance. If both are
	 * set, the closure will be preferred.
	 * @param \Closure $cCallback
	 * @return void
	 */
	public function setFilterCallback(\Closure $cCallback) {
		$this->cFilterCallback = $cCallback;
	}

	/**
	 * Getter for the filter closure.
	 * @return \Closure
	 */
	public function getFilterCallback() {
		return $this->cFilterCallback;
	}

	/**
	 * Sets the event.
	 * @throws InvalidArgumentException
	 * @param string $sEvent The event.
	 * @return \Prowl\Message
	 */
	public function setEvent($sEvent) {
		$iContentLength = mb_strlen($sEvent, 'utf-8');

		if ($iContentLength > 1024) {
			throw new \InvalidArgumentException('Event length is limited to 1024 chars. Yours is ' . $iContentLength);
		}

		$this->sEvent = (string)$sEvent;
		return $this;
	}

	/**
	 * Returns the event.
	 * @return string
	 */
	public function getEvent() {
		return $this->sEvent;
	}

	/**
	 * Sets the application.
	 * @throws \InvalidArgumentException
	 * @param string $sApp The name of the sending application.
	 * @return \Prowl\Message
	 */
	public function setApplication($sApp) {
		$iContentLength = mb_strlen($sApp, 'utf-8');

		if ($iContentLength > 254) {
			throw new \InvalidArgumentException('Application length is limited to 254 chars. Yours is ' . $iContentLength);
		}

		$this->sApplication = (string)$sApp;
		return $this;
	}

	/**
	 * Returns the application string.
	 *
	 * @return string
	 */
	public function getApplication() {
		return $this->sApplication;
	}

	/**
	 * Sets the event description.
	 *
	 * @throws \InvalidArgumentException
	 * @param string $sDescription The event description.
	 * @return \Prowl\Message
	 */
	public function setDescription($sDescription) {
		$iContentLength = mb_strlen($sDescription, 'utf-8');

		if ($iContentLength > 10000) {
			throw new \InvalidArgumentException('Description is too long. Limit is 10.000, yours is ' . $iContentLength);
		}
		$this->sDescription = (string)$sDescription;
		return $this;
	}

	/**
	 * Returns the description.
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->sDescription;
	}

	/**
	 * Sets the api key.
	 * This method uses a fluent interface.
	 *
	 * @throws \InvalidArgumentException
	 * @param string $sKey An valid api key.
	 * @return \Prowl\Message
	 */
	public function addApiKey($sKey) {
		if (is_string($sKey)) {
			$this->aApiKeys[] = (string)$sKey;
		} else {
			throw new \InvalidArgumentException('The param was not a string.');
		}
		return $this;
	} // function

	/**
	 * Removes an api key from the receiver list.
	 *
	 * @throws \OutOfRangeException
	 * @param string $sKey
	 * @return \Prowl\Message
	 */
	public function removeApiKey($sKey) {
		$iIndex = array_search($sKey, $this->aApiKeys);
		if ($iIndex === false) {
			throw new \OutOfRangeException('This API key does not exist in list.');
		} else {
			unset($this->aApiKeys[$iIndex]);
		}
		return $this;
	}

	/**
	 * Returns all actual api keys as array.
	 *
	 * @return array[string]
	 */
	public function getApiKeysAsArray() {
		return $this->aApiKeys;
	}

	/**
	 * Returns all actual api keys as string
	 *
	 * @return string
	 */
	public function getApiKeysAsString() {
		return implode(',', $this->getApiKeysAsArray());
	}

	/**
	 * Sets the proirity (-2 to 2)
	 * This method uses a fluent interface.
	 *
	 * @throws \InvalidArgumentException
	 * @param integer $iPriority An signed integer from -2 to 2
	 * @return \Prowl\Message
	 */
	public function setPriority($iPriority) {
		$mVal = filter_var($iPriority, FILTER_VALIDATE_INT);

		if (($mVal !== false) && ($mVal >= -2) && ($mVal <= 2)) {
			$this->iPriority = $mVal;
		} else {
			throw new \InvalidArgumentException('The param was not between -2 and 2 or even an integer.');
		}
		return $this;
	}

	/**
	 * Returns the priority as signed integer
	 *
	 * @return integer
	 */
	public function getPriority() {
		return $this->iPriority;
	}

	/**
	 * Validates the basic needs of the prowl api.
	 *
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function validate() {
		if (is_null($this->sEvent)) {
			throw new \InvalidArgumentException('Validation Error: Event is missing');
		}

		if (sizeof($this->aApiKeys) == 0) {
			throw new \InvalidArgumentException('Validation Error: No api keys present.');
		}
		return true;
	}
}