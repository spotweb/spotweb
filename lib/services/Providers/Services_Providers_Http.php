<?php

class Services_Providers_Http {
	private $_cacheDao;

    /**
     * Wat kind of request should we perform? Currently, only
     * GET or POST are supported
     * @var string
     */
    /**
     * Username for HTTP basic authentication
     * @var string
     */
    private $_username = null;
    /**
     * Token for HTTP Bearer authentication (used by OAuth)
     * @var string
     */
    private $_bearerAuth = null;
    /**
     * Password for HTTP basic authentication
     * @var string
     */
    private $_password = null;
    /**
     * Content-Type for information we are posting to the server
     * @var string
     */
    private $_contentType = null;
    /**
     * Array of 'key' => 'value' pairs, of stuff we want to post
     * or get to the server
     *
     * @var mixed
     */
    private $_postContent = null;
    /**
     * Array of 'key' => 'value' pairs of headers we want to send
     */
    private $_httpHeaders = array();
    /**
     * List of files to upload
     *
     * @var mixed array with 4 elements: name, filename, mime, and data
     */
    private $_uploadFiles = null;
    /**
     * Cookie string to send with request
     *
     * @var string
     */
    private $_cookie = null;
    /**
     * POST data which is intended to be sent as one stream of data to the
     * server without local processing.
     *
     * @var string
     */

	/*
	 * constructor
	 */
	public function __construct(Dao_Cache $cacheDao = null) {
		$this->_cacheDao = $cacheDao;
	}  # ctor

    /**
     * Add files to a POST request with our custom boundary, this way we can
     * send the content-type without having to resort to temporary files.
     *
     * @param $ch resource Handle to cURL resource object
     * @param $postFields array|null fields to post
     * @param $files
     * @param $rawPostData array|null raw post data we will be sending
     * @throws NotImplementedException
     * @throws Exception
     * @internal param null|\sarray $file additional headers to send
     * @return void
     */
    private function addPostFieldsToCurl($ch, $postFields, $files, $rawPostData) {

        /*
         * Files posted to another webserver, need to be in another format
         * than a plain post data to be posted.
         */
        if ($files != null) {
            /*
             * We need to create a unique  boundary string to be used between the
             * different attachments / post fields we use
             */
            $boundary = '----------------------------' . microtime(true);
            $contentType = '';
            $contentLength = 0;

            /*
             * Process the actual fields, we expect (for now) a very basic field system where
             * there is either a key/value pair
             */
            $body = array();
            if ($postFields != null) {
                foreach($postFields as $key => $val) {
                    $body[] = '--' . $boundary;
                    $body[] = 'Content-Disposition: form-data; name="' . $key . '"';
                    $body[] = '';
                    $body[] = urlencode($val);
                } # foreach
            } # if

            # process the file uploads
            if ($files != null) {
                foreach($files as $val) {
                    $body[] = '--' . $boundary;
                    $body[] = 'Content-Disposition: form-data; name="' . $val['name'] . '"; filename="' . $val['filename'] . '"';
                    $body[] = 'Content-Type: ' . $val['mime'];
                    $body[] = '';
                    $body[] = $val['data'];
                } # foreach
            } # if

            # signal end of request (note the trailing "--")
            $body[] = '--' . $boundary . '--';
            $body[] = '';

            /*
             * Content type must be set to multipart/form-data, we join
             * the body array with CR/LF pair and set a correct content
             * length.
             */
            $contentType = 'multipart/form-data; boundary=' . $boundary;
            $content = implode("\r\n", $body);
            $contentLength = strlen($content);

            /*
             * make sure no raw post data was requested
             */
            if (!empty($rawPostData)) {
                throw new Exception("Don't know how to handle post or fileupload and raw postdata");
            } # if
        } elseif (($files == null) && ($postFields != null)) {
            /*
             * If we are not posting files, but are posting POST
             * fields, we just take the easy way out.
             */
            $content = http_build_query($postFields);
            $contentType = 'application/x-www-form-urlencoded';
            $contentLength = strlen($content);
        } elseif ($rawPostData != null) {
            /*
             * We are pasting a raw data stream, so ask the caller for
             * extra information
             */
            $contentType = $this->getContentType();
            $contentLength = strlen($rawPostData);
            $content = $rawPostData;
        } else {
            throw new NotImplementedException('Unknown combination of POST/FILE/RAW posting data');
        } # else

        /*
         * Add our headers to the call
         */
        $this->addHttpHeaders(array(
            'Content-Length: ' . $contentLength,
            'Content-Type: ' . $contentType,
            'Expect: '
        ));


        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    } # addPostFieldsToCurl

    /**
     * Retrieves an uncached GET from the web
     *
     * @param $url string to retrieve
     * @param $lastModTime int Last modification time, can be null
     * @param int $redirTries Amount of tries already passed to follow a redirect
     * @return mixed array with first element the HTTP code, and second with the data (if any)
     */
    public function perform($url, $lastModTime = null, $redirTries = 0) {
        SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

        /*
         * Default our effectiveUrl to be the current URL,
         * so this way we can always return the effectiveUrl
         */
        $effectiveUrl = $url;

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0) Gecko/20100101 Firefox/8.0');
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt ($ch, CURLOPT_ENCODING, '');
        // Don't use fail on error, because sometimes we do want to se
        // the output of the content
        //      curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
        // eg, if a site returns an 400 we might want to know why.
        curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt ($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt ($ch, CURLOPT_VERBOSE, true);

        // send a cookie with the request if defined
        if ($this->getCookie() !== null) {
            curl_setopt ($ch, CURLOPT_COOKIE, $this->getCookie());
        } # if

        // Only use these curl options if no open base dir is set and php mode is off.
        $manualRedirect = false;
        if (ini_get('open_basedir') <> '' || ini_get('safe_mode')) {
            $manualRedirect = true;
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt ($ch, CURLOPT_MAXREDIRS, 1);
        } else {
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
        } # else

        /*
         * If specified, pass authorization for this request
         */
        $username = $this->getUsername();
        if (!empty($username)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->getUsername() . ':' . $this->getPassword());
        } // # if


        /*
         * OAuth 2.0 uses 'Bearer' authentication, we support this by manually sending the
         * HTTP header field
         */
        $bearerAuth = $this->getBearerAuth();
        if (!empty($bearerAuth)) {
            $this->addHttpHeaders(array('Authorization: Bearer ' . $this->getBearerAuth()));
        } # if

        /*
         * Should we be posting?
         */
        if ($this->getMethod() == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } # if

        /*
         * If we are passed fields to post to the server, actuall post them
         */
        if ((($this->getPostContent() != null) ||
             ($this->getUploadFiles() != null) ||
             ($this->getRawPostData() != null)) &&
             ($this->getMethod() == 'POST')) {
            $this->addPostFieldsToCurl($ch, $this->getPostContent(), $this->getUploadFiles(), $this->getRawPostData());
        } # if

        /*
         * If we already have content stored in our cache, just ask
         * the server if the content is modified since our last
         * time this was stored in the cache
         */
        if (($lastModTime != null) && ($lastModTime > 0)) {
            curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
            curl_setopt($ch, CURLOPT_TIMEVALUE, $lastModTime);
        } # if

        /*
         * Send our custom HTTP headers
         */
        $httpHeaders = $this->getHttpHeaders();
        if (!empty($httpHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeaders());
        } # if

        $response = curl_exec($ch);
        $errorStr = curl_error($ch);

        /*
         * Curl returns false on some unspecified errors (eg: a timeout)
         */
        if ($response !== false) {
            $curl_info = curl_getinfo($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            /*
             * Server responded with 304 (Resource not modified)
             */
            if ($http_code != 304) {
                $data = substr($response, $curl_info['header_size']);
            } else {
                $data = '';
            } # else

            /*
             * We also follow redirects, but PHP's safemode doesn't allow
             * for redirects, so fix those as well.
             */
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            if ((
                    ($effectiveUrl != $url) ||
                    ($http_code == 301) ||
                    ($http_code == 302)
                ) &&
                (
                     $manualRedirect
                )
               ) {
                if (preg_match('/Location:(.*?)\n/', $response, $matches)) {
                    $redirUrl = trim(array_pop($matches));

                    $redirTries++;

                    if ($redirTries < 20) {
                        return $this->perform($redirUrl, $lastModTime, $redirTries);
                    } # if
                } # if
            } # if

            // Get the url.
            if (preg_match('/meta.+?http-equiv\W+?refresh/i', $response)) {
                preg_match('/content.+?url\W+?(.+?)\"/i', $response, $matches);
                if (isset($matches[1])) {
                    SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '-perform(), matches[1]= ' . $matches[1]);

                    /*
                     * We can get either an relative redirect, or an fully
                     * qualified redirect. Hideref, for example, uses an
                     * relative direct. Look for those.
                     *
                     * parse_url() doesn't support relative url's, so we have
                     * to do a guess ourselves.
                     */
                    $redirUrl = $matches[1];
                    if ((stripos($redirUrl, 'http://') !== 0) &&
                        (stripos($redirUrl, 'https://') !== 0) &&
                        (stripos($redirUrl, '//') !== 0)) {
                        SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->perform(), we have gotten an correct url');

                        $urlParts = parse_url($url);

                        SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->perform(), parse_url: ' . json_encode($urlParts));

                        if ($redirUrl[0] == '/') {
                            $redirUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $redirUrl;
                        } else {
                            $redirUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . $redirUrl;
                        } # if
                    } # if

                    SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->perform(), after metafresh, url = : ' . $url);
                    $redirTries++;

                    if ($redirTries < 20) {
                        return $this->perform($redirUrl, $lastModTime, $redirTries);
                    } # if
                } # if
            } # if

        } else {
            $http_code = 700; # Curl returned an error
            $curl_info = curl_getinfo($ch);
            $data = '';
        } # else

        curl_close($ch);

        /*
         * Sometimes we get an HTTP error of 0 back, which
         * probably means a timeout or something, so fix up
         * the error string manually.
         */
        if (($errorStr == '') && ($http_code == 0)) {
            $errorStr = 'unable to connect to URL: ' . $url;
        } # if

        SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($url));
        return array('http_code' => $http_code,
                     'data' => $data,
                     'finalurl' => $effectiveUrl,
                     'successful' => ($http_code == 200 || $http_code == 304),
                     'errorstr' => 'http returncode: ' . $http_code . ' / ' . $errorStr,
                     'curl_info' => $curl_info);
    } # performGet
	
	/* 
	 * Retrieves an URL from the web and caches it when so required
	 */
	function performCachedGet($url, $storeWhenRedirected, $ttl = 900) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$url_md5 = md5($url);
		
		/*
		 * Is this URL stored in the cache and is it still valid?
		 */
		$content = $this->_cacheDao->getCachedHttp($url_md5); 
		if ((!$content) || ( (time()-(int) $content['stamp']) > $ttl)) {
            $tmpData = $this->perform($url, $content['stamp']);

			$data = $tmpData['data'];
            $http_code = $tmpData['http_code'];
            $curl_info = $tmpData['curl_info'];

			/*
			 * HTTP return code is other than 200 (OK) and 
			 * other than 304 (Resource not modified),
			 * we have no use for the result
			 */
			if ($http_code != 200 && $http_code != 304) {
				return array($http_code, false);
			} # if

			/* 
			 * A ttl > 0 is specified, meaning we are allowed to
			 * store resources in the cache
			 */
			if ($ttl > 0) {
				switch($http_code) {
					case 304		: {
						/*
						 * Update the timestamp in the database to refresh this
						 * cached resource.
						 */
						$this->_cacheDao->updateHttpCacheStamp($url_md5);
						break;
					} # 304 (resource not modified)

					default 		: {
						/*
						 * Store the retrieved information in the cache
						 */
						if (($storeWhenRedirected) || ($curl_info['redirect_count'] == 0)) {
							$this->_cacheDao->saveHttpCache($url_md5, $data);
						} # if
					} # if
				} # switch

			} # else
		} else {
			$http_code = 304;
			$data = $content['content'];
		} # else

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($url, $storeWhenRedirected, $ttl));

		return array($http_code, $data);
	} # performCachedGet

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->_password = $password;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->_username = $username;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->_username;
    }
    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->_contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * @param mixed $content
     */
    public function setPostContent($content)
    {
        $this->_postContent = $content;
    }

    /**
     * @return mixed
     */
    public function getPostContent()
    {
        return $this->_postContent;
    }
    private $_method = 'GET';

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }
    /**
     * @param null $uploadFiles
     */
    public function setUploadFiles($uploadFiles)
    {
        $this->_uploadFiles = $uploadFiles;
    }

    /**
     * @return null
     */
    public function getUploadFiles()
    {
        return $this->_uploadFiles;
    }
    private $_rawPostData = '';

    /**
     * @param string $rawPostData
     */
    public function setRawPostData($rawPostData)
    {
        $this->_rawPostData = $rawPostData;
    }

    /**
     * @return string
     */
    public function getRawPostData()
    {
        return $this->_rawPostData;
    }
    /**
     * @param string $bearerAuth
     */
    public function setBearerAuth($bearerAuth)
    {
        $this->_bearerAuth = $bearerAuth;
    }

    /**
     * @return string
     */
    public function getBearerAuth()
    {
        return $this->_bearerAuth;
    }

    /**
     * @param mixed $httpHeaders
     */
    public function addHttpHeaders($httpHeaders)
    {
        $this->_httpHeaders = array_merge($this->_httpHeaders, $httpHeaders);
    } # addHttpHeaders

    /**
     * @return mixed
     */
    public function getHttpHeaders()
    {
        return $this->_httpHeaders;
    }

    /**
     * @param null $cookie
     */
    public function setCookie($cookie) {
        $this->_cookie = $cookie;
    } # setCookie

    /**
     * @return null
     */
    public function getCookie() {
        return $this->_cookie;
    } # getCookie


} # Services_Providers_Http
