<?php

include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/Custom/CardIdentifiers.php';

header('Content-Type: application/json');

$conn = GetLocalMySQLConnection();

// Accept optional startWeek and endWeek query parameters (integers). Default behavior is week=0 for backward compatibility.
$startWeek = isset($_GET['startWeek']) ? intval($_GET['startWeek']) : null;
$endWeek = isset($_GET['endWeek']) ? intval($_GET['endWeek']) : null;

if ($startWeek === null && $endWeek === null) {
  $where = 'week = 0';
} elseif ($startWeek !== null && $endWeek === null) {
  // Single week
  $where = 'week = ' . $startWeek;
} else {
  // Both provided or only endWeek provided: normalize so start <= end
  if ($startWeek === null) $startWeek = $endWeek;
  if ($endWeek === null) $endWeek = $startWeek;
  if ($startWeek > $endWeek) {
    $tmp = $startWeek; $startWeek = $endWeek; $endWeek = $tmp;
  }
  $where = 'week BETWEEN ' . $startWeek . ' AND ' . $endWeek;
}

$query = "SELECT leaderID, baseID, week, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond FROM deckmetastats WHERE " . $where;
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
