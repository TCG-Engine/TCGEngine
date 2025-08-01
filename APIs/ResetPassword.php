<?php

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";

  session_start();
  require_once "../AccountFiles/AccountSessionAPI.php";
  $response = new stdClass();
  $response->success = false;

  if (!IsUserLoggedIn()) {
    $response->error = "You must be logged in.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
  }

  $conn = GetLocalMySQLConnection();
  $newPass = isset($_GET['newPass']) ? $_GET['newPass'] : '';

  if (empty($newPass)) {
    $response->error = "No password provided.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
  }

  $userId = LoggedInUser();
  $sql = "UPDATE users SET usersPwd=? WHERE usersId=?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    $response->error = "There was an issue resetting your password.";
    header('Content-Type: application/json');
    echo json_encode($response);
    mysqli_close($conn);
    exit();
  } else {
    $newPwdHash = password_hash($newPass, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, "si", $newPwdHash, $userId);
    if (mysqli_stmt_execute($stmt)) {
      $response->success = true;
      $response->message = "Password updated successfully.";
    } else {
      $response->error = "Failed to update password.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    mysqli_close($conn);
    exit();
  }
?>
