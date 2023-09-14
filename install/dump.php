#! /usr/bin/php
<?php

$backupFile = __DIR__ . "/dump.sql";
if (!file_exists($backupFile)) {
   echo "backup not exists ";
   exit;
}

if (!file_exists(__DIR__ . "/../apps/config/config.db.php")) {
   echo "folder not exists ";
   exit;
}
include __DIR__ . "/../apps/config/config.db.php";

$username = $CONFIG['DBUserName'];
$password = $CONFIG['DBPassword'];
$hostname = $CONFIG['HostName'];
$database = $CONFIG['DBName'];

$command = "mysql -u'{$username}' -p'{$password}' -h{$hostname} --default-character-set=utf8 $database < $backupFile";
system($command, $result);
echo $result . "\n";
rename($backupFile, dirname($backupFile) . '/dump.sql_');
rename(__DIR__, __DIR__ . "/..");
