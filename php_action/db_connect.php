<?php

// Database settings. Edit these, or set the TT_DB_* environment variables.
$localhost = getenv('TT_DB_HOST') ?: "localhost";
$username  = getenv('TT_DB_USER') ?: "root";
$password  = getenv('TT_DB_PASS') !== false ? getenv('TT_DB_PASS') : "";
$dbname    = getenv('TT_DB_NAME') ?: "timetable";
$dbsocket  = getenv('TT_DB_SOCKET') ?: null;

// Base URL used for redirects ("" = relative to the current folder)
$store_url = "";

mysqli_report(MYSQLI_REPORT_OFF);

// db connection
$connect = new mysqli($localhost, $username, $password, $dbname, 3306, $dbsocket);
// check connection
if($connect->connect_error) {
  die("Connection Failed : " . $connect->connect_error);
}
$connect->set_charset('utf8mb4');

?>
