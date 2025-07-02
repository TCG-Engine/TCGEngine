<?php

include_once '../Core/HTTPLibraries.php';
include_once './AccountSessionAPI.php';
include_once './AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';

$response = new stdClass();

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $response->error = "Invalid request method.";
  echo json_encode($response);
  exit();
}

if (!IsUserLoggedIn()) {
  $response->error = "You must be logged in to leave a team";
  echo json_encode($response);
  exit();
}

$userid = LoggedInUser();
$conn = GetLocalMySQLConnection();

$stmt = $conn->prepare("UPDATE users SET teamID = NULL WHERE usersId = ?");
$stmt->bind_param("i", $userid);
$result = $stmt->execute();
$stmt->close();

$conn->close();

if (!$result) {
  $response->error = "Failed to leave team.";
} else {
  $response->success = true;
}

echo json_encode($response);

header("Location: /TCGEngine/SharedUI/Profile.php");
exit();

?>