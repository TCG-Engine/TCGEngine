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

$response = [];

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $percentIncludedInWins = ($row['timesIncluded'] > 0) ? ($row['timesIncludedInWins'] / $row['timesIncluded']) * 100 : 0;
    $percentPlayedInWins = ($row['timesPlayed'] > 0) ? ($row['timesPlayedInWins'] / $row['timesPlayed']) * 100 : 0;
    $percentResourcedInWins = ($row['timesResourced'] > 0) ? ($row['timesResourcedInWins'] / $row['timesResourced']) * 100 : 0;

    $cardTitle = CardTitle($row['cardID']);
    $cardSubtitle = CardSubtitle($row['cardID']);
    $cardName = $cardTitle;
    if ($cardSubtitle != "") {
      $cardName .= ", " . $cardSubtitle;
    }

    $response[] = [
      'cardUid' => $row['cardID'],
      'cardName' => $cardName,
      'week' => $row['week'],
      'timesIncluded' => $row['timesIncluded'],
      'timesIncludedInWins' => $row['timesIncludedInWins'],
      'percentIncludedInWins' => number_format($percentIncludedInWins, 2),
      'timesPlayed' => $row['timesPlayed'],
      'timesPlayedInWins' => $row['timesPlayedInWins'],
      'percentPlayedInWins' => number_format($percentPlayedInWins, 2),
      'timesResourced' => $row['timesResourced'],
      'timesResourcedInWins' => $row['timesResourcedInWins'],
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
