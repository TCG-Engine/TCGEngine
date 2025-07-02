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
  $response->error = "You must be logged in to process a team invitation.";
  echo json_encode($response);
  exit();
}

// Use filter_input to get the POST parameter
$invitationID = filter_input(INPUT_POST, 'invitationID', FILTER_SANITIZE_STRING);
$mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING);

if (empty($invitationID) || empty($mode)) {
  $response->error = "Missing parameters";
  echo json_encode($response);
  exit();
}

$userid = LoggedInUser();
$conn = GetLocalMySQLConnection();

$result = true;

if ($mode === "accept") {
  $stmt = $conn->prepare("SELECT teamID FROM teaminvite WHERE inviteID = ?");
  if ($stmt === false) {
    $result = false;
  } else {
    $stmt->bind_param("i", $invitationID);
    $stmt->execute();
    $stmt->bind_result($teamID);
    if ($stmt->fetch()) {
      $stmt->close();

      $updateStmt = $conn->prepare("UPDATE users SET teamID = ? WHERE usersId = ?");
      if ($updateStmt === false) {
        $result = false;
      } else {
        $updateStmt->bind_param("ii", $teamID, $userid);
        if (!$updateStmt->execute()) {
          $result = false;
        }
        $updateStmt->close();
      }
    } else {
      $stmt->close();
      $result = false;
    }
  }
}

$delStmt = $conn->prepare("DELETE FROM teaminvite WHERE inviteID = ?");
if ($delStmt === false) {
  $result = false;
} else {
  $delStmt->bind_param("i", $invitationID);
  if (!$delStmt->execute()) {
    $result = false;
  }
  $delStmt->close();
}


$conn->close();

if (!$result) {
  $response->error = "Failed to process team invitation.";
} else {
  $response->success = true;
}

echo json_encode($response);

header("Location: /TCGEngine/SharedUI/Profile.php");
exit();

?>