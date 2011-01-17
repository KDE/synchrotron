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

// Spec: http://freedesktop.org/wiki/Specifications/open-collaboration-services#download
//
// Supported parameters from the spec:
//     contentid: Id of a content 
//     itemid: Id of a content 
//
// Non-standard parameters:
//     provider: the name of the provider to use in the database lookups

include_once('../include/config.php');
include_once("$common_includePath/db.php");

function printHeader($status = 100, $message = '')
{
    print
"<?xml version=\"1.0\"?>

<ocs>
<meta>
    <status>ok</status>
    <statuscode>$status</statuscode>
    <message>$message</message>
</meta>
<data>
";
}

function printItem($package, $provider)
{
    global $common_baseURL;
    print "    <content details=\"download\">
        <downloadlink>$common_baseURL/$provider/files/$package</downloadlink>
    </content>";
}

function printFooter()
{
    print '
</data>
</ocs>';
}

if (!canAccessApi($_SERVER['REMOTE_ADDR'])) {
    printHeader(200, _("Too many requests from ${_SERVER['REMOTE_ADDR']}"));
    printFooter();
    exit();
}

$provider = $_GET['provider'];
if (empty($provider)) {
    printHeader(101, _("Invalid provider"));
    printFooter();
    exit();
}

$contentId = $_GET['contentid'];
if (empty($contentId)) {
    printHeader(101, _("Content ID not provided"));
    printFooter();
    exit();
}

// we don't care about the item id, really, because we only ever have one
// //$itemId = $_GET['itemid'];

$db = db_connection();

unset($where);
sql_addToWhereClause($where, '', 'p.name', '=', $provider);
sql_addToWhereClause($where, 'and', 'c.id', '=', $contentId);

$items = db_query($db, "SELECT c.package, c.externalPackage FROM content c LEFT JOIN providers p ON (c.provider = p.id) WHERE $where;");

if (db_numrows($items) < 1) {
    printHeader(101, _("Content ID '$contentId' not fond"));
    printFooter();
    exit();
}

printHeader(100);
list($package, $external) = db_row($items, 0);
if (db_bool($external)) {
    $url = $package;
else ($url) {
    $url = "common_baseURL/$provider/files/$package";
}
print "    <content details=\"download\"><downloadlink>$url</downloadlink></content>\n";

printFooter();

// record the request as a download
unset($where);
sql_addToWhereClause($where, '', 'id', '=', $contentId);
db_query($db, "UPDATE content SET downloads = downloads + 1 WHERE $where;");
?>
