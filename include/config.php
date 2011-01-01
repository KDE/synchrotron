<?php

// MAIN CONFIGURATION
global $common_siteHost, $common_sitePath;
if (isset($_SERVER['SERVER_NAME'])) {
    $common_siteHost = 'http://' . $_SERVER['SERVER_NAME'];
    $common_sitePath = '/';
}

// // BASE PATHS AND URLS
global $common_baseURL, $common_htmlPath, $common_includePath;
$common_baseURL = $common_siteHost . $common_sitePath;
$basePath = '/home/aseigo/src/synchrotron';
$common_repoPath = '/home/aseigo/src/testrepo';
$common_htmlPath = $basePath . '/ocs';
$common_includePath = $basePath . '/include';

// DATABASE CONNECTION
// global $db_hame, $db_host, $db_port, $db_username;
// global $db_password, $db_localOnly, $db_singleDatabaseOnly;
$db_name = 'synchrotron';
$db_host = 'localhost';
$db_port = '5432';
$db_username = 'synchrotron';
$db_password = '';
$db_localOnly = true;
$db_singleDatabaseOnly = true;

// // DB DEBUGGING
global $db_errorMsg, $db_debug, $db_profile;
$db_errorMsg = 1;
$db_debug = 0;
$db_profile = 0;

?>
