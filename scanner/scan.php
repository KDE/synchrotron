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
        setupProviderOCS($provider);
    }

    return $providers;
}

function setupProviderOCS($provider)
{
    global $common_basePath, $common_htmlPath;
    $path = "$common_htmlPath/$provider";
    if (!is_dir($path)) {
        // in case it already existed as a file?
        unlink($path);
        mkdir($path);
    }

    mkdir("$path/files");
    $staticFiles = Array('licenses', 'distributions', 'dependencies', 'homepages');
    foreach ($staticFiles as $file) {
        copy("$common_basePath/scanner/templates/$file", "$path/$file");
    }

    $providerXml = "<providers>
        <provider>
         <id>opendesktop</id>
          <location>$common_baseURL</location>
           <name>KDE Synchrotron</name>
           <icon></icon>
           <services>
           <content ocsversion=\"1.3\" />
           </services>
           </provider>
        </providers>";
    $providerFile = fopen("$path/provider.xml", 'w');
    fwrite($providerFile, $providerXml);
}

// finds all entries that have changed in the git repository
function findChangedAssets($config, $providers)
{
    global $common_repoPath;
    $assets = Array();
    if (!chdir($common_repoPath)) {
        print("Could not change directory to the repository!\n");
        return $assets;
    }

    exec('git pull');
    $db = db_connect();
    $ts = db_query($db, "SELECT EXTRACT(EPOCH FROM DATE_TRUNC('second', lastscan)) FROM scanning;");

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

        $ts = db_query($db, "INSERT INTO scanning (lastScan) VALUES (CURRENT_TIMESTAMP);");
    } else {
        // get changes in the git repo since our last scan
        list($ts) = db_row($ts, 0);
        $log = Array();
        exec("git log --since=$ts --pretty=format:\"\" --name-only", $log);
        db_query($db, "UPDATE scanning set lastScan = CURRENT_TIMESTAMP;");

        foreach ($config as $provider => $providerConfig) {
            //print("Listing all assets for $provider\n");
            $assets[$provider] = Array();
        }

        foreach ($log as $entry) {
            if (empty($entry)) {
                continue;
            }

            $pathParts = explode('/', $entry);
            if (count($pathParts) < 2) {
                // top level file, such as "providers", just skip
                continue;
            }

            $provider = $pathParts[0];
            if (!isset($providers[$provider])) {
                continue;
            }

            $asset = $pathParts[1];
            $path = "$common_repoPath/$provider/$asset";
            $path = "$common_repoPath/$provider/$pathParts[1]";
            $assets[$provider][$asset] = $path;
        }
    }

    return $assets;
}

function processAssets($assets, $providers, $config)
{
    global $common_htmlPath;
    foreach ($assets as $provider => $providerAssets) {
        if (!isset($providers[$provider])) {
            print("Assets for $provider can not be processed due to missing provider id\n");
            continue;
        }

        processProviderAssets($providerAssets, "$common_htmlPath/$provider/files", $providers[$provider], $config[$provider]);
    }
}

function deleteAsset($providerId, $asset)
{
    sql_addToWhereClause($where, '', 'provider', '=', $providerId);
    sql_addToWhereClause($where, 'and', 'id', '=', $asset);
    db_delete(db_connect(), 'content', $where);
}

function processProviderAssets($assets, $packageBasePath, $providerId, $config)
{
    $metadataPath = $config['metadata'];
    if (empty($metadataPath)) {
        $metadataPath = 'metadata.desktop';
    }

    $db = db_connect();

    foreach ($assets as $asset => $path) {
        //print("Processing $providerId $asset at $path\n");
        if (!is_file("$path/$metadataPath")) {
            //print("No such thing as $path/$metadataPath, perhaps it was deleted?\n");
            deleteAsset($providerId, $asset);
            continue;
        }

        $metadata = new INIFile("$path/$metadataPath");
        $plugin = $metadata->getValue('X-KDE-PluginInfo-Name', 'Desktop Entry');

        if (empty($plugin)) {
            print("No X-KDE-PluginInfo-Name entry in $contentPath/$metadataPath\n");
            continue;
        }

        $packagePath = createPackage($asset, $path, $packageBasePath, $config);
        if (!$packagePath) {
            deleteAsset($providerId, $asset);
            continue;
        }

        unset($where);
        sql_addToWhereClause($where, '', 'provider', '=', $providerId);
        sql_addToWhereClause($where, 'and', 'id', '=', $asset);
        $query = db_query($db, "select * from content where $where;");
        if (db_numRows($query) > 0) {
            // just update the field
            unset($fields);
            sql_addScalarToUpdate($fields, 'version', $metadata->getValue('X-KDE-PluginInfo-Version', 'Desktop Entry'));
            sql_addScalarToUpdate($fields, 'author', $metadata->getValue('X-KDE-PluginInfo-Author', 'Desktop Entry'));
            sql_addScalarToUpdate($fields, 'homepage', $metadata->getValue('X-KDE-PluginInfo-Website', 'Desktop Entry'));
            //FIXME: get preview image from asset dir! sql_addScalarToUpdate($fields, 'preview', <image path>);
            sql_addScalarToUpdate($fields, 'name', $metadata->getValue('Name', 'Desktop Entry')); // FIXME: i18n
            sql_addScalarToUpdate($fields, 'description', $metadata->getValue('Comment', 'Desktop Entry'));
            db_update($db, 'content', $fields, $where);
        } else {
            // new asset!
            unset($fields, $values);
            sql_addIntToInsert($fields, $values, 'provider', $providerId);
            sql_addScalarToInsert($fields, $values, 'id', $asset);
            sql_addScalarToInsert($fields, $values, 'version', $metadata->getValue('X-KDE-PluginInfo-Version', 'Desktop Entry'));
            sql_addScalarToInsert($fields, $values, 'author', $metadata->getValue('X-KDE-PluginInfo-Author', 'Desktop Entry'));
            sql_addScalarToInsert($fields, $values, 'homepage', $metadata->getValue('X-KDE-PluginInfo-Website', 'Desktop Entry'));
            //FIXME: get preview image from asset dir! sql_addScalarToInsert($fields, $values, 'preview', <image path>);
            sql_addScalarToInsert($fields, $values, 'name', $metadata->getValue('Name', 'Desktop Entry')); // FIXME: i18n
            sql_addScalarToInsert($fields, $values, 'description', $metadata->getValue('Comment', 'Desktop Entry'));
            db_insert($db, 'content', $fields, $values);
        }
    }
}

function createPackage($asset, $source, $dest, $config)
{
    $compression = $config['compression'];
    $contentPath = "$source/content";
    $dir = opendir($contentPath);
    if (!$dir) {
        print("Could not open content directory in $source\n");
        goto failure;
    }


    // the simple no-compression case: just copy over the first file in content/
    if (empty($compression) || $compression == 'none') {
        while (false != ($entry = readdir($dir))) {
            if ($entry[0] == '.') {
                continue;
            } else {
                break;
            }
        }

        if (!$entry) {
            print("No entry $contentPath while doing the no-compression dance!\n");
            goto failure;
        }

        // the first non-hidden file ... copy it!
        $path= "$dest/${asset}_$entry";

        print("copy $contentPath/$entry $path");
        copy("$contentPath/$entry", $path);
        closedir($dir);
        return $path;
    }

    $suffix = $config['packageSuffix'];
    if (empty($suffix)) {
        if ($compression == 'zip') {
            $suffix = '.zip';
        } else if ($compression == 'tgz') {
            $suffix = '.tgz';
        } else if ($compression == 'tbz') {
            $suffix = '.tbz';
        }
    }

    $packagePath = "$dest/$asset$suffix";
    unlink($packagePath);

    if ($compression == 'zip') {
        $zip = new ZipArchive();
        if (!$zip->open($packagePath, ZipArchive::CREATE)) {
            print("Could not open zip file at $packagePath");
            goto failure;
        }

        while (false != ($entry = readdir($dir))) {
            if ($entry[0] == '.') {
                continue;
            }

            if (!addToZip($zip, $contentPath, $entry)) {
                $zip->close();
                goto failure;
            }
        }

        if (!$zip->close()) {
            goto failure;
        }
    } else if ($compression == 'tgz') {
        //FIXME: implement tar+gzip compression
        goto failure;
    } else if ($compression == 'tbz') {
        //FIXME: implement tar+bzip compression
        goto failure;
    } else {
        // unrecognized compression format
        print("Compression format requested ($compression) for $asset is unknown");
        goto failure;
    }

    return $packagePath;

failure:
    closedir($dir);
    return false;
}

function addToZip($zip, $basePath, $file, $subPath = '')
{
    $path = empty($subPath) ? $file : "$subPath/$file";
    $srcPath = "$basePath/$path";
    //print("adding $file from $srcPath to $path\n");
    if (is_file($srcPath)) {
        if (!$zip->addFile($srcPath, $path)) {
            print("Failed to add $path to $packagePath");
            return false;
        }
    } else if (is_dir($srcPath)) {
        $dir = opendir($srcPath);
        if (!$dir) {
            print("Could not open content directory in $entryPath\n");
            return false;
        }

        while (false != ($entry = readdir($dir))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            if (!addToZip($zip, $basePath, $entry, $path)) {
                return false;
            }
        }
    }

    return true;
}

lock();

$config = parse_ini_file($configFile, true);
$providers = providers($config);
$changedAssets = findChangedAssets($config, $providers);
processAssets($changedAssets, $providers, $config);

unlock();

?>
