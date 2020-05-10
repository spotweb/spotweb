<?php

class SpotPage_nzbhandlerapi extends SpotPage_Abs
{
    private $_nzbHandler;
    private $_params;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_params = $params;
    }

    // ctor

    public function render()
    {
        // Make sure the user has the appropriate permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_use_sabapi, '');

        $apikey = $this->_currentSession['user']['apikey'];
        if ($this->_tplHelper->apiToHash($apikey) != $this->_params['nzbhandlerapikey']) {
            error_log('API Key Incorrect');
            echo 'API Key Incorrect';

            return;
        } // if

        $nzbHandlerFactory = new Services_NzbHandler_Factory();
        $this->_nzbHandler = $nzbHandlerFactory->build(
            $this->_settings,
            $this->_currentSession['user']['prefs']['nzbhandling']['action'],
            $this->_currentSession['user']['prefs']['nzbhandling']
        );

        if ($this->_nzbHandler->hasApiSupport() !== false) {
            $action = strtolower($this->_params['action']);

            switch ($action) {
                // actions on the entire queue
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
                    $result = $this->_nzbHandler->setSpeedLimit($this->_params['limit']);
                    break;
                // actions on a specific download
                case 'movedown':
                    $result = $this->_nzbHandler->moveDown($this->_params['id']);
                    break;
                case 'moveup':
                    $result = $this->_nzbHandler->moveUp($this->_params['id']);
                    break;
                case 'movetop':
                    $result = $this->_nzbHandler->moveTop($this->_params['id']);
                    break;
                case 'movebottom':
                    $result = $this->_nzbHandler->moveBottom($this->_params['id']);
                    break;
                case 'setcategory':
                    $result = $this->_nzbHandler->setCategory($this->_params['id'], $this->_params['category']);
                    break;
                case 'setpriority':
                    $result = $this->_nzbHandler->setPriority($this->_params['id'], $this->_params['priority']);
                    break;
                case 'setpassword':
                    $result = $this->_nzbHandler->setPassword($this->_params['id'], $this->_params['password']);
                    break;
                case 'delete':
                    $result = $this->_nzbHandler->delete($this->_params['id']);
                    break;
                case 'rename':
                    $result = $this->_nzbHandler->rename($this->_params['id'], $this->_params['name']);
                    break;
                case 'pause':
                    $result = $this->_nzbHandler->pause($this->_params['id']);
                    break;
                case 'resume':
                    $result = $this->_nzbHandler->resume($this->_params['id']);
                    break;
                // non download related actions
                case 'getcategories':
                    $result = $this->_nzbHandler->getBuiltinCategories();
                    break;
                case 'getversion':
                    $tmp = $this->_nzbHandler->getVersion();
                    if ($tmp === false) {
                        $result = false;
                    } else {
                        $result['version'] = $tmp;
                    }
                    break;
                default:
                    // default action
                    $result = false;
            }
        } else {
            error_log('The configured NZB handler has no api support');
            echo 'The configured NZB handler has no api support';

            return;
        }

        // do not cache the nzbhandlerapi's output
        $this->sendExpireHeaders(true);
        $this->sendContentTypeHeader('json');

        if (($result === true) || ($result === false)) {
            $tmp['result'] = $result;
            $result = $tmp;
        }
        $result = json_encode($result);

        echo $result;
    }

    // render
} // class SpotPage_nzbhandlerapi
