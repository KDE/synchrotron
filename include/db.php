<?php

global $db_type;
if ( $db_type == "postgres" ) {
    include_once("$common_includePath/dbal/postgres.php");
} else if ( $db_type == "mysql" ) {
    include_once("$common_includePath/dbal/mysql.php");
} else {
    die("database backend not found!");
}

?>