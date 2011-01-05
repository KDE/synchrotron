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

// Spec: http://freedesktop.org/wiki/Specifications/open-collaboration-services#list-1
//
// Supported parameters from the spec:
//     search: the part of the name of the item you want to find.
//     sortmode: The sortmode of the list. Possible values are: "new": newest first , "alpha": alphabetical, "high": highest rated, "down": most downloads
//     page: The content page. You can control the size of a page with the pagesize argument. The first page is 0, the second is 1, ...
//     pagesize: The amount of entries per page. 
//
// Unsupported parameters from the spec:
//     categories
//     user
//     license
//     external
//     distribution
//
// Non-standard parameters:
//     provider: the name of the provider to use in the database lookups

include_once('../include/config.php');
include_once("$common_includePath/db.php");

function printHeader($itemCount, $pagesize, $page = 0, $message = '')
{
    print
"<?xml version=\"1.0\"?>

<ocs>
<meta>
    <status>ok</status>
    <statuscode>100</statuscode>
    <message>$message</message>
    <totalitems>$itemCount</totalitems>
    <page>$page</page>
    <itemsperpage>$pagesize</itemsperpage>
</meta>
<data>
";
}

function printItem($id, $name, $version, $updated, $created, $type, $author, $homepage, $downloads, $preview)
{
    // fields not included:
    //  language
    //  score
    //  preview1 (homepage for the preview)
    //  profilepage (author homepage)
    //  downloadname1
    //  downloadsize1
    //  downloadgpgsignature1
    //  downloadgpgfingerprint1
    print "    <content details=\"summary\">
        <id>$id</id>
        <name>$name</name>
        <version>$version</version>
        <changed>$updated</changed>
        <created>$created</created>
        <typeid>$type</typeid>
        <typename></typename>
        <personid>$author</personid>
        <detailpage>$homepage</detailpage>
        <downloads>$downloads</downloads>
";

    if (!empty($preview)) {
        print "\n<previewpic1>$preview</previewpic1>\n";
    }

    print "     </content>";
}

function printFooter()
{
    print '
</data>
</ocs>';
}

$pagesize = intval($_GET['pagesize']);
$page = max(0, intval($_GET['page']));
$searchTerm = $_GET['search'];
$sortMode = $_GET['sortmode'];

$provider = $_GET['provider'];
if (empty($provider)) {
    printHeader(0, $pagesize, 1, _("Invalid provider"));
    printFooter();
    exit();
}

$db = db_connect();

unset($where);
sql_addToWhereClause($where, '', 'p.name', '=', $provider);

list($totalItemCount) = db_row(db_query($db, "SELECT count(c.id) FROM content c LEFT JOIN providers p ON (c.provider = p.id) WHERE $where;"), 0);
if ($totalItemCount < 1) {
    printHeader(0, $pagesize);
    printFooter();
    exit();
}

unset($limit);
if ($pagesize > 0) {
    $limit = "LIMIT $pagesize";
}

unset($offset);
if ($page > 0) {
    $offset = 'OFFSET ' . $page * $pagesize;
}

unset($orderBy);
if (empty($sortMode) || $sortMode == 'new') {
    $orderBy = 'ORDER BY c.updated DESC';
} else if ($sortMode == 'alpha') {
    $orderBy = 'ORDER BY c.name'; // FIXME: i18n
} else if ($sortMode == 'down') {
    $orderBy = 'ORDER BY c.downloads DESC';
} /* else if ($sortMode == 'high') {
    ratings are not supported
} */

$items = db_query($db, "SELECT c.id, c.name, c.version, date_trunc('second', c.updated), date_trunc('second', c.created), c.author, c.homepage, c.downloads, c.preview FROM content c LEFT JOIN providers p ON (c.provider = p.id) WHERE $where $orderBy $limit $offset;");

printHeader($totalItemCount, $pagesize, $page);
$itemCount = db_numRows($items);
for ($i = 0; $i < $itemCount; ++$i) {
    list($id, $name, $version, $updated, $created, $author, $homepage, $downloads, $preview) = db_row($items, $i);
    printItem($id, $name, $version, $updated, $created, '' /* type */, $author, $homepage, $downloads, $preview);
}
printFooter();
?>
