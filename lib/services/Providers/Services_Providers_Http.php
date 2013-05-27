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
     * List of files to upload
     *
     * @var mixed array with 4 elements: name, filename, mime, and data
     */
    private $_uploadFiles = null;
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
     * @param $file sarray|null additional headers to send
     * @param $rawPostData array|null raw post data we will be sending
     * @return void
     */
    private function addPostFieldsToCurl($ch, $postFields, $files, $rawPostData) {
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
            foreach($files as $key => $val) {
                $body[] = '--' . $boundary;
                $body[] = 'Content-Disposition: form-data; name="' . $val['name'] . '"; filename="' . $val['filename'] . '"';
                $body[] = 'Content-Type: ' . $val['mime'];
                $body[] = '';
                $body[] = $val['data'];
            } # foreach
        } # if

        /*
         * If we are either posting fields or files, we can just set the
         * mime type automatically, else we will use the header information
         * as passed by the caller.
         */
        if ( ($files != null) || ($postFields != null)) {
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
        } else {
            /*
             * We are pasting a raw data stream, so ask the caller for
             * extra information
             */
            $contentType = $this->getContentType();
            $contentLength = strlen($rawPostData);
            $content = $rawPostData;
        } # else

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
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
     * @return mixed array with first element the HTTP code, and second with the data (if any)
     */
    public function perform($url, $lastModTime) {
        SpotTiming::start(__FUNCTION__);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0) Gecko/20100101 Firefox/8.0');
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

        /*
         * If specified, pass authorization for this request
         */
        $username = $this->getUsername();
        if (!empty($username)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->getUsername() . ':' . $this->getPassword());
        } // # if

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
             ($this->getUploadFiles() != null)) &&
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

        $response = curl_exec($ch);

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
                $data = substr($response, -$curl_info['download_content_length']);
            } else {
                $data = '';
            } # else

        } else {
            $http_code = 700; # Curl returned an error
            $curl_info = curl_getinfo($ch);
            $data = '';
        } # else

        curl_close($ch);

        SpotTiming::stop(__FUNCTION__, array($url));
        return array('http_code' => $http_code,
                     'data' => $data,
                     'curl_info' => $curl_info);
    } # performGet
	
	/* 
	 * Retrieves an URL from the web and caches it when so required
	 */
	function performCachedGet($url, $storeWhenRedirected, $ttl = 900) {
		SpotTiming::start(__FUNCTION__);
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

		SpotTiming::stop(__FUNCTION__, array($url, $storeWhenRedirected, $ttl));

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
} # Services_Providers_Http
