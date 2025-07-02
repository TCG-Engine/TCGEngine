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
  $response->error = "You must be logged in to invite a user to a team";
  echo json_encode($response);
  exit();
}

$userid = LoggedInUser();

$userData = LoadUserDataFromId($userid);

if($userData['teamID'] == null) {
  $response->error = "You must be on a team to invite a user to a team";
  echo json_encode($response);
  exit();
}

if (!isset($_POST['inviteeName']) || empty(trim($_POST['inviteeName']))) {
  $response->error = "Invitee name not provided.";
  echo json_encode($response);
  exit();
}
$inviteeName = trim($_POST['inviteeName']);
$inviteeData = LoadUserData($inviteeName);
if($inviteeData == null) {
  $response->error = "User not found.";
  echo json_encode($response);
  exit();
}
$inviteeID = $inviteeData['usersId'];

$conn = GetLocalMySQLConnection();

$teamID = $userData['teamID'];
$stmt = $conn->prepare("INSERT INTO teaminvite (teamID, userID, invitedBy) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $teamID, $inviteeID, $userid);
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