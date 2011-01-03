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


include_once("$common_includePath/db.php");

function i18n_dbLanguageFields($defaultPrefix)
{
    global $common_language;
    if (isset($common_language) && $common_language > 0) {
        return "CASE WHEN i18ntext.name IS NULL THEN $defaultPrefix.name ELSE i18ntext.name END as name, CASE WHEN i18ntext.description IS NULL THEN ${defaultPrefix}.description ELSE i18ntext.description END as description";
    }

    return "${defaultPrefix}.name as name, ${defaultPrefix}.description as description";
}

function i18n_dbLanguageTables($prefix, $tableAlias, $ref)
{
    global $common_language;
    if (isset($common_language) && $common_language > 0) {
        return " LEFT JOIN ${prefix}Text i18ntext ON ($tableAlias.id = i18ntext.$ref)";
    }
}

function i18n_dbAddWhereClauses(&$where)
{
    global $common_language;
    if (isset($common_language) && $common_language > 0) {
        sql_addNullCheckToWhereClause($langWhere, '', 'i18ntext.language');
        sql_addToWhereClause($langWhere, 'or', 'i18ntext.language', '=', $common_language);
        sql_addToWhereClause($where, 'and', '', '', '(' . $langWhere . ')', false, false);
    }
}

function i18n_dbOrderBy($default)
{
    global $common_language;
    if (isset($common_language) && $common_language > 0) {
        return ' ORDER BY name';
    }

    return ' ORDER BY ' . $default;
}

function i18n_setLanguage($lang)
{
    global $common_language;
    if ($lang == 'C') {
        setcookie('synchrotronLanguage', '', 0, $auth_path);
        unset($GLOBALS['common_language']);
        unset($common_language);
        unset($_COOKIE['synchrotronLanguage']);
        return;
    }

    $db = db_connection();
    sql_addToWhereClause($where, 'WHERE', 'code', '=', $lang);
    $query = db_query($db, "select id from languages $where;");
    if (db_numRows($query) > 0) {
        list($common_language) = db_row($query, 0);
        $common_language = intval($common_language);
    }
}

?>
