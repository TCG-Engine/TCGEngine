<?php

include_once __DIR__ . '/DatabaseResolution.php';

function GetLocalMySQLConnection()
{
  $hostname = getenv("MYSQL_SERVER_NAME") ?: "localhost";
  $username = getenv("MYSQL_SERVER_USER_NAME") ?: "root";
  $password = getenv("MYSQL_ROOT_PASSWORD") ?: "";
  // Throws on an unresolvable database rather than defaulting. The old default silently connected
  // every env-less CLI run to one fixed database, which is how a migration or DevTools script could
  // read and WRITE the wrong site's tables without a single error.
  $database = ResolveDatabaseName();

  $conn = mysqli_connect($hostname, $username, $password, $database);
  if (!$conn) {
    error_log("MySQL Connection error: " . mysqli_connect_error());
    return false;
  }

  return $conn;
}
?>
