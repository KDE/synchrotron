<?php
/*
 * Copyright (C) 2006 Andrew Kopciuch <akopciuch@bddf.ca>
 * Copyright (C) 2006 Aaron Seigo <aseigo@bddf.ca>
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

include_once("$common_includePath/util.php");
include_once("$common_includePath/sql.php");

$synchrotron_dbs = array();

$db_debugFrom = 0;
$db_debugTo = 99999;
$db_debugThreshold = 0;

class SynchrotronDBConnection
{
    var $identifier;
    var $db_host;
    var $db_port;
    var $db_name;
    var $db_username;
    var $db_password;
    var $localConnection;
    var $persistent;
    var $resource;

    function SynchrotronDBConnection($identifier)
    {
        $this->identifier = $identifier;
        $this->localConnection = true;
        $this->persistent = false;
        $this->resource = null;
    }

    function Copy($identifier, &$db)
    {
        $copy = new SynchrotronDBConnection($identifier);

        //$this->identifier = $identifier;
        $copy->db_host = $db->db_host;
        $copy->db_port = $db->db_port;
        $copy->db_name = $db->db_name;
        $copy->db_username = $db->db_username;
        $copy->db_password = $db->db_password;
        $copy->localConnection = $db->localConnection;
        $copy->persistent = $db->persistent;
        $copy->resource = null;

        return $copy;
    }

    function connectString()
    {
        if ($this->localConnection)
        {
            return "dbname={$this->db_name} user={$this->db_username} password={$this->db_password}";
        }
        else
        {
            return "host={$this->db_host} port={$this->db_port} dbname={$this->db_name} user={$this->db_username} password={$this->db_password}";
        }
    }
}

function db_register(&$db_connection)
{
    global $synchrotron_dbs;

    if ($db_connection instanceof SynchrotronDBConnection)
    {
        $synchrotron_dbs[$db_connection->identifier] = $db_connection;
        return true;
    }

    return false;
}

// REGISTER A DEFAULT DB
global $db_localOnly;
$defaultDB = new SynchrotronDBConnection('default');
$defaultDB->db_host = $db_host;
$defaultDB->db_port = $db_port;
$defaultDB->db_name = $db_name;
$defaultDB->db_username = $db_username;
$defaultDB->db_password = $db_password;
$defaultDB->localConnection = $db_localOnly;
db_register($defaultDB);


/*
 **********************************************************************
 * database connections
 **********************************************************************
 */

function db_connection($identifier = 'default')
{
    global $synchrotron_dbs;

    $db = $synchrotron_dbs[$identifier];

    if (!$db)
    {
        $db = SynchrotronDBConnection::Copy($identifier, $synchrotron_dbs['default']);
    }

    if ($db instanceof SynchrotronDBConnection)
    {
        if (!$db->resource)
        {
            if ($db->persistent)
            {
                $db->resource = pg_pconnect($db->connectString(), PGSQL_CONNECT_FORCE_NEW);
            }
            else
            {
                $db->resource = pg_connect($db->connectString(), PGSQL_CONNECT_FORCE_NEW);
            }
        }

        return $db->resource;
    }

    return false;
}

function db_close($identifier = 'default')
{
    global $synchrotron_dbs;

    $db = $synchrotron_dbs[$identifier];

    if ($db instanceof SynchrotronDBConnection)
    {
        return pg_close($db->resource);
    }

    return false;
}

/*
 **********************************************************************
 * debugging configuration functions
 **********************************************************************
 */

function db_setDebug($enableDebug, $enableProfiling)
{
    global $db_debug, $db_profile;

    $db_debug = $enableDebug;
    $db_profile = $enableProfiling;
}

function db_setDebugParameters($startAtQuery = 0,
                            $stopAtQuery = 9999,
                            $timeThreshhold = 0)
{
    global $db_debugFrom, $db_debugTo, $db_debugThreshold;

    $db_debugFrom = $startAtQuery;
    $db_debugTo = $stopAtQuery;
    $db_debugThreshold = $timeThreshhold;
}

function db_printProfilingSummary()
{
    db_query('', '', 1, 2);
}

/*
 **********************************************************************
 * After all that other stuff is set ... let's query the DB
 **********************************************************************
 */

function db_query($db_connection, $sql, $debug = 0, $profile = 0)
{
    global $db_errorMsg, $db_debug, $db_profile;

    if ($profile || $db_profile)
    {
        static $summary = array();
        if ($profile == 2) // summarize!
        {
            arsort($summary);
            $count = count($summary);
            $totalTime = 0;
            foreach ($summary as $queryNumber => $time)
            {
                $totalTime += floatval($time);
            }

            print "<table><tr><th colspan=3><h2><a name=\"dbProfileTable\">Page Database Usage Summary</a></h3></th></tr>";
            print "<tr><th>Query#</th><th>Time (ms)</th><th>% Total</th></tr>";
            foreach ($summary as $queryNumber => $time)
            {
                $time = floatval($summary[$queryNumber]);
                $percent = number_format(floatval($time) / $totalTime, 4) * 100;
                $totalPercent += $percent;
                print "<tr><td>$queryNumber</td><td><a href=\"#dbQuery$queryNumber\">$time</a></td><td>$percent %</td></tr>";
            }
            print "<tr><th>$count queries</th><th>$totalTime ms</th><th>$totalPercent %</th></tr>";
            print "</table>";
            return;
        }
    }

    if ($debug || $db_debug)
    {
        global $db_debugFrom, $db_debugTo, $db_debugThreshold;


        static $i = 0;
        ++$i;
        $printDetails = ($db_debugFrom <= $i && $i <= $db_debugTo);
        unset($details);
        if ($printDetails)
        {
            $details .= "<pre><a name=\"dbQuery$i\" href=\"#dbProfileTable\">$i</a>: <b>"
                         . htmlentities($sql) . "</b>\n\n";
        }

        if ($profile || $db_profile)
        {
            $howBad = pg_exec($db_connection, "EXPLAIN ANALYZE $sql");
            $numBad = db_numRows($howBad);
            unset($line);
            for ($j = 0; $j < $numBad; ++$j)
            {
                list($line) = db_row($howBad, $j);

                if ($printDetails)
                {
                    $details .= "$line\n";
                }
            }

            $time = preg_match("/Total runtime: ([0-9\.]+)/", $line, $runtime);
            if ($runtime[1])
            {
                $summary[$i] = $runtime[1];
                $printDetails = $printDetails &&
                                floatval($runtime[1]) > $db_debugThreshold;
            }
        }

        if ($printDetails)
        {
            print $details . '</pre>';
        }
    }

    $rv = pg_exec($db_connection, $sql);

    if (!$rv && $db_errorMsg)
    {
        print_msg('ERROR', _("Database Execution Error!"),
                  pg_errormessage($db_connection) . "<br><br><b>" .
                  _("Statement passed to database was:") .
                  "</b><br><br>$sql");
    }

    return $rv;
}

/*
 **********************************************************************
 * handling the result sets from db_query, 
 * number of rows, retrieve a row
 **********************************************************************
 */

function db_numRows($db_query)
{
    return pg_numrows($db_query);
}

function db_row($db_query, $row)
{
    return pg_fetch_row($db_query, $row);
}

function db_rowArray($db_query, $row)
{
    return pg_fetch_array($db_query, $row);
}

function db_rowAssocArray($db_query, $row)
{
    return pg_fetch_array($db_query, $row, PGSQL_ASSOC);
}

function db_array($db_query)
{
    $rv = array();

    $numRows = pg_numrows($db_query);

    for ($i = 0; $i < $numRows; $i++)
    {
        list($val) = pg_fetch_row($db_query, $i);
        array_push($rv, $val);
    }

    if (count($rv) <=0)
    {
        return NULL;
    }
    return $rv;
}

function db_assocArray($db_query)
{
    $rv = array();

    $numRows = pg_numrows($db_query);

    for ($i = 0; $i < $numRows; $i++)
    {
        list($key, $val) = db_row($db_query, $i);
        $rv[$key] = $val;
    }

    if (count($rv) <=0)
    {
        return NULL;
    }
    return $rv;
}

/*
 **********************************************************************
 * start, end, abort a transaction
 **********************************************************************
 */

function db_startTransaction($db_connection)
{
    return pg_exec($db_connection, "BEGIN;");
}

function db_endTransaction($db_connection)
{
    return pg_exec($db_connection, "COMMIT;");
}

function db_abortTransaction($db_connection)
{
    return pg_exec($db_connection, "ABORT;");
}

/*
 **********************************************************************
 * SEQUENCE handling for nextval, currval
 **********************************************************************
 */

function db_seqNextVal($db_connection, $sequence)
{
    $rc = -1;
    $seqVal = db_query($db_connection, "SELECT nextval('" . addslashes($sequence) . "');");
    if (db_numRows($seqVal) > 0)
    {
        list($rc) = db_row($seqVal, 0);
    }
    return $rc;
}

function db_seqCurrentVal($db_connection, $sequence)
{
    $rc = -1;
    $seqVal = db_query($db_connection, "SELECT currval('" . addslashes($sequence) . "');");
    if (db_numRows($seqVal) > 0)
    {
        list($rc) = db_row($seqVal, 0);
    }
    return $rc;
}

/*
 **********************************************************************
 * conversions from db data formats to PHP data formats
 **********************************************************************
 */

function db_boolean($bool)
{
    if ($bool == 't' || $bool == 'true')
    {
        return true;
    }

    return false;
}

function db_toBoolean($bool)
{
    if ($bool)
    {
        return 'true';
    }

    return 'false';
}

/*
 **********************************************************************
 * INSERT, UPDATE, and DELETE
 **********************************************************************
 */

function db_insert($db, $table, $fields, $values, $debug = 0, $profile = 0)
{
    return db_query($db, "INSERT INTO $table ($fields) VALUES ($values);", $debug, $profile);
}

function db_update($db, $table, $fields, $where = '', $debug = 0, $profile = 0)
{
    if ($where)
    {
        return db_query($db, "UPDATE $table SET $fields WHERE $where;", $debug, $profile);
    }

    return db_query($db, "UPDATE $table SET $fields;", $debug, $profile);
}

function db_delete($db, $table, $where = '', $debug = 0, $profile = 0)
{
    if ($where)
    {
        return db_query($db, "DELETE FROM $table WHERE $where;", $debug, $profile);
    }

    return db_query($db, "DELETE FROM $table;", $debug, $profile);
}


/* FUNC:    db_quickQuery
 * PARAMS:  $db, the db connection
 *          $query, the query string you would like to execute
 *          [$noResultsError, outputs an error if there are no results
 *           $debug, outputs debugging info, including the query
 *           $profile, outputs profile data from the query]
 * DESC:    This function calls db_query with the given params, and then checks
 *          to see if any rows were returned.  If rows were returned it returns
 *          the data array for the first row (row 0).  Otherwise it returns
 *          false.  Note: list(...) = false, sets all of the variables to null.
 * USAGE:   This should be used if you're getting data from a single row,
 *          generally you will list the variables from the data like so:
 *          list($id, $dataA, $dataB) = db_quickQuery($db,
 *              "SELECT id, a, b FROM my_table WHERE id = 1000");
 */

function db_quickQuery($db, $query, $noResultsError = false, $debug = 0, $profile = 0)
{
    $results = db_query($db, $query, $debug, $profile);

    if (db_numRows($results) > 0)
    {
        return db_row($results, 0);
    }
    else if ($noResultsError)
    {
        print_msg('ERROR', "db_quickQuery(...)",
            "0 rows returned on the following query:<BR><PRE>".$query."</PRE>");
    }
        
    return false;
}

?>
