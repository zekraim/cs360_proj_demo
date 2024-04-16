<?php

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "cs360_project";

if(!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname, 3307)){
    die("Failed to connect to database!");
}

