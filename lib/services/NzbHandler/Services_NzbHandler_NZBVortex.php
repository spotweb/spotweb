<?php

class Services_NzbHandler_NZBVortex extends Services_NzbHandler_abs
{
    private $_host = null;
    private $_url = null;
    private $_apikey = null;
    private $_sessionid = null;

    public function __construct(Services_Settings_Container $settings, array $nzbHandling)
    {
        parent::__construct($settings, 'NZBVortex', 'D/L', $nzbHandling);

        $nzbvortex = $nzbHandling['nzbvortex'];
        $this->_host = $nzbvortex['host'];
        $this->_url = 'http://'.$nzbvortex['host'].':'.$nzbvortex['port'].'/api';
        $this->_apikey = $nzbvortex['apikey'];
    }

    // __construct

    public function processNzb($fullspot, $nzblist)
    {
        $response = null;
        foreach ($nzblist as $nzb) {
            $spot = $nzb['spot'];
            $filename = $this->cleanForFileSystem($spot['title']).'.nzb';
            $category = $this->convertCatToSabnzbdCat($spot);
            $data_array = ['filename' => $filename, 'nzb' => $nzb['nzb'], 'category' => $category];
            $response = $this->sendRequest('addnzb', $data_array);
        }

        return $response;
    }

    // processNzb

    private function sendRequest($method, $args)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        if ($this->_sessionid == null) {
            //A. get server nonce for login
            //B. generate a client nonce (cnonce)
            //C. login using api-key, both nonces
            //D. Add a new download & list queue
            $nonce = $this->getNonce();
            if ($nonce != null) {
                $cnonce = $this->gen_uuid();
                $sessionID = $this->login($nonce, $cnonce);
                if ($sessionID != null) {
                    $this->_sessionid = $sessionID;
                } else {
                    $errorStr = 'ERROR: Failed to login, check api-key';
                    error_log($errorStr);

                    throw new Exception($errorStr);
                }
            } else {
                $errorStr = 'ERROR: Failed to get nonce, check server address and make sure NZBVortex is running';
                error_log($errorStr);

                throw new Exception($errorStr);
            }
        }
        if ($this->_sessionid) {
            $sessionID = $this->_sessionid;
            switch ($method) {
                case 'status':
                    $response = $this->webUpdate($args, $sessionID);
                    break;

                case 'addnzb':
                    $response = $this->addNZB($args, $sessionID);
                    break;

                case 'version':
                    $response = $this->getAppVersion();
                    break;

                default:
                    $response = $this->sendCommand($method, $sessionID, $args);
                    break;
            }
            if ($response == 'ERROR403') {
                $this->_sessionid = null;

                return $this->sendRequest($method, $args);
            }
        }

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$method, $args]);

        return $response;
    }

    // sendRequest

    // NzbHandler API functions

    /**
     * Check if handler is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (empty($this->_url)) {
            return false;
        } // if

        try {
            $this->sendrequest('status', null);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /*
     * Return the supported API functions for this NzbHandler imlementation
     */
    public function hasApiSupport()
    {
        $api = 'getStatus,pauseQueue,resumeQueue,setSpeedLimit,moveDown,moveUp'
            .',moveTop,moveBottom,setCategory,delete,pause,resume,getVersion';

        return $api;
    }

    // hasApiSupport

    //======================================
    //     Helper functions
    //======================================

    // Create curl connection with HTTPS options
    private function createCurlConnection($api_call)
    {
        $ch = curl_init($this->_url.$api_call);

        //for debugging
        //$fp = fopen("example_homepage.txt", "w");
        //curl_setopt($ch, CURLOPT_FILE, $fp);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        return $ch;
    }

    // Generate client UUID, we use it for a client nonce (cnonce)
    private function gen_uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    //======================================
    //     Authentication functions
    //======================================

    // nonce is valid for 30 seconds, use it to login within those 30 seconds
    private function getNonce()
    {
        $ch = $this->createCurlConnection('/auth/nonce');

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        if ($code == 200) {
            $result = json_decode($body, true);
            $result = $result['authNonce'];
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);
        //fclose($fp);

        return $result;
    }

    // SessionID received will be invalidated
    // when the session is not being used for 5 minutes
    // re-request when receiving HTTP 403, do not store sessionid
    private function login($nonce, $cnonce)
    {
        $hash_source = $nonce.':'.$cnonce.':'.$this->_apikey;
        $sha256 = hash('sha256', $hash_source, true);
        $hash = base64_encode($sha256);

        $ch = $this->createCurlConnection('/auth/login?nonce='.urlencode($nonce).'&cnonce='.urlencode($cnonce).'&hash='.urlencode($hash));

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        if ($code == 200) {
            $result = json_decode($body, true);
            $result = $result['sessionID'];
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);

        return $result;
    }

    //======================================
    //     API functions
    //======================================

    private function getAppVersion()
    {
        $ch = $this->createCurlConnection('/api/app/appversion');

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        if ($code == 200) {
            $json = json_decode($body, true);
            $result = $json['appversion'];
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);

        return $result;
    }

    private function sendCommand($cmd, $sessionID, $args)
    {
        $pars = '';
        if ($args != null) {
            $pars = $args.'/'.$cmd;
        } else {
            $pars = $cmd;
        }
        $ch = $this->createCurlConnection('/nzb/'.$pars.'?sessionid='.urlencode($sessionID));

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        if ($code == 200) {
            $result = json_decode($body, true);
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);

        return $result;
    }

    private function webUpdate($args, $sessionID)
    {
        $ch = $this->createCurlConnection('/app/webUpdate?sessionid='.urlencode($sessionID));

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        if ($code == 200) {
            $result = json_decode($body, true);
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);

        return $result;
    }

    //Adding a NZB via a File
    private function addNZB($data, $sessionID)
    {
        $filename = $data['filename'];
        $nzbdata = $data['nzb'];
        $category = $data['category'];
        $gn = '';
        if (strlen($category) > 0) {
            $gn = '&groupname='.urlencode($category);
        }

        $ch = $this->createCurlConnection('/nzb/add?sessionid='.urlencode($sessionID).$gn);

        // Write NZB to a temporary file
        $tmpfile = '/tmp/'.$filename;
        $filetype = 'application/octet-stream';

        $myfile = fopen($tmpfile, 'w') or exit('Unable to open file!');
        fwrite($myfile, $nzbdata);
        fclose($myfile);

        $POST_DATA = [
            'upload1' => curl_file_create($tmpfile, $filetype, $filename),
        ];
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POST_DATA);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        if ($code == 200) {
            $result = json_decode($body, true);
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);
        // 'Cleanup' temporary file
        if (!unlink($tmpfile)) {
            $myfile = fopen($tmpfile, 'w') or exit('Unable to open file!');
            fwrite($myfile, '');
            fclose($myfile);
        }

        return $result;
    }

    //List NZBs from queue
    private function listNZBs($sessionID)
    {
        $ch = $this->createCurlConnection('/nzb?sessionid='.urlencode($sessionID));

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = null;

        echo 'HTTP result code: '.$code;
        if ($code == 200) {
            $result = json_decode($body, true);
            foreach ($result['nzbs'] as $nzb) {
                echo 'Found NZB: '.$nzb['uiTitle']."<br>\n";
            }
        } elseif ($code == 403) {
            $result = 'ERROR403';
        }
        curl_close($ch);

        return $result;
    }

    /*
     * Add an NZB file to download queue
     */
    public function uploadNzb($filename, $category, $addToTop, $nzb)
    {
        error_log('filename: '.$filename);
        error_log('category: '.$category);
        error_log('addToTop: '.$addToTop);
        error_log('nzb: '.$nzb);

        $args = [$filename, $category, $addToTop, base64_encode($nzb)];

        return $this->sendrequest('append', $args);
    }

    // uploadNzb

    /*
     * NZBVortex API method: webUpdate
     * The getStatus() method returns a JSON object containing the following
     * name/value pairs:
     *
     * queue.status
     * queue.paused
     * queue.speedlimit
     * queue.freediskspace
     * queue.totaldiskspace
     * queue.bytepersec
     * queue.secondsremaining
     * queue.mbsize
     * queue.mbremaining
     * queue.nrofdownloads
     * download[].paused
     * download[].id
     * download[].filename
     * download[].category
     * download[].mbsize
     * download[].mbremaining
     * download[].percentage
     */
    public function getStatus()
    {
        $status = $this->sendrequest('status', null);

        $result = [];

        $result['queue']['status'] = 'Active';

        $result['queue']['paused'] = false;
        if ($status['speedlimit_enabled'] != 0) {
            $result['queue']['speedlimit'] = $status['speedlimit'];
        } else {
            $result['queue']['speedlimit'] = 0;
        }
        $result['queue']['freediskspace'] = '-';
        $result['queue']['totaldiskspace'] = '-';
        $result['queue']['bytepersec'] = $status['speed'];
        $result['queue']['mbsize'] = 0;
        $result['queue']['mbremaining'] = 0;

        $secondsremaining = 0;
        $result['queue']['secondsremaining'] = (int) ($secondsremaining);

        $downloads = [];
        $i = 0;
        $isPaused = false;
        foreach ($status['nzbs'] as $nzb) {
            $downloads[$i]['paused'] = $nzb['isPaused'];
            $isPaused = $isPaused | $nzb['isPaused'];
            $downloads[$i]['id'] = $nzb['id'];
            $downloads[$i]['filename'] = $nzb['uiTitle'];
            $downloads[$i]['category'] = $nzb['groupName'];
            $downloads[$i]['mbsize'] = round($nzb['totalDownloadSize'] / 1024 / 1024);
            $downloads[$i]['mbremaining'] = round(($nzb['totalDownloadSize'] - $nzb['downloadedSize']) / 1024 / 1024);
            $result['queue']['mbremaining'] = $result['queue']['mbremaining'] + $downloads[$i]['mbremaining'];
            $downloads[$i]['percentage'] = $nzb['progress'];
            $result['queue']['mbsize'] = $result['queue']['mbsize'] + $downloads[$i]['mbsize'];
            $i = $i + 1;
        }
        $result['queue']['paused'] = $isPaused;
        $result['queue']['slots'] = $downloads;
        $result['queue']['nrofdownloads'] = count($downloads);

        return $result;
    }

    // getStatus

    /*
     * NZBVortex API method: pause
     * Pause the download queue
     */
    public function pauseQueue()
    {
        return $this->sendrequest('pause', null);
    }

    //pauseQueue

    /*
     * NZBVortex API method: resume
     * Resume the download queue when paused
     */
    public function resumeQueue()
    {
        return $this->sendrequest('resume', null);
    }

    // resumeQueue

    /*
     * NZBVortex API method: rate
     * Set the maximum download rate
     */
    public function setSpeedLimit($limit)
    {
        return false;
    }

    // setSpeedLimit

    /*
     * NZBVortex API method: movedown
     * Move a download one position down in the queue
     */
    public function moveDown($id)
    {
        return $this->sendrequest('movedown', $id);
    }

    // moveDown

    /*
     * NZBVortex API method: moveup
     * Move a download one position up in the queue
     */
    public function moveUp($id)
    {
        return $this->sendrequest('moveup', $id);
    }

    // moveUp

    /*
     * NZBVortex API method: movetop
     * Move a download to the top of the queue
     */
    public function moveTop($id)
    {
        return $this->sendrequest('movetop', $id);
    }

    // moveTop

    /*
     * NZBVortex API method: movebottom
     * Move a download to the bottom of the queue
     */
    public function moveBottom($id)
    {
        return $this->sendrequest('movebottom', $id);
    }

    // moveBottom

    /*
     * NZBVortex API method:
     * Set the category for a download
     */
    public function setCategory($id, $category)
    {
        return false;
    }

    // setCategory

    /*
     * NZBVortex API method:
     * Set the priority for a download
     */
    public function setPriority($id, $priority)
    {
        return false;
    }

    // setPriority

    /*
     * NZBVortex API method: -
     */
    public function setPassword($id, $password)
    {
        return false;
    }

    // setPassword

    /*
     * NZBVortex API method: cancelDelete
     * Delete a download from the queue
     */
    public function delete($id)
    {
        return $this->sendrequest('cancelDelete', $id);
    }

    // delete

    /*
     * NZBVortex API method: -
     */
    public function rename($id, $name)
    {
        return false;
    }

    // rename

    /*
     * NZBVortex API method: pause
     * Pause a download in the queue
     */
    public function pause($id)
    {
        return $this->sendrequest('pause', $id);
    }

    // pause

    /*
     * NZBVortex API method: resume
     * Resume a paused download in the queue
     */
    public function resume($id)
    {
        return $this->sendrequest('resume', $id);
    }

    // resume

    /*
     * NZBVortex API method: -
     * It is not possible to get the list of set categories from NZBVortex
     * Therefor we'll use the list of categories defined in SpotWeb.
     * The 'readonly' name/value pair is set to false to allow for a template to offer a
     * free text field so that the user can assign a category name not defined in the
     * category list.
     */
    public function getBuiltinCategories()
    {
        $result = parent::getBuiltinCategories();

        // allow adding of adhoc categories
        $result['readonly'] = false;

        return $result;
    }

    // getCategories

    /*
     * NZBVortex API method: version
     * Returns the version of NZBVortex
     */
    public function getVersion()
    {
        return $this->sendrequest('version', null);
    }

    // getVersion
} // class Services_NzbHandler_NZBVortex
