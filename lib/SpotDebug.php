<?php

class SpotDebug {
    const DISABLED           = 999999;
    const TRACE              = 10;
    const DEBUG              = 20;
    const INFO               = 30;
    const WARN               = 40;
    const ERROR              = 50;
    const FATAL              = 60;

    static private $_level = 0;

    static function enable($lvl) {
        self::$_level = $lvl;
    } # enable()

    static function disable() {
        self::$_level = self::DISABLED;
    } # disable()


    static function msg($lvl, $msg) {
        if (self::$_level <= $lvl) {
            echo microtime(true) . ': ' . $msg . PHP_EOL;
        }
    } # msg

} # class SpotDebug
