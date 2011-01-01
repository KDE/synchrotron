#!/usr/bin/env php
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

include_once('../include/config.php');
include_once("$common_includePath/db.php");
include_once("$common_includePath/iniparser.php");

$configFile = "$common_repoPath/providers";
$configFd = fopen($configFile, 'c');

// tries to get a file lock on the providers config
// on failure, we exit on the assumption another scan is running
function lock()
{
    global $configFd;
    if (!flock($configFd, LOCK_EX | LOCK_NB)) {
        print("locking failed\n");
        exit();
    }
}

// free the lock on the providers config
function unlock()
{
    global $configFd;
    flock($configFd, LOCK_UN);
    fclose($configFd);
}


// goes through the providers config and ensures every provider has an entry in the db
// returns an array of provider names to ID from the providers table in the databse
function providers($config)
{
    $db = db_connect();
    $providers = Array();

    foreach ($config as $provider => $providerConfig) {
        //print("processing provider $provider\n");
        unset($where);
        sql_addToWhereClause($where, '', 'name', '=', $provider);
        $query = "select id from providers where $where";
        $result = db_query($db, $query);
        if (db_numRows($result) < 1) {
            unset($fields, $values);
            sql_addScalarToInsert($fields, $values, 'name', $provider);
            if (isset($providerConfig['typename'])) {
                sql_addScalarToInsert($fields, $values, 'name', $providerConfig['typename']);
            }
            db_insert($db, 'providers', $fields, $values);
            $result = db_query($db, $query);
        }

        list($providers[$provider]) = db_row($result, 0);
    }

    return $providers;
}

function findChangedAssets($config)
{
    global $common_repoPath;
    $assets = Array();
    if (!chdir($common_repoPath)) {
        print("Could not change directory to the repository!\n");
        return $assets;
    }

    exec('git pull');
    $db = db_connect();
    $ts = db_query($db, "SELECT lastScan FROM scanning;");

    // no scan! do it from scratch then...
    if (db_numRows($ts) < 1) {
        //print("Fresh scan!\n");
        foreach ($config as $provider => $providerConfig) {
            //print("Listing all assets for $provider\n");
            $providerAssets = Array();
            $path = "$common_repoPath/$provider";
            $dir = opendir($path);
            if (!$dir) {
                print("Could not open directory for '$provider': $path\n");
                continue;
            }

            while (false != ($entry = readdir($dir))) {
                if ($entry[0] == '.') {
                    //print("'$path/$entry' is a hidden dir.\n");
                    continue;
                }

                $contentPath = "$path/$entry";
                if (is_dir($contentPath)) {
                    $providerAssets[$entry] = $contentPath;
                }
            }

            closedir($dir);
            $assets[$provider] = $providerAssets;
        }

        //$ts = db_query($db, "INSERT INTO scanning (lastScan) VALUES (CURRENT_TIMESTAMP);");
        return $assets;
    }

}

function processAssets($assets, $providers, $config)
{
    foreach ($assets as $provider => $providerAssets) {
        if (!isset($providers[$provider])) {
            print("Assets for $provider can not be processed due to missing provider id\n");
            continue;
        }

        processProviderAssets($providerId, $providerAssets, $config[$provider]);
    }
}

function processProviderAssets($providerId, $assets, $config)
{
    $metadataPath = $config['metadata'];
    if (empty($metadataPath)) {
        $metadataPath = 'metadata.desktop';
    }

    foreach ($assets as $asset => $path) {
        print("Processing $providerId $asset at $path\n");
        if (!is_file("$path/$metadataPath")) {
            print("Fail ... no such thing as $path/$metadataPath\n");
            continue;
        }

        $metadata = new INIFile("$path/$metadataPath");
        $plugin = $metadata->getValue('X-KDE-PluginInfo-Name', 'Desktop Entry');

        if (empty($asset)) {
            print("No X-KDE-PluginInfo-Name entry in $contentPath/$metadataPath\n");
            continue;
        }

        print("Got $plugin\n");
    }
}

lock();

$config = parse_ini_file($configFile, true);
$providers = providers($config);
print("booyah\n");
$changedAssets = findChangedAssets($config, $providers);
processAssets($changedAssets, $providers, $config);

unlock();

?>
