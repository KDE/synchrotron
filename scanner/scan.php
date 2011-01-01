#!/usr/bin/env php
<?php

include_once('../include/config.php');

$configFile = "$common_repoPath/config";
$configFd = fopen($configFile, 'c');

function lock()
{
    global $configFd;
    if (!flock($configFd, LOCK_EX | LOCK_NB)) {
        print("locking failed\n");
        exit();
    }
}

function unlock()
{
    global $configFd;
    flock($configFd, LOCK_UN);
    fclose($configFd);
}

lock();



unlock();

?>
