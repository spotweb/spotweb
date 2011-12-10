<?php

    class Growl
    {
        const GROWL_PRIORITY_LOW        = -2;
        const GROWL_PRIORITY_MODERATE   = -1;
        const GROWL_PRIORITY_NORMAL     = 0;
        const GROWL_PRIORITY_HIGH       = 1;
        const GROWL_PRIORITY_EMERGENCY  = 2;

        private $appName;
        private $address;
        private $notifications;
        private $password;
        private $port;

        public function __construct($app_name = 'PHP Growl')
        {
            $this->appName       = utf8_encode($app_name);
            $this->address       = null;
            $this->notifications = array();
            $this->password      = null;
            $this->port          = 9887;
        }

        public function addNotification($name, $enabled = true)
        {
            $this->notifications[] = array('name' => utf8_encode($name), 'enabled' => $enabled);
        }

        public function register($connection)
        {
            $this->setConnectionData($connection);

            $data         = '';
            $defaults     = '';
            $num_defaults = 0;

            for($i = 0; $i < count($this->notifications); $i++)
            {
                $data .= pack('n', strlen($this->notifications[$i]['name'])) . $this->notifications[$i]['name'];
                if($this->notifications[$i]['enabled'])
                {
                    $defaults .= pack('c', $i);
                    $num_defaults++;
                }
            }

            // pack(Protocol version, type, app name, number of notifications to register)
            $data  = pack('c2nc2', 1, 0, strlen($this->appName), count($this->notifications), $num_defaults) . $this->appName . $data . $defaults;
            $data .= pack('H32', md5($data . $this->password));

            return $this->send($data);
        }

        public function notify($connection, $name, $title, $message, $priority = 0, $sticky = false)
        {
            $this->setConnectionData($connection);

            $name     = utf8_encode($name);
            $title    = utf8_encode($title);
            $message  = utf8_encode($message);
            $priority = intval($priority);

            $flags = ($priority & 7) * 2;
            if($priority < 0) $flags |= 8;
            if($sticky) $flags |= 256;

            // pack(protocol version, type, priority/sticky flags, notification name length, title length, message length. app name length)
            $data = pack('c2n5', 1, 1, $flags, strlen($name), strlen($title), strlen($message), strlen($this->appName));
            $data .= $name . $title . $message . $this->appName;
            $data .= pack('H32', md5($data . $this->password));

            return $this->send($data);
        }

        private function send($data)
        {
            if((!defined('GROWL_SOCK') && function_exists('socket_create') && function_exists('socket_sendto')) || (GROWL_SOCK === 'socket'))
            {
                $sck = ( strlen(inet_pton($this->address)) > 4 && defined('AF_INET6') )
                    ? socket_create(AF_INET6, SOCK_DGRAM, SOL_UDP)
                    : socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                socket_sendto($sck, $data, strlen($data), 0x100, $this->address, $this->port);
                $this->resetConnectionData();
                return true;
            }
            elseif((!defined('GROWL_SOCK') && function_exists('fsockopen')) || (GROWL_SOCK === 'fsock'))
            {
                $fp = fsockopen('udp://' . $this->address, $this->port);
                fwrite($fp, $data);
                fclose($fp);
                $this->resetConnectionData();
                return true;
            }

            return false;
        }

        private function setConnectionData($connection)
        {
            if(empty($connection['address']))
            {
                throw new Exception('Address Missing', 'Unable to send notification without ip address.');
            }

            $this->address  = $connection['address'];
            $this->password = (!empty($connection['password'])) ? $connection['password'] : '';
        }

        private function resetConnectionData()
        {
            $this->address  = null;
            $this->password = null;
        }
    }
