<?php
/*
 *   Copyright 2011 Aaron Seigo <aseigo@kde.org>
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Library General Public License as
 *   published by the Free Software Foundation; either version 2, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details
 *
 *   You should have received a copy of the GNU Library General Public
 *   License along with this program; if not, write to the
 *   Free Software Foundation, Inc.,
 *   51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */


// MAIN CONFIGURATION
global $common_siteHost, $common_sitePath;
if (isset($_SERVER['SERVER_NAME'])) {
    $common_siteHost = 'http://' . $_SERVER['SERVER_NAME'];
    $common_sitePath = '/';
}

// BASE PATHS AND URLS
global $common_baseURL, $common_htmlPath, $common_includePath, $common_basePath;
$common_baseURL = $common_siteHost . $common_sitePath;
$common_basePath = '/home/aseigo/src/synchrotron';
$common_repoPath = '/home/aseigo/src/testrepo';
$common_htmlPath = $common_basePath . '/ocs';
$common_includePath = $common_basePath . '/include';

// DATABASE CONNECTION
// global $db_hame, $db_host, $db_port, $db_username;
// global $db_password, $db_localOnly, $db_singleDatabaseOnly;
$db_name = 'synchrotron';
$db_host = 'localhost';
$db_port = '5432';
$db_username = 'synchrotron_ro';
$db_writeusername = 'synchrotron';
$db_password = '';
$db_localOnly = true;
$db_singleDatabaseOnly = true;

// DB DEBUGGING
global $db_errorMsg, $db_debug, $db_profile;
$db_errorMsg = 1;
$db_debug = 0;
$db_profile = 0;

?>
