<?php

$dbsettings['engine'] = 'pdo_mysql';
$dbsettings['host'] = getenv('DB_SERVER');
$dbsettings['dbname'] = getenv('DB');
$dbsettings['user'] = getenv('DB_USER');
$dbsettings['pass'] = getenv('DB_PASSWD');
