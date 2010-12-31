<?php

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

?>
