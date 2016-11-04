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
namespace Prowl {

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
	class SecureConnector extends Connector {

		/**
		 * The API base url.
		 * @var string
		 */
		protected $sApiUrl = 'https://api.prowlapp.com/publicapi/';

		/**
		 * ProwlConnector.class provides access to the
		 * webservice interface of Prowl by using
		 * cUrl + SSL. Use the setters of this class
		 * to provide the mandatory parameters.
		 *
		 * @throws \RuntimeException
		 */
		public function __construct() {

			parent::__construct();

			$curl_info = curl_version(); // Checks for cURL function and SSL version. Thanks Adrian Rollett!
			if (empty($curl_info['ssl_version'])) {
				throw new \RuntimeException('Your cUrl Extension does not support SSL.');
			}
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

			$this->rCurl = curl_init($this->sApiUrl . $sUrl);

			curl_setopt($this->rCurl, CURLOPT_HEADER, 0);
			curl_setopt($this->rCurl, CURLOPT_USERAGENT, "Prowl PHP Client/" . $this->sVersion);
			curl_setopt($this->rCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);


			curl_setopt($this->rCurl, CURLOPT_SSL_VERIFYPEER, false);
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
	}
}