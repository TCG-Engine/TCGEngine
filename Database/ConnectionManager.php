<?php

function GetLocalMySQLConnection()
{
  $hostname = (!empty(getenv("MYSQL_SERVER_NAME")) ? getenv("MYSQL_SERVER_NAME") : "localhost");
  $username = (!empty(getenv("MYSQL_SERVER_USER_NAME")) ? getenv("MYSQL_SERVER_USER_NAME") : "root");
  $password = (!empty(getenv("MYSQL_ROOT_PASSWORD")) ? getenv("MYSQL_ROOT_PASSWORD") : "");
  $database = (!empty(getenv("MYSQL_DATABASE_NAME")) ? getenv("MYSQL_DATABASE_NAME") : "swuonline");

  $conn = mysqli_connect($hostname, $username, $password, $database);
  if (!$conn) {
    error_log("MySQL Connection error: " . mysqli_connect_error());
    return false;
  }

  return $conn;
}
?>
