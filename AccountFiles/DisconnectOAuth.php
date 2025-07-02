<?php

include_once '../Core/HTTPLibraries.php';
include_once './AccountSessionAPI.php';
include_once './AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';

$response = new stdClass();

$type = TryGet("type", default: "");

// Ensure request method is POST
if($type == "") {
  $response->error = "Parameter error. Nothing to disconnect.";
  echo json_encode($response);
  exit();
}

if (!IsUserLoggedIn()) {
  $response->error = "You must be logged in to disconnect an oauth connection.";
  echo json_encode($response);
  exit();
}

$userid = LoggedInUser();

if ($type == "discord") {
  $conn = GetLocalMySQLConnection();
  $query = "UPDATE users SET discordID = NULL WHERE usersId = ?";
  $stmt = $conn->prepare($query);
  if ($stmt && $stmt->execute([$userid])) {
    $response->success = "Successfully disconnected {$type}.";
    $_SESSION["discordID"] = "";
  } else {
    $response->error = "Error disconnecting {$type} connection.";
  }
} else if ($type == "patreon") {
  $conn = GetLocalMySQLConnection();
  $query = "UPDATE users SET patreonAccessToken = NULL, patreonRefreshToken = NULL WHERE usersId = ?";
  $stmt = $conn->prepare($query);
  if ($stmt && $stmt->execute([$userid])) {
    $response->success = "Successfully disconnected patreon.";
    unset($_SESSION["patreonAuthenticated"]);
  } else {
    $response->error = "Error disconnecting patreon connection.";
  }
}


echo json_encode($response);

header("Location: /TCGEngine/SharedUI/Profile.php");
exit();

?>