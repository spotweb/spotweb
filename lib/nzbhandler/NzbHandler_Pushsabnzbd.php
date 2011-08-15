<?php
define("SABNZBD_TIMEOUT",15);

class NzbHandler_Pushsabnzbd extends NzbHandler_abs
{
	private $_url = null;

	private $_sabnzbd = null;
	
	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'SABnzbd', 'SAB', $nzbHandling);
		
		$sabnzbd = $nzbHandling['sabnzbd'];
		
		$this->_sabnzbd = $sabnzbd;
		
		# prepare sabnzbd url
		$this->_url = $sabnzbd['url'] . 'sabnzbd/api?mode=addfile&apikey=' . $sabnzbd['apikey'] . '&output=text';
	} # __construct
	
	public function processNzb($fullspot, $nzblist)
	{
		$nzb = $this->prepareNzb($fullspot, $nzblist);
		$title = urlencode($this->cleanForFileSystem($fullspot['title']));
		$category = urlencode($this->convertCatToSabnzbdCat($fullspot));

		# yes, using a local variable instead of the member variable is intentional
		$url = $this->_url . '&nzbname=' . $title . '&cat=' . $category;

		@define('MULTIPART_BOUNDARY', '--------------------------'.microtime(true));
		# equivalent to <input type="file" name="nzbfile"/>
		@define('FORM_FIELD', 'nzbfile'); 

		# dit is gecopieerd van:
		#	http://stackoverflow.com/questions/4003989/upload-a-file-using-file-get-contents

		# creeer de header
		$header = 'Content-Type: multipart/form-data; boundary='.MULTIPART_BOUNDARY;

		# bouw nu de content
		$content = "--" . MULTIPART_BOUNDARY . "\r\n";
		$content .= 
            "Content-Disposition: form-data; name=\"" . FORM_FIELD . "\"; filename=\"" . $nzb['filename'] . "\"\r\n" .
			"Content-Type: " . $nzb['mimetype'] . "\r\n\r\n" . 
			$nzb['nzb'] ."\r\n";
			
		# signal end of request (note the trailing "--")
		$content .= "--".MULTIPART_BOUNDARY."--\r\n";

		$output = $this->sendHttpRequest('POST', $url, $header, $content, SABNZBD_TIMEOUT);
		
		if ($output	=== false)
		{
			error_log("Unable to open sabnzbd url: " . $url);
			throw new Exception("Unable to open sabnzbd url: " . $url);
		} # if
		
		if (strtolower(trim($output)) != 'ok')
		{
			error_log("sabnzbd returned: " . $output);
			throw new Exception("sabnzbd returned: " . $output);
		} # if
	} # processNzb

	
# --------------------------
# - NzbHandler API methods -	
# --------------------------
	
	/*
	 * Return the supported API functions for this NzbHandler imlementation
	 */
	public function hasApiSupport()
	{
		$api = "getStatus,pauseQueue,resumeQueue,setSpeedLimit,moveDown,moveUp"
			+ ",moveTop,moveBottom,setCategory,setPriority,delete,rename"
			+ ",pause,resume,getVersion";
		
		return $api;
	} # hasApiSupport

	/*
	 * SABnzbd API method: queue
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
		$output = $this->querySabnzbd("mode=queue&output=json");
		$response = json_decode($output, true);
		
		$status = $response['queue'];
		
		$result = array();
		$result['queue']['status'] = $status['status'];
		$result['queue']['paused'] = $status['paused'];
		$result['queue']['speedlimit'] = (int)$status['speedlimit'];
		$result['queue']['freediskspace'] = $status['diskspace2'];
		$result['queue']['totaldiskspace'] = $status['diskspacetotal2'];
		$result['queue']['bytepersec'] = (int)($status['kbpersec'] * 1024);
		$result['queue']['mbremaining'] = $status['mbleft'];
		
		$timeleft = explode(":", $status['timeleft']);
		$secondsremaining = $timeleft[0] * 3600 + $timeleft[1] * 60 + $timeleft[2];  
		$result['queue']['secondsremaining'] = $secondsremaining; 

		$slots = $status['slots'];
		$downloads = array();
		for ($i = 0; $i < count($slots); $i++)
		{
			$downloads[$i]['paused'] = ($slots[$i]['status'] == 'Paused');
			$downloads[$i]['id'] = $slots[$i]['nzo_id'];
			$downloads[$i]['filename'] = $slots[$i]['filename'];
			$downloads[$i]['category'] = $slots[$i]['cat'];
			$downloads[$i]['mbsize'] = $slots[$i]['mb'];
			$downloads[$i]['mbremaining'] = $slots[$i]['mbleft'];
			$downloads[$i]['percentage'] = $slots[$i]['percentage'];  
		}
		
		$result['queue']['slots'] = $downloads;
		$result['queue']['nrofdownloads'] = count($downloads); 
		
		return $result;
	} # status

	/*
	 * SABnzbd API method: pause
	 * Pause the download queue
	 */
	public function pauseQueue()
	{
		$output = $this->querySabnzbd("mode=pause&output=json");
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # pauseQueue

	/*
	 * SABnzbd API method: resume
	 * Resume the download queue when paused
	 */
	public function resumeQueue()
	{
		$output = $this->querySabnzbd("mode=resume&output=json");
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # resumeQueue

	/*
	 * SABnzbd API method: config
	 * Set the maximum download rate
	 */
	public function setSpeedLimit($limit)
	{
		$output = $this->querySabnzbd("mode=config&name=speedlimit&value=" . $limit . "&output=json");
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # setSpeedLimit

	/*
	 * SABnzbd API method: switch
	 * Move a download one position down in the queue
	 */
	public function moveDown($id)
	{
		$output = $this->querySabnzbd("mode=queue&output=json");
		$response = json_decode($output, true);
		
		$slots = $response['queue']['slots'];
		$totaldownloads = count($slots);
		$position = -1;		
		for ($i = 0; $i < $totaldownloads; $i++)
		{
			if ($slots[$i]['nzo_id'] == $id)
			{
				$index = $slots[$i]['index'];
				if ($index <= ($totaldownloads - 1)) // we can't go lower than the bottom of the queue
				{
					$output = $this->querySabnzbd("mode=switch&value=" . $id . "&value2=" . ($index+1));
					
					$response = json_decode($output, true);
					$position = $response['result']['position'];
				}
				break; // we're done, exit loop		
			}
		}
		
		return ($position != -1);		
	} # moveDown

	/*
	 * SABnzbd API method: switch
	 * Move a download one position up in the queue
	 */
	public function moveUp($id)
	{
		$output = $this->querySabnzbd("mode=queue&output=json");
		$response = json_decode($output, true);

		$status = $response['queue'];
		$slots = $status['slots'];
		
		$totaldownloads = count($slots);
		$position = -1;		
		for ($i = 0; $i < $totaldownloads; $i++)
		{
			if ($slots[$i]['nzo_id'] == $id)
			{
				$index = $slots[$i]['index'];
				if ($index >= 0) // we can't go beyond the top of the queue
				{
					$output = $this->querySabnzbd("mode=switch&value=" . $id . "&value2=" . ($index-1));
					
					$response = json_decode($output, true);
					$position = $response['result']['position'];
				}
				break; // we're done, exit loop		
			}
		}

		return ($position != -1);	
	} # moveUp

	/*
	 * SABnzbd API method: switch
	 * Move a download to the top of the queue
	 */
	public function moveTop($id)
	{
		$output = $this->querySabnzbd("mode=switch&value=" . $id . "&value2=0");
		$response = json_decode($output, true);

		return ($response['status'] == true);		
	} # moveTop

	/*
	 * SABnzbd API method: switch
	 * Move a download to the bottom of the queue
	 */
	public function moveBottom($id)
	{
		$output = $this->querySabnzbd("mode=switch&value=" . $id . "&value2=-1");
		$response = json_decode($output, true);

		return ($response['status'] == true);		
	} # moveBottom

	/*
	 * SABnzbd API method: change_cat
	 * Set the category for a download
	 */
	public function setCategory($id, $category)
	{
		$output = $this->querySabnzbd("mode=change_cat&value=" .$id . "&value2=" . $category);
		$response = json_decode($output, true);

		return ($response['status'] == true);		
	} # setCategory

	/*
	 * SABnzbd API method: queue
	 * Set the priority for a download
	 */
	public function setPriority($id, $priority)
	{
		$output = $this->queurySabnzbd("mode=queue&name=priority&value=" . $id . "&value2=" . $priority);
		$response = json_decode($output, true);

		return ($response['status'] == true);
		
	} # setPriority

	/*
	 * SABnzbd API method: -
	 * Not implemented yet.
	 */
	public function setPassword($id, $password)
	{
		return false;
	} # setPassword

	/*
	 * SABnzbd API method: queue
	 * Delete a download from the queue
	 */
	public function delete($id)
	{
		$output = $this->querySabnzbd("mode=queue&name=delete&value=" . $id . "&output=json");
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # delete

	/*
	 * SABnzbd API method: queue
	 * Rename a download
	 */
	public function rename($id, $name)
	{
		$output = $this->querySabnzbd("mode=queue&name=rename&value=" . $id . "&value2=THENEWNAME" . $name);
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # rename

	/*
	 * SABnzbd API method: queue
	 * Pause a download in the queue
	 */
	public function pause($id)
	{
		$output = $this->querySabnzbd("mode=queue&name=pause&value=" . $id);
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # pause
	
	/*
	 * SABnzbd API method: queue
	 * Resume a paused download in the queue
	 */
	public function resume($id)
	{
		$output = $this->querySabnzbd("mode=queue&name=resume&value=" . $id);

		var_dump($output);
		
		$response = json_decode($output, true);

		return ($response['status'] == true);
	} # resume

	/*
	 * SABnzbd API method: queue
	 * Get list of categories from SABnzbd
	 * The 'readonly' name/value pair is set to true to inform a template that SABnzbd
	 * does not support assigning ad-hoc categories.
	 */
	public function getCategories()
	{
		$output = $this->querySabnzbd("mode=queue&output=json");
		$response = json_decode($output, true);
		
		$categories = $response['queue']['categories'];

		$result = array();
		$result['readonly'] = true;	// inform the GUI to not allow adding of adhoc categories
		$result['categories'] = $categories;
		
		return $result;
	} # getCategories
	
	/*
	 * SABnzbd API method: version
	 * Returns the version of SABnzbd
	 */
	public function getVersion()
	{
		$output = $this->querySabnzbd("mode=version&output=json");
		$response = json_decode($output, true);

		return $response['version'];
	} # getVersion
	
	/*
	 * Method used to query the SABnzbd API methods
	 */
	private function querySabnzbd($request)
	{
		$url = $this->_sabnzbd['url'] . "sabnzbd/api?" . $request . '&apikey=' . $this->_sabnzbd['apikey'] . '&output=json';
		$output = @file_get_contents($url);
		
		return $output;
	} # querySabnzbd
		
} # class NzbHandler_Pushsabnzbd
