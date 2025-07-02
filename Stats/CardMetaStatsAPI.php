<?php

include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$conn = GetLocalMySQLConnection();

$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'cardID';
$sortOrder = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';

$query = "SELECT * FROM cardmetastats ORDER BY $sortColumn $sortOrder";
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
