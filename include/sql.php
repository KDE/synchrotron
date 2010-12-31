<?php
/*
 * sql_addToWhereClause -> adds the next conditional to the where clause
 *
 * $clauses : the existing string
 * $boolGlue : the boolean condition (where, and, or, etc)
 * $field : the filed from the DB the condition is on
 * $condition :  the conditional operator (>, ~*, #<, =, etc)
 * $value : the value of the condition
 * $quotes : flag for whether single quotes are to be used for the value
 */

function sql_addToWhereClause(&$clauses, $boolGlue, $field, $condition, $value,
                              $quotes = true, $esc = true)
{
    $clauses .= " $boolGlue $field $condition ";

    if ($quotes)
    {
        if ($esc)
        {
            $clauses .= "'" . addslashes($value) . "'";
        }
        else
        {
            $clauses .= "'$value'";
        }
    }
    else if ($esc)
    {
        $clauses .= addslashes($value);
    }
    else
    {
        $clauses .= $value;
    }
}

function sql_addNullCheckToWhereClause(&$clauses, $boolGlue, $field, $null = true)
{
    if ($null) {
        $clauses .= " $boolGlue $field IS NULL";
    } else  {
        $clauses .= " $boolGlue $field IS NOT NULL";
    }
}

/*
 * sql_addAlphaToWhereClause
 * $clauses : the existing string
 */
function sql_addAlphaToWhereClause(&$clauses, $column)
{
    $alpha = httpVar('alpha');

    if ($alpha && $alpha != 'All')
    {
        sql_addToWhereClause($clauses, $clauses ? 'AND' : 'WHERE', $column, '~*', "^[" . addslashes($alpha) . "]");
    }
}

function sql_addSortColToOrderBy(&$orderBy, &$orderColIDs)
{
    $oBy = intval(httpVar('oBy'));
    $oHow = httpVar('oHow');

    if (!is_array($orderColIDs))
    {
        return;
    }

    $orderCol = $orderColIDs[$oBy];
    if ($orderCol)
    {
        $orderCol .= $oHow == 'd' ? ' desc' : ' asc';
    }
    else
    {
        $orderCol = $orderColIDs[0];
    }

    if ($orderCol)
    {
        if ($orderBy)
        {
            $orderBy .= ", $orderCol";
        }
        else
        {
            $orderBy = "ORDER BY $orderCol";
        }
    }
}

/*
 * adding fields and values to inserts
 */
function sql_addRawToInsert(&$fields, &$values, $newField, $newValue)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    if (strlen($newValue) > 0)
    {
        $fields .= "$seperator$newField";
        $values .= "$seperator$newValue";
    }
}

function sql_addScalarToInsert(&$fields, &$values, $newField, $newValue, $trimWS = true)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    if ($trimWS)
    {
        $newValue = trim($newValue);
    }

    if (strlen($newValue) > 0)
    {
        $fields .= "$seperator$newField";
        $values .= "$seperator'" . addslashes($newValue) . "'";
    }
}

function sql_addIntToInsert(&$fields, &$values, $newField, $newValue, $forceZero = false)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $newValue = intval($newValue);

    if ($newValue || $forceZero)
    {
        if (!$newValue)
        {
            $newValue = 0;
        }

        $fields .= "$seperator$newField";
        $values .= "$seperator$newValue";
    }
}

function sql_addFloatToInsert(&$fields, &$values, $newField, $newValue, $maxPrecision = 2)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $newValue = floatval($newValue);
    preg_match("/(^[\\-\\+]?[0-9]*\\.?[0-9]{0,$maxPrecision})/", $newValue, $matches);
    $newValue = $matches[1];

    if (!$newValue || $newValue == '.')
    {
        $newValue = '0.0';
    }

    $fields .= "$seperator$newField";
    $values .= "$seperator$newValue";
}

function sql_addArrayToInsert(&$fields, &$values, $newField, &$newValues, $fieldSeperator = ':', $useKeys = true)
{
    $seperator = '';

    if (!is_array($newValues) || count($newValues) < 1)
    {
        return;
    }

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $compacted = null;
    reset($newValues);
    list($key, $value) = each($newValues);
    if ($useKeys)
    {
        $compacted = $key;
    }
    else
    {
        $compacted = $value;
    }

    while (list($key, $value) = each($newValues))
    {
        if ($useKeys)
        {
            $compacted .= $fieldSeperator . $key;
        }
        else
        {
            $compacted .= $fieldSeperator . $value;
        }
    }

    $compacted .= $fieldSeperator;
    $fields .= "$seperator$newField";
    $values .= "$seperator'" . addslashes($compacted) . "'";
}

function sql_addBoolToInsert(&$fields, &$values, $newField, $bool)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $fields .= "$seperator$newField";

    if ($bool)
    {
        $values .= $seperator . 'true';
    }
    else
    {
        $values .= $seperator . 'false';
    }
}

function sql_addTimestampToInsert(&$fields, &$values, $newField, $newTimestamp)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $newDate = date('r', $newTimestamp);

    if ($newDate)
    {
        $fields .= "$seperator$newField";
        $values .= "$seperator'$newDate'::timestamp";
    }
}

function sql_addDateToInsert(&$fields, &$values, $newField, $month, $day, $year, $timestamp = false)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $conversion = '';
    $month = ereg_replace('[^0-9]', '', $month);
    $day = ereg_replace('[^0-9]', '', $day);
    $year = ereg_replace('[^0-9]', '', $year);

    if ($timestamp)
    {
        $conversion = "to_timestamp('$month-$day-$year', 'MM-DD-YYYY')";
    }
    else
    {
        $conversion = "to_date('$month-$day-$year', 'MM-DD-YYYY')";
    }

    if (checkdate(intval($month), intval($day), intval($year)))
    {
        $fields .= "$seperator$newField";
        //$values .= "$seperator" . "CAST('$month-$day-$year' AS $cast)";
        $values .= "$seperator" . "$conversion";
    }
}

function sql_addStrDateToInsert(&$fields, &$values, $newField, $newDate, $timestamp = false)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $conversion = '';
    $newDate = date('m-d-Y', strtotime($newDate));

    if ($timestamp)
    {
        $conversion = "to_timestamp('$newDate', 'MM-DD-YYYY')";
    }
    else
    {
        $conversion = "to_date('$newDate', 'MM-DD-YYYY')";
    }

    if ($newDate)
    {
        $fields .= "$seperator$newField";
        //$values .= "$seperator" . "CAST('$newDate' AS $cast)";
        $values .= "$seperator" . "$conversion";
    }
}

/*
 * adding fields and values to updates
 */
function sql_addRawToUpdate(&$fields, $newField, $newValue)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    if ($newValue)
    {
        $fields .= "$seperator$newField = $newValue";
    }
}

function sql_addNullToUpdate(&$fields, $newField)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $fields .= "$seperator$newField = NULL";
}

function sql_addScalarToUpdate(&$fields, $newField, $newValue, $trimWS = true)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    if ($trimWS)
    {
        $newValue = trim($newValue);
    }

    $fields .= "$seperator$newField = '" . addslashes($newValue) . "'";
}

function sql_addIntToUpdate(&$fields, $newField, $newValue)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $newValue = intval($newValue);

    if (!$newValue)
    {
        $newValue = 0;
    }

    $fields .= "$seperator$newField = $newValue";
}

function sql_addFloatToUpdate(&$fields, $newField, $newValue, $maxPrecision = 2)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $newValue = floatval($newValue);
    preg_match("/(^[\\-\\+]?[0-9]*\\.?[0-9]{0,$maxPrecision})/", $newValue, $matches);
    $newValue = $matches[1];

    if (!$newValue || $newValue == '.')
    {
        $newValue = '0.0';
    }

    $fields .= "$seperator$newField = $newValue";
}

function sql_addArrayToUpdate(&$fields, $newField, &$newValues, $fieldSeperator = ':', $useKeys = true)
{
    if (!is_array($newValues))
    {
        $newValues = array();
    }

    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $compacted = null;
    reset($newValues);
    list($key, $value) = each($newValues);
    if ($useKeys)
    {
        $compacted = $key;
    }
    else
    {
        $compacted = $value;
    }

    while (list($key, $value) = each($newValues))
    {
        if ($useKeys)
        {
            $compacted .= $fieldSeperator . $key;
        }
        else
        {
            $compacted .= $fieldSeperator . $value;
        }
    }

    $compacted .= $fieldSeperator;
    $fields .= "$seperator$newField = '" . addslashes($compacted) . "'";
}

function sql_addBoolToUpdate(&$fields, $newField, $bool)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    if ($bool)
    {
        $fields .= "$seperator$newField = true";
    }
    else
    {
        $fields .= "$seperator$newField = false";
    }
}

function sql_addTimestampToUpdate(&$fields, $newField, $newTimestamp)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $newDate = date('r', $newTimestamp);

    if ($newDate)
    {
        $fields .= "$seperator$newField = '$newDate'::timestamp";
    }
}

function sql_addDateToUpdate(&$fields, $newField, $month, $day, $year, $timestamp = false)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $conversion = '';
    $month = ereg_replace('[^0-9]', '', $month);
    $day = ereg_replace('[^0-9]', '', $day);
    $year = ereg_replace('[^0-9]', '', $year);

    if ($timestamp)
    {
        $conversion = "to_timestamp('$month-$day-$year', 'MM-DD-YYYY')";
    }
    else
    {
        $conversion = "to_date('$month-$day-$year', 'MM-DD-YYYY')";
    }

    if (checkdate(intval($month), intval($day), intval($year)))
    {
        //$fields .= "$seperator$newField = CAST('$month-$day-$year' AS $cast)";
        $fields .= "$seperator$newField = $conversion";
    }
}


function sql_addStrDateToUpdate(&$fields, $newField, $newDate, $timestamp = false)
{
    $seperator = '';

    if ($fields != '')
    {
        $seperator = ', ';
    }

    $conversion = '';
    $newDate = date('m-d-Y', strtotime($newDate));

    if ($timestamp)
    {
        $conversion = "to_timestamp('$newDate', 'MM-DD-YYYY')";
    }
    else
    {
        $conversion = "to_date('$newDate', 'MM-DD-YYYY')";
    }

    if ($newDate)
    {
        //$fields .= "$seperator$newField = CAST('$newDate' AS $cast)";
        $fields .= "$seperator$newField = $conversion";
    }
}

?>
