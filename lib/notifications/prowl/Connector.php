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
 * Prowl Connector
 *
 * This class provides a connection to the prowl service
 * at http://www.prowlapp.com.
 *
 * @author Mario Mueller <mario.mueller.work at gmail.com>
 * @version 1.0.0
 * @package Prowl
 * @subpackage Connector
 */
class Connector {

	/**
	 * System version to send it with the client string
	 * @var string
	 */
	protected $sVersion = "1.0.0";

	/**
	 * The cUrl connection
	 * @var resource
	 */
	protected $rCurl = null;

	/**
	 * Shall we use a proxy?
	 * @var boolean
	 */
	protected $bUseProxy = false;

	/**
	 * The proxy url
	 * @var string
	 */
	protected $sProxyUrl = null;

	/**
	 * The password for the proxy.
	 * This is optional.
	 * @var string
	 */
	protected $sProxyPasswd = null;

	/**
	 * The provider key. Use the
	 * setter to modify this.
	 * @var string
	 */
	protected $sProviderKey = null;

	/**
	 * Sets the identifier if this
	 * should be a post request.
	 * @var boolean
	 */
	protected $bIsPostRequest = false;


	/**
	 * The API base url.
	 * @var string
	 */
	protected $sApiUrl = 'https://api.prowlapp.com/publicapi/';

	/**
	 * The API key verification url
	 * @var string
	 */
	protected $sVerifyContext = 'verify?apikey=%s&providerkey=%s';

	/**
	 * New messages will be send to
	 * this endpoint.
	 * @var string
	 */
	protected $sPushEndpoint = 'add';

	/**
	 * The last response that was
	 * received from the API.
	 * @var \Prowl\Response
	 */
	protected $oLastResponse = null;

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
	 * Enforce SSL usage via cURL
	 *@var boolean 
	 */
	private $bUseCurlSSL = true;

	/**
	 * ProwlConnector.class provides access to the
	 * webservice interface of Prowl by using
	 * cUrl + SSL. Use the setters of this class
	 * to provide the mandatory parameters.
	 *
	 * @throws \RuntimeException
	 * @return void
	 */
	public function __construct() {
		if (extension_loaded('curl') == false) {
			throw new \RuntimeException('cUrl Extension is not available.');
		}

		$curl_info = curl_version(); // Checks for cURL function and SSL version. Thanks Adrian Rollett!
		if (empty($curl_info['ssl_version'])) {
			throw new \RuntimeException('Your cUrl Extension does not support SSL.');
		}
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
	 * @return Prowl\Security\Secureable
	 */
	public function getFilter() {
		return $this->oFilterIntance;
	}

	/**
	 * Verifies the keys. This is optional but
	 * will we part of the future workflow
	 *
	 * @param string $sApikey
	 * @param string $sProvkey
	 * @return \Prowl\Response
	 */
	public function verify($sApikey, $sProvkey) {
		$sReturn = $this->execute(sprintf($this->sVerifyContext, $sApikey, $sProvkey));
		return \Prowl\Response::fromResponseXml($sReturn);
	}


	/**
	 * Sets the provider key.
	 * This method uses a fluent interface.
	 *
	 * @param string $sKey
	 * @return \Prowl\Connector
	 */
	public function setProviderKey($sKey) {
		if (is_string($sKey)) {
			$this->sProviderKey = $sKey;
		} else {
			throw new \InvalidArgumentException('The param was not a string.');
		}
		return $this;
	}

	/**
	 * Sets the post request identifier to true or false.
	 * This method uses a fluent interface.
	 *
	 * @param boolean $bIsPost
	 * @return \Prowl\Connector
	 */
	public function setIsPostRequest($bIsPost) {
		if (is_bool($bIsPost)) {
			$this->bIsPostRequest = $bIsPost;
		} else {
			throw new \InvalidArgumentException('The param was not a bool.');
		}
		return $this;
	}

	/**
	 * Pushes a message to the given api key.
	 *
	 * @param \Prowl\Message $oMessage
	 * @return \Prowl\Response
	 */
	public function push(\Prowl\Message $oMessage) {
		// Messages must be sent as post
		$this->setIsPostRequest(true);
		$oMessage->validate();

		if ($oMessage->getFilterCallback() == null) {
			if ($this->getFilterCallback() != null) {
				$oMessage->setFilterCallback($this->getFilterCallback());
			}
		}

		// if the previous routine did not set a callback, try to set the filter instance.
		if ($oMessage->getFilterCallback() == null) {
			if ($oMessage->getFilter() == null) {
				if ($this->getFilter() != null) {
					$oMessage->setFilter($this->getFilter());
				} else {
					throw new \RuntimeException("No filter found. " . "Please set a filter either in the message or in the connector");
				}
			}
		}

		$aParams['apikey'] = $oMessage->getApiKeysAsString();
		$aParams['providerkey'] = $this->sProviderKey;
		$aParams['application'] = $oMessage->getApplication();
		$aParams['event'] = $this->filter($oMessage, $oMessage->getEvent());
		$aParams['description'] = $this->filter($oMessage, $oMessage->getDescription());
		$aParams['priority'] = $oMessage->getPriority();

		if ($oMessage->getUrl() != null) {
			$aParams['url'] = $oMessage->getUrl();
		}

		array_map(create_function('$sAryVal', 'return str_replace("\\n","\n", $sAryVal);'), $aParams);

		$sContextUrl = $this->sPushEndpoint;

		if (!$this->bIsPostRequest) {
			$sContextUrl .= '?';
		}

		$sParams = http_build_query($aParams);
		$sReturn = $this->execute($sContextUrl, $this->bIsPostRequest, $sParams);

		$this->oLastResponse = \Prowl\Response::fromResponseXml($sReturn);

		return $this->oLastResponse;
	}

	/**
	 * Decides based on the presence of a closure or a filter
	 * which way to go for filtering.
	 *
	 * @throws \RuntimeException
	 * @param \Prowl\Message $oMessage
	 * @param string $sContent
	 * @return string
	 */
	private function filter(\Prowl\Message $oMessage, $sContent) {
		if ($oMessage->getFilterCallback() != null) {
			$cFilter = $oMessage->getFilterCallback();
			return $cFilter($sContent);
		} elseif ($oMessage->getFilter() != null) {
			$oFilter = $oMessage->getFilter();
			return $oFilter->filter($sContent);
		} else {
			throw new \RuntimeException("No filter set, abort.");
		}
	}

	/**
	 * Requests a token for a user registration. This is the first step.
	 * Be sure to set the provider key first! This call doesn't lower your
	 * global remaining call-count.
	 *
	 * @throws \RuntimeException
	 * @return \Prowl\Response
	 */
	public function retrieveToken() {
		if (empty($this->sProviderKey)) {
			throw new \RuntimeException("Cannot execute retrieve/token without a provider key.");
		}

		$aParams = array('providerkey' => $this->sProviderKey);
		$sRequestUrl = 'retrieve/token?';
		$sParams = http_build_query($aParams);

		// This request is GET-only.
		$sReturn = $this->execute($sRequestUrl, $this->bIsPostRequest, $sParams);
		$this->oLastResponse = \Prowl\Response::fromResponseXml($sReturn);

		return $this->oLastResponse;
	}

	/**
	 * Requests a token for a user registration. This is the first step.
	 * Be sure to set the provider key first! This call doesn't lower your
	 * global remaining call-count.
	 *
	 * @throws \RuntimeException
	 * @return \Prowl\Response
	 */
	public function retrieveApiKey($sToken) {
		if (empty($this->sProviderKey)) {
			throw new \RuntimeException("Cannot execute retrieve/apikey without a provider key.");
		}

		$aParams = array('providerkey' => $this->sProviderKey, 'token' => $sToken);
		$sRequestUrl = 'retrieve/apikey?';
		$sParams = http_build_query($aParams);

		// This request is GET-only.
		$sReturn = $this->execute($sRequestUrl, $this->bIsPostRequest, $sParams);
		$this->oLastResponse = \Prowl\Response::fromResponseXml($sReturn);

		return $this->oLastResponse;
	}


	/**
	 * The remaining requests
	 *
	 * @throws \RuntimeException
	 * @return integer
	 */
	public function getRemaining() {
		if (is_null($this->oLastResponse)) {
			throw new \RuntimeException('Cannot access last response. Did you made a request?');
		}
		return $this->oLastResponse->getRemaining();
	}

	/**
	 * The reset date by last response.
	 *
	 * @throws \RuntimeException
	 * @return integer
	 */
	public function getResetDate() {
		if (is_null($this->oLastResponse)) {
			throw new \RuntimeException('Cannot access last response. Did you made a request?');
		}
		return $this->oLastResponse->getResetDate();
	}

	/**
	 * Sets the usage of SSL via cURL. Default is true!
	 * 
	 * @param boolean $bUseSwitch
	 * @return void
	 */
	public function useSSL($bUseSwitch) {
		$this->bUseCurlSSL = $bUseSwitch;
	}

	/**
	 * Executes the request via cUrl and returns the response.
	 *
	 * @param string	 $sUrl			 The resource context
	 * @param boolean	 $bIsPostRequest	Is it a post request?
	 * @param string	 $sParams		The urlencode'ed params.
	 * @return string
	 */
	protected function execute($sUrl, $bIsPostRequest = false, $sParams = null) {

		if ($bIsPostRequest == false) {
			$sUrl .= $sParams;
		}

		//TODO Make this more reliable
		$sHackedUrl = $this->sApiUrl . $sUrl;
		if ($this->bUseCurlSSL === true) {
			$sHackedUrl = str_replace("http://", "https://", $sHackedUrl);
		}

		$this->rCurl = curl_init($sHackedUrl);

		curl_setopt($this->rCurl, CURLOPT_HEADER, 0);
		curl_setopt($this->rCurl, CURLOPT_USERAGENT, "Prowl PHP Client/" . $this->sVersion);
		curl_setopt($this->rCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

		if ($this->bUseCurlSSL === true) {
			curl_setopt($this->rCurl, CURLOPT_SSL_VERIFYPEER, false);
		}

		curl_setopt($this->rCurl, CURLOPT_RETURNTRANSFER, 1);

		if ($bIsPostRequest) {
			curl_setopt($this->rCurl, CURLOPT_POST, 1);
			curl_setopt($this->rCurl, CURLOPT_POSTFIELDS, $sParams);
		}

		if ($this->bUseProxy) {
			curl_setopt($this->rCurl, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($this->rCurl, CURLOPT_PROXY, $this->sProxyUrl);
			curl_setopt($this->rCurl, CURLOPT_PROXYUSERPWD, $this->sProxyPasswd);
		}

		$sReturn = curl_exec($this->rCurl);
		curl_close($this->rCurl);
		return $sReturn;
	}

	/**
	 * Sets the proxy server.
	 *
	 * @since  0.3.1
	 * @param  string $sProxy			 The URL to a proxy server.
	 * @param  string $sUserPassword	The Password for the server (opt.)
	 * @return \Prowl\Connector
	 */
	public function setProxy($sProxy, $sUserPasswd = null) {
		$mUrl = filter_var((string)$sProxy, FILTER_VALIDATE_URL);
		if ($mUrl !== false) {
			$this->bUseProxy = true;
			$this->sProxyUrl = $mUrl;

			if (is_string($sUserPasswd)) {
				$this->sProxyPasswd = (string)$sUserPasswd;
			}
		}
		return $this;
	}
}