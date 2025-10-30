<?php


  include_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
  include_once './Database/ConnectionManager.php';

  //Set up the list of cards to choose from
  $p1Leaders = [];
  $p1Bases = [];
  $p1Cards = [];
  $allCards = GetAllCardIds();
  foreach ($allCards as $cardId) {
    $cardType = CardType($cardId);
    if($cardType == "Leader") {
      array_push($p1Leaders, new Leaders($cardId));
    } else if($cardType == "Base") {
      array_push($p1Bases, new Bases($cardId));
    } else {
      array_push($p1Cards, new Cards($cardId));
    }
  }

  WriteGamestate("./SWUDeck/");

  $conn = GetLocalMySQLConnection();
  
  // First check if there are any rows with source = 1 (deck owner stats)
  $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM carddeckstats WHERE deckID = ? AND source = 1");
  $checkStmt->bind_param("i", $gameName);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();
  $row = $checkResult->fetch_assoc();
  $sourceToUse = ($row['count'] > 0) ? 1 : 0;
  $checkStmt->close();
  
  // Now query with the appropriate source filter
  $stmt = $conn->prepare("SELECT * FROM carddeckstats WHERE deckID = ? AND source = ?");
  $stmt->bind_param("ii", $gameName, $sourceToUse);
  $stmt->execute();
  $result = $stmt->get_result();
  $stmt->close();
  $conn->close();
  $playWinRate = "";
  $resourceRatio = "";
  
  while ($row = $result->fetch_assoc()) {
    $playWinRate .= "    case '" . $row["cardID"] . "': return " . ($row["timesPlayed"] > 0 ? round($row["timesPlayedInWins"] / $row["timesPlayed"], 4) : -1) . ";\r\n";
    $resourceRatio .= "    case '" . $row["cardID"] . "': return " . ($row["timesResourced"] + $row["timesPlayed"] > 0 ? round($row["timesResourced"] / ($row["timesResourced"] + $row["timesPlayed"]), 4) : -1) . ";\r\n";
  }

  echo("<script>\r\n");
  echo("function CardPlayWinRate(cardId) {\r\n");
  echo("  switch(cardId) {\r\n");
  echo($playWinRate);
  echo("    default: return -1;\r\n");
  echo("  }\r\n");
  echo("}\r\n");
  echo("function CardResourceRatio(cardId) {\r\n");
  echo("  switch(cardId) {\r\n");
  echo($resourceRatio);
  echo("    default: return -1;\r\n");
  echo("  }\r\n");
  echo("}\r\n");
  echo("function PriceHeatmap(cardId) {\r\n");
  echo("  switch(cardId) {\r\n");
  //echo($resourceRatio);
  echo("    default: return 0.01;\r\n");
  echo("  }\r\n");
  echo("}\r\n");
  echo("</script>\r\n");

  include_once './Utils/Output/SWUSimImplementation.php';

      /*
    $cardStatsTable .= "<tr>";
    $cardStatsTable .= "<td>" . htmlspecialchars(CardTitle($row["cardID"]), ENT_QUOTES, 'UTF-8') . "</td>";
    $cardStatsTable .= "<td>" . $row["timesIncluded"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesIncludedInWins"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesPlayed"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesPlayedInWins"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesResourced"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesResourcedInWins"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesDiscarded"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesDiscardedInWins"] . "</td>";
    $cardStatsTable .= "</tr>";
    */
?>