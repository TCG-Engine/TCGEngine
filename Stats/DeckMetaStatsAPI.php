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

// Aggregate results across weeks by leaderID+baseID
$response = [];
$aggregates = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $leaderIDstr = $row['leaderID'];
    $baseIDstr = $row['baseID'];

    // Use integers only for comparison
    $leaderID_int = intval($leaderIDstr);
    $baseID_int = intval($baseIDstr);
    $numPlays = intval($row['numPlays']);

    // Skip invalid entries
    if ($leaderIDstr === "" || $leaderID_int === -1 || $baseID_int === -1 || $numPlays === 0) {
      continue;
    }

    $key = $leaderIDstr . '|' . $baseIDstr;
    if (!isset($aggregates[$key])) {
      $aggregates[$key] = [
        'leaderID' => $leaderIDstr,
        'baseID' => $baseIDstr,
        'numWins' => 0,
        'numPlays' => 0,
        'playsGoingFirst' => 0,
        'turnsInWins' => 0,
        'totalTurns' => 0,
        'cardsResourcedInWins' => 0,
        'totalCardsResourced' => 0,
        'remainingHealthInWins' => 0,
        'winsGoingFirst' => 0,
        'winsGoingSecond' => 0,
      ];
    }

    $aggregates[$key]['numWins'] += intval($row['numWins']);
    $aggregates[$key]['numPlays'] += intval($row['numPlays']);
    $aggregates[$key]['playsGoingFirst'] += intval($row['playsGoingFirst']);
    $aggregates[$key]['turnsInWins'] += intval($row['turnsInWins']);
    $aggregates[$key]['totalTurns'] += intval($row['totalTurns']);
    $aggregates[$key]['cardsResourcedInWins'] += intval($row['cardsResourcedInWins']);
    $aggregates[$key]['totalCardsResourced'] += intval($row['totalCardsResourced']);
    $aggregates[$key]['remainingHealthInWins'] += intval($row['remainingHealthInWins']);
    $aggregates[$key]['winsGoingFirst'] += intval($row['winsGoingFirst']);
    $aggregates[$key]['winsGoingSecond'] += intval($row['winsGoingSecond']);
  }

  // Build response from aggregates
  foreach ($aggregates as $agg) {
    $numPlays = $agg['numPlays'];
    $numWins = $agg['numWins'];
    $response[] = [
      'leaderID' => $agg['leaderID'],
      'leaderTitle' => CardTitle($agg['leaderID']),
      'leaderSubtitle' => CardSubtitle($agg['leaderID']),
      'baseID' => $agg['baseID'],
      'baseTitle' => CardTitle($agg['baseID']),
      'baseSubtitle' => CardSubtitle($agg['baseID']),
      'numPlays' => $numPlays,
      'winRate' => number_format(($numPlays > 0 ? ($numWins / $numPlays) * 100 : 0), 2, '.', ''),
      'avgTurnsInWins' => $numWins > 0 ? number_format($agg['turnsInWins'] / $numWins, 2, '.', '') : null,
      'avgTurnsInLosses' => ($numPlays - $numWins) > 0 ? number_format(($agg['totalTurns'] - $agg['turnsInWins']) / ($numPlays - $numWins), 2, '.', '') : null,
      'avgCardsResourcedInWins' => $numWins > 0 ? number_format($agg['cardsResourcedInWins'] / $numWins, 2, '.', '') : null,
      'avgRemainingHealthInWins' => $numWins > 0 ? number_format($agg['remainingHealthInWins'] / $numWins, 2, '.', '') : null,
    ];
  }
}

$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
