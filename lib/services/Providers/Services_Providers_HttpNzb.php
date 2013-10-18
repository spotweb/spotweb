<?php
/*
 * FIXME
 * XXX
 * TODO
 *
 * Need to use the standard Services_Providers_Http class
 *
 */
/**
 *
 * This class is used to find alternate download urls for nzb's.
 *
 */
class Services_Providers_HttpNzb {
    protected $spot = null;
    protected $alternateDownloadUrl = null;
    protected $nzb = null;
    protected $_cacheDao = null;

    public function __construct(array $spot, Dao_Cache $cacheDao) {
        $this->spot = $spot;
        $this->_cacheDao = $cacheDao;
    }

    public function hasNzb() {
        // Check if we can get an nzb.
        if ($this->getNzb()) {
            return true;
        }

        return false;
    }

    /**
     *
     * Returns nzb file in xml format.
     */
    public function getNzb() {
        if ($this->nzb) {
            // \O/ We already found an nzb before. Return the xml!
            return $this->nzb;
        } else if ($this->nzb === false) {
            // We already did a http request and this results in a badly formed xml.
            return null;
        }

        // Get the alternate url.
        $url = $this->getUrlForSpot();

        // If there is no alternate url return;
        if (!$url) {
            SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->getnzb(), failed to find url for spot');

            $this->nzb = false;
            return null;
        }

        SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->getnzb(), url: ' . $url);

        // Initialize http class.
        $svcHttp = new Services_Providers_Http($this->_cacheDao);
        $result = $svcHttp->perform($url);

        // Check if any error occured
        if ($result['successful']) {
            // Load the body into simplexml.
            // If the xml is well formed this will result in true thus returning the xml.
            // Suppress errors if the string is not well formed, where testing here.
            $body = $result['data'];
            if (@simplexml_load_string($body)) {
                SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->getnzb(), found nzb');

                $this->nzb = $body;
                return $this->nzb;
            } else if ($body) {
                SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->getnzb(), found body but not a valid XML');

                // we did not get a direct link to an nzb file.
                // more parsing is needed t(*_*t)
                $this->nzb = $this->downloadNzbFrom($url, $body);
                return $this->nzb;
            } else {
                $this->nzb = false;
            }
        } else {
            trigger_error($result['errorstr']);
        }

        // Return nothing we have a http error.
        return null;
    }

    /**
     *
     * Check if we have an url and return it if there is one.
     * @return String returns the url or null
     */
    function getUrlForSpot() {
        if (!$this->hasUrlForSpot()) {
            $this->nzb = false;
            return null;
        }

        return $this->alternateDownloadUrl;
    }

    /**
     *
     * Check for specific string to check if we have an alternate download url.
     */
    public function hasUrlForSpot() {
        if ($this->alternateDownloadUrl) {
            return true;
        }

        // Array containing url matches. Must contain the first part of the url.
        $matches = array(
            'http://base64.derefer.me',
            'http://derefer.me',
            'http://alturl.com',
            'http://tiny.cc',
            'http://bit.ly',
            'http://goo.gl',
            'http://hideref.org',
            'http://tiny.cc',
            'http://www.dereferer.org',
        );

        // Search in the website url
        if (isset($this->spot['website'])) {
            SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->hasUrlForSpot(), website = ' . $this->spot['website']);

            foreach ($matches as $needle) {
                SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->hasUrlForSpot(), website needle = ' . $needle);

                if (strpos($this->spot['website'], $needle) !== false) {
                    SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->hasUrlForSpot(), website match');

                    // Stop search we have a match
                    $this->alternateDownloadUrl = $this->resolveUrl($this->spot['website']);
                    if ($this->alternateDownloadUrl === false) {
                        return false;
                    } else {
                        return true;
                    } # else

                }
            }
        }

        // We have no alternate yet lets spider the description.
        if (isset($this->spot['description'])) {
            foreach ($matches as $needle) {
                SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->hasUrlForSpot(), content looking for ' . $needle);

                if (strpos($this->spot['description'], $needle) !== false) {
                    SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->hasUrlForSpot(), needle match');

                    // Stop search we have a match, get the url from the description
                    $url = false;
                    preg_match('/\>(' . str_replace('/', '\/', preg_quote($needle)) . '.*)\</', $this->spot['description'], $matches);

                    if (isset($matches[1])) {
                        $url = $matches[1];
                    } else {
                        /*
                         * We get the body before it has been converted
                         * to HTML from the UBB code. We try to extract
                         * the URL from the body anyway
                         */
                        preg_match("(([^=])((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\))+[\w\d:#@%/;$()~_?\+-=\\\.&]*))", $this->spot['description'], $matches);
                        if (isset($matches[2])) {
                            $url = $matches[2];
                        } # if
                    } # else

                    SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->hasUrlForSpot(), needle match, url: ' . $url);

                    if ($url) {
                        $this->alternateDownloadUrl = $this->resolveUrl($url);

                        if ($this->alternateDownloadUrl === false) {
                            return false;
                        } else {
                            return true;
                        } # else
                    }
                }
            }
        }

        $this->nzb = false;
        return false;
    }

    /**
     *
     * Find the alternate url
     * @param $url String containing alternate url.
     * @return boolean could the file be resolved?
     */
    protected function resolveUrl($url) {
        SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->resolveUrl(), url=' . $url);

        // Initialize download retrieval class
        $svcHttp = new Services_Providers_Http($this->_cacheDao);
        $result = $svcHttp->perform($url);

        // Check if any error occured
        if (!$result['successful']) {
            SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->resolveUrl(), not succesful=' . $result['errorstr']);
            return false;
        } # if

        // Execute (save responce to var for manual following redirects)
        return $result['finalurl'];
    }

    /**
     * Cases for calling the specific parse methods
     *
     * @param String $url
     * @param String $body
     * @return bool|mixed
     */
    protected function downloadNzbFrom($url, $body) {
        SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->downloadNzbfrom() ' . $url);


        // Binsearch
        if (strpos($url, 'binsearch.info') !== FALSE) {
            return $this->downloadNzbFromBinsearch($url, $body);
        }

        // NZB Index
        if ((strpos($url, 'nzbindex.nl') !== FALSE) || (strpos($url, 'nzbindex.com') !== FALSE)) {
            // This function does not use the $body var.
            return $this->downloadNzbFromNzbindex($url);
        }

        // NZB Club
        if (strpos($url, 'nzbclub.com') !== FALSE) {
            // This function does not use the $body var.
            return $this->downloadNzbFromNzbclub($url);
        }

        // No support found return ;(
        return false;
    }

    /**
     * Tries to download the actual nzb from binsearch
     *
     * @param String $url
     * @param String $body
     * @return bool|mixed
     */
    protected function downloadNzbFromBinsearch($url, $body) {
        // Match to get the nzb id.
        preg_match('/\q\=([a-z0-9]*)&*/i', $url, $matches);

        // This match is essential for the download
        if (!count($matches)) {
            return false;
        }

        // Hardcoded download url.
        $downloadUrl = 'http://www.binsearch.info/fcgi/nzb.fcgi?q=' . $matches[1];

        $dom = new DOMDocument;

        // Suppress errors, html does not have to be well formed to function.
        @$dom->loadHTML($body);
        $ids = array();

        // Fetch table rows from the result page.
        foreach ($dom->getElementsByTagName('tr') as $tr) {

            // Only continue parsing if the search query is found in the tr.
            if (strpos($tr->nodeValue, $matches[1]) !== false) {

                // Get all input fields.
                $fields = $tr->getElementsByTagName('input');

                // Check type, we need the checkbox :)
                foreach ($fields as $input) {
                    if ($input->getAttribute('type') == 'checkbox') {

                        // walk up the DOM tree and check if the next element has the string in the name.
                        // this way we only have the download rows left.
                        if (
                            $input->parentNode &&
                            $input->parentNode->nextSibling
                            && strpos($input->parentNode->nextSibling->nodeValue, $matches[1]) !== false
                        ) {

                            // Push name to array. This name is needed to fetch the download.
                            $ids[] = $input->getAttribute('name');
                        }
                    }

                }

            }
        }

        // Fetch the last id assuming our download was the first to post.
        // This step is to prevent accidental porn downloads.
        $id = null;
        if (count($ids)) {
            $id = array_pop($ids);
        }

        // Withoud an id where not going to be able to get the nzb.
        if (!$id) {
            return false;
        }

        $postdata = array(
            'action' => 'nzb',
            $id => $id
        );

        return $this->postAndDownloadNzb($downloadUrl, $postdata);
    }

    /**
     *
     * Execute a POST to the given url and return the body.
     * @param String $url
     * @param array $postdata
     * @return bool|mixed
     */
    protected function postAndDownloadNzb($url, array $postdata) {
        // Initialize download retrieval class
        $svcHttp = new Services_Providers_Http($this->_cacheDao);
        $svcHttp->setPostContent($postdata);
        $svcHttp->setMethod('POST');
        $result = $svcHttp->perform($url);

        // Check if any error occured
        if (!$result['successful']) {
            SpotDebug::msg(SpotDebug::DEBUG, __CLASS__ . '->postAndDownloadNzb(), not succesful=' . $result['errorstr']);
            return false;
        } # if

        // Load the body into simplexml.
        // If the xml is well formed this will result in true thus returning the xml.
        // Suppress errors if the string is not well formed, where testing here.
        if (@simplexml_load_string($result['data'])) {
            return $result['data'];
        } else {
            return false;
        } # else
    }

    /**
     * Tries to download the actual nzb from nzbindex
     *
     * @param String $url
     * @internal param String $body
     * @return bool|mixed
     */
    protected function downloadNzbFromNzbindex($url) {
        // New http request to get the page again
        // This time do a request with the cookie that accepts the Disclaimer agreement.

        // Initialize download retrieval class
        $svcHttp = new Services_Providers_Http($this->_cacheDao);
        $svcHttp->setCookie('agreed=true');
        $result = $svcHttp->perform($url);

        // Check if any error occured
        if (!$result['successful']) {
            trigger_error($result['errorstr']);
            return false;
        }

        // Match to get the nzb id.
        preg_match('/\q\=([a-z0-9]*)/i', $url, $matches);

        // This match is essential for the download
        if (!count($matches)) {
            return false;
        }

        $dom = new DOMDocument;

        // Suppress errors, html does not have to be well formed to function.
        $body = $result['data'];
        @$dom->loadHTML($body);

        // Fetch a tags from the result page.
        foreach ($dom->getElementsByTagName('a') as $a) {

            // Search for the direct nzb download link :)
            if (trim(strtolower($a->nodeValue)) == 'download') {
                $url = $a->getAttribute('href');

                if (!empty($matches[1])) {
                    if (strpos($url, $matches[1]) !== false) {
                        // We just found a direct download link for our url.
                        // No need for posting, just another get using http.
                        return $this->getAndDownloadNzb($url);
                    }
                } # if
            } # if
        } # foreach

        // Could not find the right download link.
        return false;
    }

    /**
     *
     * Curl GET return xml or false if not well formed.
     * @param String $url
     * @return bool|mixed
     */
    protected function getAndDownloadNzb($url) {
        // Initialize download retrieval class
        $svcHttp = new Services_Providers_Http($this->_cacheDao);
        $result = $svcHttp->perform($url);

        // Check if any error occured
        if (!$result['successful']) {
            trigger_error($result['errorstr']);
            return false;
        }

        // Load the body into simplexml.
        // If the xml is well formed this will result in true thus returning the xml.
        // Suppress errors if the string is not well formed, where testing here.
        $body = $result['data'];
        if (@simplexml_load_string($body)) {
            return $body;
        } else {
            return false;
        }

        return false;
    }

    /**
     *
     * Tries to download the actual nzb from nzbclub
     *
     * @param String $url
     * @return bool|mixed
     */
    protected function downloadNzbFromNzbclub($url) {
        $downloadUrl = str_replace('nzb_view', 'nzb_get', $url) . 'nzb';
        return $this->getAndDownloadNzb($downloadUrl);
    }

}

