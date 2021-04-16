<?php

error_reporting(2147483647);

require_once __DIR__.'/vendor/autoload.php';

if (file_exists(__DIR__.'/settings.php')) {
    require_once __DIR__.'/settings.php';
}
if (file_exists(__DIR__.'/dbsettings.inc.php')) {
    require_once __DIR__.'/dbsettings.inc.php';
}

set_error_handler('SpotInstall::ownWarning', E_WARNING);

if (file_exists(__DIR__.'/reallymyownsettings.php')) {
    require_once __DIR__.'/reallymyownsettings.php';
}

/**
 * We output headers after already sending HTML, make
 * sure output buffering is turned on.
 */
ob_start();

/**
 * We default to a succeeded install, let it prove
 * otherwise.
 */
global $_testInstall_Ok;

$_testInstall_Ok = true;

session_start();

/**
 * Dummy translate function.
 */
if (!function_exists('_')) {
    function _($s)
    {
        return $s;
    }
}

/**
 * Only run the wizard when no database settings have been entered yet, to prevent
 * any information disclosure.
 */
if ((isset($dbsettings)) && (isset($_GET['page']))) {
    SpotInstall::showTemplate(
        'fatalerror.inc.php',
        ['x' => new Exception("Spotweb has already been setup. If you want to run this wizard again, please remove the file 'dbsettings.inc.php'")]
    );
    exit();
}

/**
 * determine what page of the wizzard we are on, and display that one.
 */
$pageNumber = (isset($_GET['page']) ? $_GET['page'] : 1);

switch ($pageNumber) {
    case 2:
        SpotInstall::askDbSettings();
        break;
    case 3:
        SpotInstall::askNntpSettings();
        break;
    case 4:
        SpotInstall::askSpotwebSettings();
        break;
    case 99:
        SpotInstall::createSystem();
        break;

    default:
        SpotInstall::performAndPrintTests();
        break;
}

ob_end_flush();
