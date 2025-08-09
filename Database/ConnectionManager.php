<?php

function GetLocalMySQLConnection()
{
  $hostname = getenv("MYSQL_SERVER_NAME") ?: "localhost";
  $username = getenv("MYSQL_SERVER_USER_NAME") ?: "root";
  $password = getenv("MYSQL_ROOT_PASSWORD") ?: "";
  $database = getenv("MYSQL_DATABASE_NAME") ?: "swuonline";

  $conn = mysqli_connect($hostname, $username, $password, $database);
  if (!$conn) {
    error_log("MySQL Connection error: " . mysqli_connect_error());
    return false;
  }

  return $conn;
}
?>
