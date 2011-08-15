<?php

class NzbHandler_Nzbget extends NzbHandler_abs
{
	private $_host = null;
	private $_timeout = null;
	private $_url = null;
	private $_credentials = null;

	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'NZBGet', 'D/L', $nzbHandling);
				
		$nzbget = $nzbHandling['nzbget'];
		$this->_host = $nzbget['host'];
		$this->_timeout = $nzbget['timeout'];
		$this->_url = "http://" . $nzbget['host'] . ":" . $nzbget['port'] . "/jsonrpc";
		$this->_credentials = base64_encode($nzbget['username'] . ":" . $nzbget['password']);
	} # __construct

	public function processNzb($fullspot, $nzblist)
	{
		$filename = $this->cleanForFileSystem($fullspot['title']) . '.nzb';
		# nzbget does not support zip files, must merge
		$nzb = $this->mergeNzbList($nzblist); 
		$category = $this->convertCatToSabnzbdCat($fullspot);

		return $this->uploadNzb($filename, $category, false, $nzb);
	} # processNzb

	private function sendRequest($method, $args)
	{
		$reqarr = array('version' => '1.1', 'method' => $method, 'params' => $args);
		$content = json_encode($reqarr);
		
		# creeer de header
		$header = "Host: ". $this->_host . "\r\n".
			"Authorization: Basic " . $this->_credentials . "\r\n".
			"Content-type: application/json\r\n".
			"Content-Length: " . strlen($content) . "\r\n" .
			"\r\n";		
		
		$output = $this->sendHttpRequest('POST', $this->_url, $header, $content, $this->_timeout);

		if ($output === false)
		{
			error_log("ERROR: Could not decode json-data for NZBGet method '" . $apiCall ."'");
			throw new Exception("ERROR: Could not decode json-data for NZBGet method '" . $apiCall ."'");
		}

		$response = json_decode($output, true);
		if (is_array($response) && isset($response['error']) && isset($response['error']['code']))
		{
			error_log("NZBGet RPC: Method '" . $method . "', " . $response['error']['message'] . " (" . $response['error']['code'] . ")");
			throw new Exception("NZBGet RPC: Method '" . $method . "', " . $response['error']['message'] . " (" . $response['error']['code'] . ")");
		}
		else if (is_array($response) && isset($response['result']))
		{
			$response = $response['result'];
		}
		
		return $response;
	} # sendRequest
	
	# NzbHandler API functions
	
	/*
	 * Return the supported API functions for this NzbHandler imlementation
	 */
	public function hasApiSupport()
	{
		$api = "getStatus,pauseQueue,resumeQueue,setSpeedLimit,moveDown,moveUp"
			. ",moveTop,moveBottom,setCategory,delete,pause,resume,getVersion";
				
		// add functions for NZBGet v0.8.0 and higher
		if ($this->getVersion() >= "0.8.0")
		{
			$api .= ",setPriority,rename";
		}
		
		return $api;
	} # hasApiSupport

	/*
	 * NZBGet API method: append
	 * Add an NZB file to download queue 
	 */
	public function uploadNzb($filename, $category, $addToTop, $nzb)
	{
		$args = array($filename, $category, $addToTop, base64_encode($nzb));

		return $this->sendrequest('append', $args);
	} # nzbgetApi_append
	
	/*
	 * NZBGet API method: status
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
		$status = $this->sendrequest('status', null);
		$listgroups = $this->sendrequest('listgroups', null);
		
		$result = array();
		
		if ($status['ServerPaused'] != true)
		{
			$result['queue']['status'] = ($status['ServerStandBy'] == true)?"Idle":"Active";
		}
		else
		{
			$result['queue']['status'] = 'Paused';
		}
		
		$result['queue']['paused'] = $status['ServerPaused'];
		$result['queue']['speedlimit'] = $status['DownloadLimit'];
		$result['queue']['freediskspace'] = "-";
		$result['queue']['totaldiskspace'] = "-";
		$result['queue']['bytepersec'] = $status['DownloadRate'];
		$result['queue']['mbsize'] = 0;
		$result['queue']['mbremaining'] = $status['RemainingSizeMB'];
		
		$secondsremaining = 0;
		if ($status['DownloadRate'] != 0)
		{
			if ($status['RemainingSizeLo'] < 0)
			{
				$secondsremaining = $status['RemainingSizeMB'] / ($status['DownloadRate'] / 1024 /1024);
			}
			else
			{
				$secondsremaining = $status['RemainingSizeLo'] / $status['DownloadRate'];
			}
		}
		
		$result['queue']['secondsremaining'] = (int)($secondsremaining); 
	
		$downloads = array();
		for ($i = 0; $i < count($listgroups); $i++)
		{
			$downloads[$i]['paused'] = ($listgroups[$i]['PausedSizeLo'] > 0);
			$downloads[$i]['id'] = $listgroups[$i]['LastID'];
			$downloads[$i]['filename'] = $listgroups[$i]['NZBNicename'];
			$downloads[$i]['category'] = $listgroups[$i]['Category'];
			$downloads[$i]['mbsize'] = $listgroups[$i]['FileSizeMB'];
			$downloads[$i]['mbremaining'] = $listgroups[$i]['RemainingSizeMB'];

			$downloads[$i]['percentage'] = 0;
			if ($listgroups[$i]['FileSizeMB'] > 0)
			{
				$downloads[$i]['percentage'] = round((($listgroups[$i]['FileSizeMB'] - $listgroups[$i]['RemainingSizeMB']) / $listgroups[$i]['FileSizeMB']) * 100);
			}

			$result['queue']['mbsize'] = $result['queue']['mbsize'] + $downloads[$i]['mbsize']; 
		}

		$result['queue']['slots'] = $downloads;
		$result['queue']['nrofdownloads'] = count($downloads);		
		
		return $result;
	} # getStatus

	/*
	 * NZBGet API method: pause
	 * Pause the download queue
	 */
	public function pauseQueue()
	{
		return $this->sendrequest('pause', null);
	} #pauseQueue

	/*
	 * NZBGet API method: resume
	 * Resume the download queue when paused
	 */
	public function resumeQueue()
	{
		return $this->sendrequest('resume', null);
	} # resumeQueue

	/*
	 * NZBGet API method: rate
	 * Set the maximum download rate
	 */
	public function setSpeedLimit($limit)
	{
		$args = array((int)$limit);

		return $this->sendrequest('rate', $args);
	} # setSpeedLimit
	
	/*
	 * NZBGet API method: editqueue
	 * Move a download one position down in the queue
	 */
	public function moveDown($id)
	{
		$args = array('groupmoveoffset', (int)1, '', (int)$id);
		
		return $this->sendrequest('editqueue', $args);
	} # moveDown
	
	/*
	 * NZBGet API method: editqueue
	 * Move a download one position up in the queue
	 */
	public function moveUp($id)
	{
		$args = array('groupmoveoffset', (int)-1, '', (int)$id);
		
		return $this->sendrequest('editqueue', $args);
	} # moveUp

	/*
	 * NZBGet API method: editqueue
	 * Move a download to the top of the queue
	 */
	public function moveTop($id)
	{
		$args = array('groupmovetop', 0, '', (int)$id);
		
		return $this->sendrequest('editqueue', $args);
	} # moveTop

	/*
	 * NZBGet API method: editqueue
	 * Move a download to the bottom of the queue
	 */
	public function moveBottom($id)
	{
		$args = array('groupmovebottom', 0, '', (int)$id);
		
		return $this->sendrequest('editqueue', $args);
	} # moveBottom
	
	/*
	 * NZBGet API method: editqueue
	 * Set the category for a download
	 */
	public function setCategory($id, $category)
	{
		$args = array('groupsetcategory', (int)0, $category, (int)$id);

		return $this->sendrequest('editqueue', $args);
	} # setCategory

	/*
	 * NZBGet API method: editqueue
	 * Set the priority for a download
	 * Only supported when using NZBGet v0.8.0 (or higher)
	 */
	public function setPriority($id, $priority)
	{
		if ($this->getVersion() < "0.8.0") return false;
		
		# parse integer value a string
		$priority = '' + $priority;
		$args = array('groupsetpriority', (int)0, $priority, (int)$id);

		return $this->sendrequest('editqueue', $args);
	} # setPriority

	/*
	 * NZBGet API method: -
	 * Not implemented yet. Could be added using the editqueue method and using the
	 * GroupSetParameter parameter to set a postprocessing parameter. This would however
	 * also require support in the used post-process script.
	 */
	public function setPassword($id, $password)
	{
		return false;
	} # setPassword
	
	/*
	 * NZBGet API method: editqueue
	 * Delete a download from the queue
	 */
	public function delete($id)
	{
		$args = array('groupdelete', (int)0, '', (int)$id);

		return $this->sendrequest('editqueue', $args);
	} # delete
	
	/*
	 * NZBGet API method: editqueue
	 * Rename a download
	 * Only supported when using NZBGet v0.8.0 (or higher)
	 */
	public function rename($id, $name)
	{
		if ($this->getVersion() < "0.8.0") return false;
		
		$name = cleanForFileSystem($name);

		$args = array('groupsetname', (int)0, $name, (int)$id);

		return $this->sendrequest('editqueue', $args);		
	} # rename
	
	/*
	 * NZBGet API method: editqueue
	 * Pause a download in the queue
	 */
	public function pause($id)
	{
		$args = array('grouppause', (int)0, '', (int)$id);

		return $this->sendrequest('editqueue', $args);
	} # pause

	/*
	 * NZBGet API method: editqueue
	 * Resume a paused download in the queue
	 */
	public function resume($id)
	{
		$args = array('groupresume', (int)0, '', (int)$id);

		return $this->sendrequest('editqueue', $args);
	} # resume

	/*
	 * NZBGet API method: -
	 * Since NZBGet will simply create a category directory if it does not exist yet,
	 * NZBGet does not have a fixed list of categories. Therefor we'll use the list of
	 * categories defined in SpotWeb.
	 * The 'readonly' name/value pair is set to false to allow for a template to offer a
	 * free text field so that the user can assign a category name not defined in the
	 * category list.
	 */
	public function getCategories()
	{
		$result = parent::getCategories();

		// allow adding of adhoc categories
		$result['readonly'] = false;
		
		return $result;
	} # getCategories

	/*
	 * NZBGet API method: version
	 * Returns the version of NZBGet
	 */
	public function getVersion()
	{
		return $this->sendrequest('version', null);
	} # getVersion
	
} # class NzbHandler_Nzbget
