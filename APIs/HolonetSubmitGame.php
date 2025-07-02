<?php

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";

  $input = file_get_contents('php://input');

  $conn = GetLocalMySQLConnection();

  $sql = "INSERT INTO deck_game_raw_data (deckID, gameStats, source) VALUES (?, ?, ?)";
  $stmt = mysqli_stmt_init($conn);
  if(mysqli_stmt_prepare($stmt, $sql)) {
    mysqli_stmt_bind_param($stmt, "isi", $deckID, $input, 1);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }
  
  mysqli_close($conn);

?>
