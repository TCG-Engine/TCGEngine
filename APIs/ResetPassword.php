<?php

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";

  $conn = GetLocalMySQLConnection();

  $newPass = TryGet("newPass", "");
  $email = TryGet("email", "");
  $key = TryGet("key", "");

  if($key != hash('sha256', 'There was an issue resetting your password.')) exit;

    // Finally we update the users table with the newly created password.
    $sql = "UPDATE users SET usersPwd=? WHERE usersEmail=?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
      $response->error = "There was an issue resetting your password.";
      echo (json_encode($response));
    	mysqli_close($conn);
      exit();
    } else {
      $newPwdHash = password_hash($newPass, PASSWORD_DEFAULT);
      mysqli_stmt_bind_param($stmt, "ss", $newPwdHash, $email);
      mysqli_stmt_execute($stmt);
      mysqli_close($conn);
      exit();
    }
?>
