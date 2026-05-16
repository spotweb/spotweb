#!/usr/bin/php
<?php

error_reporting(2147483647);

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/SpotThemes.php';

if (php_sapi_name() !== 'cli') {
    exit('validate-theme.php can only be run from the console'.PHP_EOL);
} // if

$themeName = isset($argv[1]) ? trim($argv[1]) : '';
if ($themeName === '') {
    echo 'Usage: php bin/validate-theme.php <theme-name>'.PHP_EOL.PHP_EOL;
    echo 'Discovered themes:'.PHP_EOL;
    foreach (SpotThemes::discoverThemes() as $discoveredThemeName => $label) {
        echo ' - '.$discoveredThemeName.PHP_EOL;
    } // foreach
    exit(1);
} // if

$validation = SpotThemes::validateTheme($themeName);
if ($validation['is_valid']) {
    echo 'Theme "'.$themeName.'" is valid.'.PHP_EOL;
    exit(0);
} // if

echo 'Theme "'.$themeName.'" is NOT valid.'.PHP_EOL;
echo 'Missing files:'.PHP_EOL;
foreach ($validation['missing_files'] as $missingFile) {
    echo ' - '.$missingFile.PHP_EOL;
} // foreach

exit(1);
