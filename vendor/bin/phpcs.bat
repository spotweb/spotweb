@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../squizlabs/php_codesniffer/bin/phpcs
php "%BIN_TARGET%" %*
