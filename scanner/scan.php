#!/usr/bin/env php
<?php

$lockFile = '/tmp/synchrotron_scan.lock';
$resource = fopen($lockFile, 'c');

function lock()
{
    global $lockFile, $resource;
    if (!flock($resource, LOCK_EX | LOCK_NB)) {
        print("locking failed\n");
        exit();
    }
}

function unlock()
{
    global $lockFile, $resource;
    $resource = fopen($lockFile, 'c');
    flock($resource, LOCK_UN);
}

lock();

unlock();

?>
