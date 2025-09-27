<?php

include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$conn = GetLocalMySQLConnection();

// Accept optional startWeek and endWeek query parameters (integers). Default to week=0 for backward compatibility.
$startWeek = isset($_GET['startWeek']) ? intval($_GET['startWeek']) : null;
$endWeek = isset($_GET['endWeek']) ? intval($_GET['endWeek']) : null;

$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'cardID';
$sortOrder = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';

if ($startWeek === null && $endWeek === null) {
  $where = 'week = 0';
} elseif ($startWeek !== null && $endWeek === null) {
  $where = 'week = ' . $startWeek;
} else {
  if ($startWeek === null) $startWeek = $endWeek;
  if ($endWeek === null) $endWeek = $startWeek;
  if ($startWeek > $endWeek) { $tmp = $startWeek; $startWeek = $endWeek; $endWeek = $tmp; }
  $where = 'week BETWEEN ' . $startWeek . ' AND ' . $endWeek;
}

$query = "SELECT * FROM cardmetastats WHERE " . $where . " ORDER BY $sortColumn $sortOrder";
$result = $conn->query($query);

// Aggregate across weeks by cardID
$response = [];
$aggregates = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $cardId = $row['cardID'];
    if (!isset($aggregates[$cardId])) {
      $aggregates[$cardId] = [
        'cardID' => $cardId,
        'timesIncluded' => 0,
        'timesIncludedInWins' => 0,
        'timesPlayed' => 0,
        'timesPlayedInWins' => 0,
        'timesResourced' => 0,
        'timesResourcedInWins' => 0,
      ];
    }
    $aggregates[$cardId]['timesIncluded'] += intval($row['timesIncluded']);
    $aggregates[$cardId]['timesIncludedInWins'] += intval($row['timesIncludedInWins']);
    $aggregates[$cardId]['timesPlayed'] += intval($row['timesPlayed']);
    $aggregates[$cardId]['timesPlayedInWins'] += intval($row['timesPlayedInWins']);
    $aggregates[$cardId]['timesResourced'] += intval($row['timesResourced']);
    $aggregates[$cardId]['timesResourcedInWins'] += intval($row['timesResourcedInWins']);
  }

  foreach ($aggregates as $card) {
    $percentIncludedInWins = ($card['timesIncluded'] > 0) ? ($card['timesIncludedInWins'] / $card['timesIncluded']) * 100 : 0;
    $percentPlayedInWins = ($card['timesPlayed'] > 0) ? ($card['timesPlayedInWins'] / $card['timesPlayed']) * 100 : 0;
    $percentResourcedInWins = ($card['timesResourced'] > 0) ? ($card['timesResourcedInWins'] / $card['timesResourced']) * 100 : 0;

    $cardTitle = CardTitle($card['cardID']);
    $cardSubtitle = CardSubtitle($card['cardID']);
    $cardName = $cardTitle;
    if ($cardSubtitle != "") {
      $cardName .= ", " . $cardSubtitle;
    }

    $response[] = [
      'cardUid' => $card['cardID'],
      'cardName' => $cardName,
      'timesIncluded' => $card['timesIncluded'],
      'timesIncludedInWins' => $card['timesIncludedInWins'],
      'percentIncludedInWins' => number_format($percentIncludedInWins, 2),
      'timesPlayed' => $card['timesPlayed'],
      'timesPlayedInWins' => $card['timesPlayedInWins'],
      'percentPlayedInWins' => number_format($percentPlayedInWins, 2),
      'timesResourced' => $card['timesResourced'],
      'timesResourcedInWins' => $card['timesResourcedInWins'],
      'percentResourcedInWins' => number_format($percentResourcedInWins, 2),
    ];
  }
} else {
  $response['message'] = "No records found.";
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);

?>
