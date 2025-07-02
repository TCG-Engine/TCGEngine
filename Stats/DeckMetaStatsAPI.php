<?php

include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/Custom/CardIdentifiers.php';

header('Content-Type: application/json');

$conn = GetLocalMySQLConnection();
$query = "SELECT leaderID, baseID, week, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond FROM deckmetastats WHERE week = 0";
$result = $conn->query($query);

$response = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Store original string values to preserve leading zeros
    $leaderIDstr = $row['leaderID'];
    $baseIDstr = $row['baseID'];
    
    // Use integers only for comparison, not for storage
    $leaderID_int = intval($leaderIDstr);
    $baseID_int = intval($baseIDstr);
    $numPlays = intval($row['numPlays']);

    // Skip entries with empty leaderID, -1 IDs, or zero plays
    if ($leaderIDstr === "" || $leaderID_int === -1 || $baseID_int === -1 || $numPlays === 0) {
      continue;
    }

    $response[] = [
      'leaderID' => $leaderIDstr,
      'leaderTitle' => CardTitle($leaderIDstr),
      'leaderSubtitle' => CardSubtitle($leaderIDstr),
      'baseID' => $baseIDstr,
      'baseTitle' => CardTitle($baseIDstr),
      'baseSubtitle' => CardSubtitle($baseIDstr),
      'numPlays' => $numPlays,
      'winRate' => number_format(($numPlays > 0 ? ($row['numWins'] / $numPlays) * 100 : 0), 2, '.', ''),
      'avgTurnsInWins' => $row['numWins'] > 0 ? number_format($row['turnsInWins'] / $row['numWins'], 2, '.', '') : null,
      'avgTurnsInLosses' => ($numPlays - $row['numWins']) > 0 ? number_format(($row['totalTurns'] - $row['turnsInWins']) / ($numPlays - $row['numWins']), 2, '.', '') : null,
      'avgCardsResourcedInWins' => $row['numWins'] > 0 ? number_format($row['cardsResourcedInWins'] / $row['numWins'], 2, '.', '') : null,
      'avgRemainingHealthInWins' => $row['numWins'] > 0 ? number_format($row['remainingHealthInWins'] / $row['numWins'], 2, '.', '') : null,
    ];
  }
}

$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
