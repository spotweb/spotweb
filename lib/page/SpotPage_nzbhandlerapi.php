<?php
class SpotPage_nzbhandlerapi extends SpotPage_Abs {

	private $_nzbHandler;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		
		parent::__construct($db, $settings, $currentSession);
	} # ctor	
	
	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_use_sabapi, '');
		
		parse_str($_SERVER['QUERY_STRING'], $request);

		$apikey = $this->_currentSession['user']['apikey'];
		if ($this->_tplHelper->apiToHash($apikey) != $request['nzbhandlerapikey']) {
			error_log('API Key Incorrect');
			die ('API Key Incorrect');
		}
		
		$nzbHandlerFactory = new NzbHandler_Factory();
		$this->_nzbHandler = $nzbHandlerFactory->build($this->_settings, 
					$this->_currentSession['user']['prefs']['nzbhandling']['action'], 
					$this->_currentSession['user']['prefs']['nzbhandling']);
		
		if ($this->_nzbHandler->hasApiSupport() !== false)
		{
			$action = strtolower($request['action']);
			
			switch($action)
			{
				# actions on the entire queue
				case 'getstatus':
					$result = $this->_nzbHandler->getStatus();
					break;
				case 'pausequeue':
					$result = $this->_nzbHandler->pauseQueue();
					break;
				case 'resumequeue':
					$result = $this->_nzbHandler->resumeQueue();
					break;
				case 'setspeedlimit':
					$result = $this->_nzbHandler->setSpeedLimit($request['limit']);
					break;
				# actions on a specific download
				case 'movedown':
					$result = $this->_nzbHandler->moveDown($request['id']);
					break;
				case 'moveup':
					$result = $this->_nzbHandler->moveUp($request['id']);
					break;
				case 'movetop':
					$result = $this->_nzbHandler->moveTop($request['id']);
					break;
				case 'movebottom':
					$result = $this->_nzbHandler->moveBottom($request['id']);
					break;
				case 'setcategory':
					$result = $this->_nzbHandler->setCategory($request['id'], $request['category']);
					break;
				case 'setpriority':
					$result = $this->_nzbHandler->setPriority($request['id'], $request['priority']);
					break;
				case 'setpassword':
					$result = $this->_nzbHandler->setPassword($request['id'], $request['password']);
					break;
				case 'delete':
					$result = $this->_nzbHandler->delete($request['id']);
					break;
				case 'rename':
					$result = $this->_nzbHandler->rename($request['id'], $request['name']);
					break;
				case 'pause':
					$result = $this->_nzbHandler->pause($request['id']);
					break;
				case 'resume':
					$result = $this->_nzbHandler->resume($request['id']);
					break;
				# non download related actions
				case 'getcategories':
					$result = $this->_nzbHandler->getCategories();
					break;
				case 'getversion':
					$tmp = $this->_nzbHandler->getVersion();
					if ($tmp === false)
					{
						$result = false;
					}
					else
					{
						$result['version'] = $tmp;
					}
					break;
				default:
					# default action
					$result = false;
			}
		}
		else
		{
			error_log('The configured NZB handler has no api support');
			die('The configured NZB handler has no api support');
		}
		
		# de nzbhandlerapi output moet niet gecached worden
		$this->sendExpireHeaders(true);
		header('Content-type: application/json');

		if (($result === true) || ($result === false))
		{
			$tmp['result'] = $result;
			$result = $tmp;
		}
		$result = json_encode($result);
		
		error_log("Result: " . $result);
		
		echo $result;
	} # render

} # class SpotPage_nzbhandlerapi