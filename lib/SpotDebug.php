<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class SpotDebug
{
    const DISABLED = 999999;
    const TRACE = 10;
    const DEBUG = 20;
    const INFO = 30;
    const WARN = 40;
    const ERROR = 50;
    const FATAL = 60;

    private static $_level = self::DISABLED;
    private static $_logDir = __DIR__.'/../logs';
    private static $_debugLogDao = null;

    protected static function spotlevelToMonolevel($lvl)
    {
        switch ($lvl) {
            case self::DISABLED:
            case self::TRACE:
                return Logger::DEBUG;
            case self::DEBUG:
                return Logger::INFO;
            case self::INFO:
                return Logger::NOTICE;
            case self::WARN:
                return Logger::WARNING;
            case self::ERROR:
                return Logger::ERROR;
            case self::FATAL:
                return Logger::CRITICAL;
            default:
                return Logger::NOTICE;
        }
    }

    public static function enable($lvl)
    {
        self::$_level = $lvl;
        if (!is_dir(self::$_logDir)) {
            mkdir(self::$_logDir, 0755);
        }
        self::$_debugLogDao = new Logger('SpotWeb');
        $handler = new RotatingFileHandler(self::$_logDir.'/spotweb.log', 7, self::spotlevelToMonolevel($lvl));
        $handler->getFormatter()->ignoreEmptyContextAndExtra(true);
        self::$_debugLogDao->pushHandler($handler);
    }

    // enable()

    public static function disable()
    {
        self::$_level = self::DISABLED;
    }

    // disable()

    public static function msg($lvl, $msg, $context = [])
    {
        if (!is_null(self::$_debugLogDao)) {
            self::$_debugLogDao->addRecord(self::spotlevelToMonolevel($lvl), $msg, $context);
        }
    }

    // msg
} // class SpotDebug
