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
  $response->error = "You must be logged in to create a team";
  echo json_encode($response);
  exit();
}

// Use filter_input to get the POST parameter
$teamName = filter_input(INPUT_POST, 'teamName', FILTER_SANITIZE_STRING);

if (empty($teamName)) {
  $response->error = "Missing parameters";
  echo json_encode($response);
  exit();
}

$userid = LoggedInUser();
$conn = GetLocalMySQLConnection();
$stmt = $conn->prepare("INSERT INTO team (TeamName, ownerID) VALUES (?, ?)");
$stmt->bind_param("si", $teamName, $userid);
$result = $stmt->execute();
$teamID = $conn->insert_id;
$stmt->close();

if ($result) {
  $stmt = $conn->prepare("UPDATE users SET teamID = ? WHERE usersId = ?");
  $stmt->bind_param("ii", $teamID, $userid);
  $result = $stmt->execute();
  $stmt->close();
}

$conn->close();

if (!$result) {
  $response->error = "Failed to create team.";
} else {
  $response->success = true;
}

echo json_encode($response);

header("Location: /TCGEngine/SharedUI/Profile.php");
exit();

?>