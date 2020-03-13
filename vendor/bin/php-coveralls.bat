@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../php-coveralls/php-coveralls/bin/php-coveralls
php "%BIN_TARGET%" %*
