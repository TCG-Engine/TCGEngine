<?php

include_once '../SharedUI/MenuBar.php';
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$isMobile = IsMobile();

$forIndividual = false;

$conn = GetLocalMySQLConnection();
echo "<div style='overflow-x:auto; overflow-y:auto; max-height: calc(100vh - 200px); bottom:20px; scrollbar-width: thin; scrollbar-color: #888 #f1f1f1; display: flex; justify-content: center;'>";
echo "<style>
  /* Modern scrollbar styles */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  ::-webkit-scrollbar-thumb {
    background-color: #888;
    border-radius: 10px;
    border: 2px solid transparent;
    background-clip: content-box;
  }
  ::-webkit-scrollbar-thumb:hover {
    background-color: #555;
  }
  ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }
</style>";

$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'cardID';
$sortOrder = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';
$newOrder = $sortOrder == 'asc' ? 'desc' : 'asc';
$arrow = $sortOrder == 'asc' ? '↑' : '↓';

$query = "SELECT * FROM cardmetastats ORDER BY $sortColumn $sortOrder";
$result = $conn->query($query);

if ($result->num_rows > 0) {
  echo "<table border='1' style='border-collapse: collapse;'>";
  echo "<tr style='background: linear-gradient(135deg, rgba(16, 16, 128, 0.8) 25%, rgba(32, 32, 144, 0.8) 25%, rgba(32, 32, 144, 0.8) 50%, rgba(16, 16, 128, 0.8) 50%, rgba(16, 16, 128, 0.8) 75%, rgba(32, 32, 144, 0.8) 75%, rgba(32, 32, 144, 0.8)); background-size: 28.28px 28.28px; color: white;'>"; // Blended pattern
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=cardID&order=$newOrder'>Card ID" . ($sortColumn == 'cardID' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=week&order=$newOrder'>Week" . ($sortColumn == 'week' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=timesIncluded&order=$newOrder'>Times Included" . ($sortColumn == 'timesIncluded' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=timesIncludedInWins&order=$newOrder'>Times Included In Wins" . ($sortColumn == 'timesIncludedInWins' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>% Included In Wins</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=timesPlayed&order=$newOrder'>Times Played" . ($sortColumn == 'timesPlayed' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=timesPlayedInWins&order=$newOrder'>Times Played In Wins" . ($sortColumn == 'timesPlayedInWins' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>% Played In Wins</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=timesResourced&order=$newOrder'>Times Resourced" . ($sortColumn == 'timesResourced' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'><a href='?sort=timesResourcedInWins&order=$newOrder'>Times Resourced In Wins" . ($sortColumn == 'timesResourcedInWins' ? " $arrow" : "") . "</a></th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>% Resourced In Wins</th>";
  echo "</tr>";

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
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $cardName . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['week']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['timesIncluded']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['timesIncludedInWins']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($percentIncludedInWins, 2) . "%</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['timesPlayed']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['timesPlayedInWins']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($percentPlayedInWins, 2) . "%</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['timesResourced']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['timesResourcedInWins']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($percentResourcedInWins, 2) . "%</td>";
    echo "</tr>";
  }
  echo "</table>";
} else {
  echo "No records found.";
}
echo "</div>";

$conn->close();

include_once '../SharedUI/Disclaimer.php';

?>