<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';

  include_once '../Database/ConnectionManager.php';
  include_once '../AccountFiles/AccountDatabaseAPI.php';
  include_once '../AccountFiles/AccountSessionAPI.php';

  $deckID = TryGet("deckID", "");
  $format = strtolower(TryGet("format", "premier"));

  // Add validation to ensure only deck owner can clear stats
  if(!IsUserLoggedIn()) {
    echo("You must be logged in to clear stats.");
    exit;
  }
  
  $loggedInUser = LoggedInUser();
  $assetData = LoadAssetData(1, $deckID);
  
  if($assetData == null) {
    echo("This deck does not exist.");
    exit;
  }
  
  $assetOwner = $assetData["assetOwner"];
  if($loggedInUser != $assetOwner) {
    echo("You must be the deck owner to clear its stats.");
    exit;
  }

  $conn = GetLocalMySQLConnection();

  // Reset deckstats for this format only
  $stmt = $conn->prepare("UPDATE deckstats SET numWins = 0, numPlays = 0, playsGoingFirst = 0, turnsInWins = 0, totalTurns = 0, cardsResourcedInWins = 0, totalCardsResourced = 0, remainingHealthInWins = 0, winsGoingFirst = 0, winsGoingSecond = 0 WHERE deckID = ? AND format = ?");
  $stmt->bind_param("is", $deckID, $format);
  $stmt->execute();
  $stmt->close();

  // Clear carddeckstats for this format
  $stmt = $conn->prepare("DELETE FROM carddeckstats WHERE deckID = ? AND format = ?");
  $stmt->bind_param("is", $deckID, $format);
  $stmt->execute();
  $stmt->close();

  // Clear opponentdeckstats for this format
  $stmt = $conn->prepare("DELETE FROM opponentdeckstats WHERE deckID = ? AND format = ?");
  $stmt->bind_param("is", $deckID, $format);
  $stmt->execute();
  $stmt->close();

  // Clear opponentnamedbasestats for this format (Rare/Special base matchup rows, tracked by baseID)
  $stmt = $conn->prepare("DELETE FROM opponentnamedbasestats WHERE deckID = ? AND format = ?");
  $stmt->bind_param("is", $deckID, $format);
  $stmt->execute();
  $stmt->close();

  $conn->close();
  
  echo "Stats cleared successfully.";
?>