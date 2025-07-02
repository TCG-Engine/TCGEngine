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
  include_once "../Assets/patreon-php-master/src/PatreonDictionary.php";
  include_once "../Assets/patreon-php-master/src/PatreonLibraries.php";

  $gameName = TryGet("gameName", "");

  ParseGamestate(__DIR__ . "/");

  echo '<link rel="icon" type="image/png" href="/TCGEngine/Assets/Images/blueDiamond.png">';
  echo("<body style='margin:0px; scroll: none;'>");
  $skipInitialize = true;
  $playerID = 1;//To fix php errors
  include_once './InitialLayout.php';

  echo("</body>");
?>

<script>
  var el = document.getElementById("myStuff");
  window.statsSource = "all"; // Default to showing all stats
<?php
    // Get the stats source from URL parameter, defaulting to 'all'
    $statsSource = isset($_GET["source"]) ? $_GET["source"] : "all";
    
    $conn = GetLocalMySQLConnection();
    
    // Query for deckstats table with source filter
    if ($statsSource === "all") {
      $stmt = $conn->prepare("SELECT SUM(numWins) as numWins, SUM(numPlays) as numPlays, 
        SUM(turnsInWins) as turnsInWins, SUM(totalTurns) as totalTurns, 
        SUM(cardsResourcedInWins) as cardsResourcedInWins, SUM(totalCardsResourced) as totalCardsResourced, 
        SUM(remainingHealthInWins) as remainingHealthInWins, SUM(winsGoingFirst) as winsGoingFirst, 
        SUM(winsGoingSecond) as winsGoingSecond, SUM(playsGoingFirst) as playsGoingFirst 
        FROM deckstats WHERE deckID = ?");
      $stmt->bind_param("i", $gameName);
    } else {
      $sourceVal = ($statsSource === "owner") ? 1 : 0;
      $stmt = $conn->prepare("SELECT SUM(numWins) as numWins, SUM(numPlays) as numPlays, 
        SUM(turnsInWins) as turnsInWins, SUM(totalTurns) as totalTurns, 
        SUM(cardsResourcedInWins) as cardsResourcedInWins, SUM(totalCardsResourced) as totalCardsResourced, 
        SUM(remainingHealthInWins) as remainingHealthInWins, SUM(winsGoingFirst) as winsGoingFirst, 
        SUM(winsGoingSecond) as winsGoingSecond, SUM(playsGoingFirst) as playsGoingFirst 
        FROM deckstats WHERE deckID = ? AND source = ?");
      $stmt->bind_param("ii", $gameName, $sourceVal);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $deckStats = $result->fetch_assoc();
    $hasDeckStats = $deckStats != null && $deckStats["numPlays"] > 0;

    $stmt->close();
    
    // Query for opponentdeckstats table with source filter
    if ($statsSource === "all") {
      $stmt = $conn->prepare("SELECT * FROM opponentdeckstats WHERE deckID = ?");
      $stmt->bind_param("i", $gameName);
    } else {
      $sourceVal = ($statsSource === "owner") ? 1 : 0;
      $stmt = $conn->prepare("SELECT * FROM opponentdeckstats WHERE deckID = ? AND source = ?");
      $stmt->bind_param("ii", $gameName, $sourceVal);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    // For matchup stats, we need to aggregate if looking at all sources
    if ($statsSource === "all") {
      // Close the previous query and create a new one that aggregates the data
      $stmt->close();
      $stmt = $conn->prepare("SELECT leaderID,
        SUM(winsVsGreen) as winsVsGreen, SUM(winsVsBlue) as winsVsBlue, 
        SUM(winsVsRed) as winsVsRed, SUM(winsVsYellow) as winsVsYellow,
        SUM(totalVsGreen) as totalVsGreen, SUM(totalVsBlue) as totalVsBlue, 
        SUM(totalVsRed) as totalVsRed, SUM(totalVsYellow) as totalVsYellow
        FROM opponentdeckstats WHERE deckID = ? GROUP BY leaderID");
      $stmt->bind_param("i", $gameName);
      $stmt->execute();
      $result = $stmt->get_result();
    }
    
    $matchupStats = "<br><strong>Matchup Stats:</strong><br>";
    $matchupStats .= "<table class='statsTable'><tr><th rowspan='2'>Leader</th><th rowspan='2'>Wins</th><th rowspan='2'>Losses</th><th rowspan='2'>Win Rate</th><th colspan='2'>Green</th><th colspan='2'>Blue</th><th colspan='2'>Red</th><th colspan='2'>Yellow</th></tr>";
    $matchupStats .= "<tr><th>Win%</th><th>Played</th><th>Win%</th><th>Played</th><th>Win%</th><th>Played</th><th>Win%</th><th>Played</th></tr>";
    while ($row = $result->fetch_assoc()) {
      $totalPlays = $row["totalVsGreen"] + $row["totalVsBlue"] + $row["totalVsRed"] + $row["totalVsYellow"];
      $totalWins = $row["winsVsGreen"] + $row["winsVsBlue"] + $row["winsVsRed"] + $row["winsVsYellow"];
      $totalLosses = $totalPlays - $totalWins;
      $totalWinRate = ($totalPlays > 0) ? ($totalWins / $totalPlays) * 100 : 0;
      $winRateVsGreen = ($row["totalVsGreen"] > 0) ? ($row["winsVsGreen"] / $row["totalVsGreen"]) * 100 : 0;
      $winRateVsBlue = ($row["totalVsBlue"] > 0) ? ($row["winsVsBlue"] / $row["totalVsBlue"]) * 100 : 0;
      $winRateVsRed = ($row["totalVsRed"] > 0) ? ($row["winsVsRed"] / $row["totalVsRed"]) * 100 : 0;
      $winRateVsYellow = ($row["totalVsYellow"] > 0) ? ($row["winsVsYellow"] / $row["totalVsYellow"]) * 100 : 0;
      $matchupStats .= "<tr>";
      $matchupStats .= "<td>" . htmlspecialchars(CardTitle($row["leaderID"]) . ", " . CardSubtitle($row["leaderID"]), ENT_QUOTES, 'UTF-8') . "</td>";
      $matchupStats .= "<td>" . $totalWins . "</td>";
      $matchupStats .= "<td>" . $totalLosses . "</td>";
      $matchupStats .= "<td>" . number_format($totalWinRate, 2) . "%</td>";
      $matchupStats .= "<td>" . ($row["totalVsGreen"] > 0 ? number_format($winRateVsGreen, 2) . "%" : "-") . "</td>";
      $matchupStats .= "<td>" . $row["totalVsGreen"] . "</td>";
      $matchupStats .= "<td>" . ($row["totalVsBlue"] > 0 ? number_format($winRateVsBlue, 2) . "%" : "-") . "</td>";
      $matchupStats .= "<td>" . $row["totalVsBlue"] . "</td>";
      $matchupStats .= "<td>" . ($row["totalVsRed"] > 0 ? number_format($winRateVsRed, 2) . "%" : "-") . "</td>";
      $matchupStats .= "<td>" . $row["totalVsRed"] . "</td>";
      $matchupStats .= "<td>" . ($row["totalVsYellow"] > 0 ? number_format($winRateVsYellow, 2) . "%" : "-") . "</td>";
      $matchupStats .= "<td>" . $row["totalVsYellow"] . "</td>";
      $matchupStats .= "</tr>";
    }
   
    $matchupStats .= "</table>";

    $stmt->close();    $deckStatsOutput = "";
    if($deckStats != null) {
      $deckStatsOutput = "<div style='margin: 10px; max-height:calc(98vh - 150px); overflow-y:auto; scrollbar-width: thin; scrollbar-color: #5a5a5a #2a2a2a;'>";
      $deckStatsOutput .= "<table style='padding:10px; color: white; border-collapse: collapse; width: 100%;'><tr><td style='vertical-align: top; min-width: 300px;'>";
      //Leader and base image
      $leaders = &GetLeader(1);
      $bases = &GetBase(1);
      $deckStatsOutput .= "<div style='margin-bottom: 20px; text-align: center;'>";
      if(count($leaders) > 0) {
        $leaderID = $leaders[0]->CardID;
        $deckStatsOutput .= "<img src='./concat/" . $leaderID . ".webp' alt='" . CardTitle($leaderID) . "' style='max-width: 100px; margin-right: 20px;'>";
      }
      if(count($bases) > 0) {
        $baseID = $bases[0]->CardID;
        $deckStatsOutput .= "<img src='./concat/" . $baseID . ".webp' alt='" . CardTitle($baseID) . "' style='max-width: 100px;'>";
      }
      $deckStatsOutput .= "</div>";
      //Number of wins stats
      $deckStatsOutput .= "<strong>Number of wins:</strong> " . $deckStats["numWins"] . "<br>";
      $deckStatsOutput .= "<strong>Number of losses:</strong> " . ($deckStats["numPlays"] - $deckStats["numWins"]) . "<br>";
      $winRate = ($deckStats["numPlays"] > 0) ? ($deckStats["numWins"] / $deckStats["numPlays"]) * 100 : 0;
      $deckStatsOutput .= "<strong>Win rate:</strong> " . number_format($winRate, 2) . "%<br>";
      $deckStatsOutput .= "<br>";
      //Number of turns stats
      $averageTurnsInWins = ($deckStats["numWins"] > 0) ? ($deckStats["turnsInWins"] / $deckStats["numWins"]) : 0;
      $deckStatsOutput .= "<strong>Average turns in wins:</strong> " . number_format($averageTurnsInWins, 2) . "<br>";
      $numLosses = $deckStats["numPlays"] - $deckStats["numWins"];
      $turnsInLosses = $deckStats["totalTurns"] - $deckStats["turnsInWins"];
      $averageTurnsInLosses = ($numLosses > 0) ? ($turnsInLosses / $numLosses) : 0;
      $deckStatsOutput .= "<strong>Average turns in losses:</strong> " . number_format($averageTurnsInLosses, 2) . "<br>";
      $averageTurns = ($deckStats["numPlays"] > 0) ? ($deckStats["totalTurns"] / $deckStats["numPlays"]) : 0;
      $deckStatsOutput .= "<strong>Average turns:</strong> " . number_format($averageTurns, 2) . "<br>";
      //Cards resources stats
      $deckStatsOutput .= "<br>";
      $averageCardsResourcedInWins = ($deckStats["numWins"] > 0) ? ($deckStats["cardsResourcedInWins"] / $deckStats["numWins"]) : 0;
      $deckStatsOutput .= "<strong>Average cards resourced in wins:</strong> " . number_format($averageCardsResourcedInWins, 2) . "<br>";
      $cardsResourcedInLosses = $deckStats["totalCardsResourced"] - $deckStats["cardsResourcedInWins"];
      $averageCardsResourcedInLosses = ($numLosses > 0) ? ($cardsResourcedInLosses / $numLosses) : 0;
      $deckStatsOutput .= "<strong>Average cards resourced in losses:</strong> " . number_format($averageCardsResourcedInLosses, 2) . "<br>";
      $averageCardsResourced = ($deckStats["numPlays"] > 0) ? ($deckStats["totalCardsResourced"] / $deckStats["numPlays"]) : 0;
      $deckStatsOutput .= "<strong>Average cards resourced:</strong> " . number_format($averageCardsResourced, 2) . "<br>";
      //Damage stats
      $deckStatsOutput .= "<br>";
      $averageBaseDamageInWins = ($deckStats["numWins"] > 0) ? ($deckStats["remainingHealthInWins"] / $deckStats["numWins"]) : 0;
      $deckStatsOutput .= "<strong>Average base damage in wins:</strong> " . number_format($averageBaseDamageInWins, 2) . "<br>";
      //First player stats
      $deckStatsOutput .= "<br>";
      $winsGoingFirstPercentage = ($deckStats["numWins"] > 0) ? ($deckStats["winsGoingFirst"] / $deckStats["numWins"]) * 100 : 0;
      $winsGoingSecondPercentage = ($deckStats["numWins"] > 0) ? ($deckStats["winsGoingSecond"] / $deckStats["numWins"]) * 100 : 0;
      $deckStatsOutput .= "<strong>Wins going first:</strong> " . number_format($winsGoingFirstPercentage, 2) . "%<br>";
      $deckStatsOutput .= "<strong>Wins going second:</strong> " . number_format($winsGoingSecondPercentage, 2) . "%<br>";
      $deckStatsOutput .= "<br>";
      $winRateGoingFirst = ($deckStats["numPlays"] > 0 && $deckStats["playsGoingFirst"] > 0) ? ($deckStats["winsGoingFirst"] / $deckStats["playsGoingFirst"]) * 100 : 0;
      $winRateGoingSecond = ($deckStats["numPlays"] > 0 && ($deckStats["numPlays"] - $deckStats["playsGoingFirst"]) > 0) ? ($deckStats["winsGoingSecond"] / ($deckStats["numPlays"] - $deckStats["playsGoingFirst"])) * 100 : 0;      $deckStatsOutput .= "<strong>Win rate going first:</strong> " . number_format($winRateGoingFirst, 2) . "%<br>";      $deckStatsOutput .= "<strong>Win rate going second:</strong> " . number_format($winRateGoingSecond, 2) . "%<br>";
      $deckStatsOutput .= "<br><div style='display:flex; gap:10px;'>";
      $deckStatsOutput .= "<button onclick='clearStats()'>Clear Stats</button>";      $deckStatsOutput .= "<button onclick='showAddStatsForm()'>Add Stats</button>";
      $deckStatsOutput .= "</div>";
      $deckStatsOutput .= "</td><td style='width: 70%;'><div>";
    }    $allLeaders = &GetLeaders(1);
    $allMainDeck = &GetMainDeck(1);
    $allSideBoard = &GetSideBoard(1);
    //Card stats
    if ($statsSource === "all") {
      $stmt = $conn->prepare("SELECT cardID, 
        SUM(timesIncluded) as timesIncluded, 
        SUM(timesIncludedInWins) as timesIncludedInWins, 
        SUM(timesDrawn) as timesDrawn, 
        SUM(timesDrawnInWins) as timesDrawnInWins, 
        SUM(timesPlayed) as timesPlayed, 
        SUM(timesPlayedInWins) as timesPlayedInWins, 
        SUM(timesResourced) as timesResourced, 
        SUM(timesResourcedInWins) as timesResourcedInWins, 
        SUM(timesDiscarded) as timesDiscarded, 
        SUM(timesDiscardedInWins) as timesDiscardedInWins 
        FROM carddeckstats WHERE deckID = ? GROUP BY cardID");
      $stmt->bind_param("i", $gameName);
    } else {
      $sourceVal = ($statsSource === "owner") ? 1 : 0;
      $stmt = $conn->prepare("SELECT cardID, 
        SUM(timesIncluded) as timesIncluded, 
        SUM(timesIncludedInWins) as timesIncludedInWins, 
        SUM(timesDrawn) as timesDrawn, 
        SUM(timesDrawnInWins) as timesDrawnInWins, 
        SUM(timesPlayed) as timesPlayed, 
        SUM(timesPlayedInWins) as timesPlayedInWins, 
        SUM(timesResourced) as timesResourced, 
        SUM(timesResourcedInWins) as timesResourcedInWins, 
        SUM(timesDiscarded) as timesDiscarded, 
        SUM(timesDiscardedInWins) as timesDiscardedInWins 
        FROM carddeckstats WHERE deckID = ? AND source = ? GROUP BY cardID");
      $stmt->bind_param("ii", $gameName, $sourceVal);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Create arrays to store main deck and sideboard card stats
    $mainDeckCardStats = [];
    $sideboardCardStats = [];
    
    // Get all card IDs in main deck
    $mainDeckCardIDs = [];
    foreach ($allMainDeck as $card) {
      $mainDeckCardIDs[] = $card->CardID;
    }
    
    // Separate card stats into main deck and sideboard
    while ($row = $result->fetch_assoc()) {
      if (in_array($row["cardID"], $mainDeckCardIDs)) {
        $mainDeckCardStats[] = $row;
      } else {
        $sideboardCardStats[] = $row;
      }
    }
    
    // Table header
    $tableHeader = "<tr><th>Card Name</th><th>Times Included</th><th>Times Included In Wins</th><th>Times Drawn</th><th>Times Drawn In Wins</th><th>Times Played</th><th>Times Played In Wins</th><th>Times Resourced</th><th>Times Resourced In Wins</th><th>Times Discarded</th><th>Times Discarded In Wins</th></tr>";
    
    // Main deck stats table
    $cardStatsTable = "<br><strong>Main Deck Stats:</strong><br>";
    $cardStatsTable .= "<table class='statsTable'>" . $tableHeader;
    
    foreach ($mainDeckCardStats as $row) {
      $cardStatsTable .= "<tr>";
      $cardStatsTable .= "<td>" . htmlspecialchars(CardTitle($row["cardID"]), ENT_QUOTES, 'UTF-8') . " (" . CardSet($row["cardID"]) . ")</td>";
      $cardStatsTable .= "<td>" . $row["timesIncluded"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesIncludedInWins"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesDrawn"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesDrawnInWins"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesPlayed"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesPlayedInWins"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesResourced"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesResourcedInWins"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesDiscarded"] . "</td>";
      $cardStatsTable .= "<td>" . $row["timesDiscardedInWins"] . "</td>";
      $cardStatsTable .= "</tr>";
    }
    
    $cardStatsTable .= "</table>";
    
    // Sideboard stats table (only if there are sideboard cards with stats)
    if (!empty($sideboardCardStats)) {
      $cardStatsTable .= "<br><strong>Sideboard Stats:</strong><br>";
      $cardStatsTable .= "<table class='statsTable'>" . $tableHeader;
      
      foreach ($sideboardCardStats as $row) {
        $cardStatsTable .= "<tr>";
        $cardStatsTable .= "<td>" . htmlspecialchars(CardTitle($row["cardID"]), ENT_QUOTES, 'UTF-8') . " (" . CardSet($row["cardID"]) . ")</td>";
        $cardStatsTable .= "<td>" . $row["timesIncluded"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesIncludedInWins"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesDrawn"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesDrawnInWins"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesPlayed"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesPlayedInWins"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesResourced"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesResourcedInWins"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesDiscarded"] . "</td>";
        $cardStatsTable .= "<td>" . $row["timesDiscardedInWins"] . "</td>";
        $cardStatsTable .= "</tr>";
      }
      
      $cardStatsTable .= "</table>";
    }
    $deckStatsOutput .= $cardStatsTable . $matchupStats . "</div>";

    $stmt->close();
    $conn->close();

    $deckStatsOutput .= "</div></td></tr></table>";    // Create the source selection UI with styled toggle buttons
    $sourceSelector = '<div class=\"stats-source-selector\">';
    $sourceSelector .= '<div class=\"selector-buttons\">';
    $sourceSelector .= '<div class=\"selector-btn ' . ($statsSource === "all" ? "active" : "") . '\" onclick=\"changeStatsSource(\'all\')\" title=\"Combined statistics from both owner and community\">';
    $sourceSelector .= '<i class=\"selector-icon all-icon\">&#9733;</i>All Stats</div>';
    
    $sourceSelector .= '<div class=\"selector-btn ' . ($statsSource === "owner" ? "active" : "") . '\" onclick=\"changeStatsSource(\'owner\')\" title=\"Statistics submitted by the deck owner only\">';
    $sourceSelector .= '<i class=\"selector-icon owner-icon\">&#9787;</i>Owner Stats</div>';
    
    $sourceSelector .= '<div class=\"selector-btn ' . ($statsSource === "community" ? "active" : "") . '\" onclick=\"changeStatsSource(\'community\')\" title=\"Statistics submitted by the community excluding the owner\">';
    $sourceSelector .= '<i class=\"selector-icon community-icon\">&#9734;</i>Community Stats</div>';
    
    $sourceSelector .= '</div>';
    $sourceSelector .= '</div>';
      if(!$hasDeckStats) {
      echo "var deckStats = \"<div style='margin: 10px;'>";
      echo $sourceSelector;
      echo "<strong>No stats available for this deck with the selected filter. </strong></div>\";";
    }
    else {
      echo "var deckStats = \"";
      echo $sourceSelector;
      echo $deckStatsOutput;
      echo "\";";
    }
        echo "deckStats += \"<div id='addStatsForm' style='display:none; max-height: 80%; overflow-y: auto;'>\";";
      echo "deckStats += \"<form><label for='statType' style='display: inline-block; width: 110px; margin-right: 10px;'>Leaders:</label><select id='leader'>\";";
      foreach ($allLeaders as $leader) {
        $leaderID = $leader->CardID;
        $leaderName = htmlspecialchars(CardTitle($leaderID), ENT_QUOTES, 'UTF-8');
        echo "deckStats += \"<option value='$leaderID'>$leaderName</option>\";";
      }
      echo "deckStats += \"</select><br><br>\";";
      echo "deckStats += \"<label for='BaseColor' style='display: inline-block; width: 110px; margin-right: 10px;'>Base Color:</label><select id='baseColor'><option value='Green'>Green</option><option value='Blue'>Blue</option><option value='Red'>Red</option><option value='Yellow'>Yellow</option></select><br><br>\";";
      echo "deckStats += \"<input type='radio' id='win' name='statType' value='win'><label for='win' style='margin-right: 10px;'>Win</label>\";";
      echo "deckStats += \"<input type='radio' id='loss' name='statType' value='loss'><label for='loss'>Loss</label><br><br>\";";
      echo "deckStats += \"<input type='radio' id='firstPlayer' name='playerType' value='firstPlayer'><label for='firstPlayer'  style='margin-right: 10px;'>First Player</label>\";";
      echo "deckStats += \"<input type='radio' id='secondPlayer' name='playerType' value='secondPlayer'><label for='secondPlayer'>Second Player</label><br><br>\";";
      echo "deckStats += \"<label style='display: inline-block; width: 110px; margin-right: 10px;'>Rounds:</label>\";";
      echo "deckStats += \"<input type='number' id='rounds' name='rounds' value='0' min='0' max='100' oninput='validateNumberInput(this)' onkeypress='handleKeyPress(event)'><br><br>\";";
      echo "deckStats += \"<label style='display: inline-block; width: 110px; margin-right: 10px;'>Winner Health:</label>\";";
      echo "deckStats += \"<input type='number' id='winnerHealth' name='winnerHealth' value='0' placeholder='0' min='0' max='100' oninput='validateNumberInput(this)' onkeypress='handleKeyPress(event)'><br><br>\";";
      echo "deckStats += \"<label style='display: inline-block; width: 200px;'> </label>\";";
      echo "deckStats += \"<label style='display: inline-block; width: 100px;margin-right: 10px;'>Played</label>\";";
      echo "deckStats += \"<label style='display: inline-block; width: 100px;'>Resourced</label><br>\";";
      $uniqueMainDeckCards = [];
      foreach ($allMainDeck as $card) {
        if (!in_array($card->CardID, $uniqueMainDeckCards)) {
          $uniqueMainDeckCards[] = $card->CardID;
          $cardID = $card->CardID;
          $cardName = htmlspecialchars(CardTitle($cardID), ENT_QUOTES, 'UTF-8');
          echo "deckStats += \"<label style='display: inline-block; width: 200px;'>$cardName</label>\";";
          echo "deckStats += \"<input type='number' id='played_$cardID' name='played_$cardID' placeholder='0' value='0' style='display: inline-block; width: 100px; margin-right: 10px;' min='0' max='100' oninput='validateNumberInput(this)' onkeypress='handleKeyPress(event)'>\";";
          echo "deckStats += \"<input type='number' id='resourced_$cardID' name='resourced_$cardID' placeholder='0' value='0' style='display: inline-block; width: 100px;' min='0' max='100' oninput='validateNumberInput(this)' onkeypress='handleKeyPress(event)'><br>\";";
        }
      }
      $uniqueSideBoardCards = [];
      foreach ($allSideBoard as $card) {
        if (!in_array($card->CardID, $uniqueSideBoardCards) && (!in_array($card->CardID, $uniqueMainDeckCards))) {
          $uniqueSideBoardCards[] = $card->CardID;
          $cardID = $card->CardID;
          $cardName = htmlspecialchars(CardTitle($cardID), ENT_QUOTES, 'UTF-8');
          echo "deckStats += \"<label style='display: inline-block; width: 200px;'>$cardName</label>\";";
          echo "deckStats += \"<input type='number' id='played_$cardID' name='played_$cardID' placeholder='0' value='0' style='display: inline-block; width: 100px; margin-right: 10px;' min='0' max='100' oninput='validateNumberInput(this)' onkeypress='handleKeyPress(event)'>\";";
          echo "deckStats += \"<input type='number' id='resourced_$cardID' name='resourced_$cardID' placeholder='0' value='0' style='display: inline-block; width: 100px;' min='0' max='100' oninput='validateNumberInput(this)' onkeypress='handleKeyPress(event)'><br>\";";
        }
      }
      echo "deckStats += \"<br><button type='button' onclick='confirmAddStats()' style='margin-right: 10px;'>Add</button><button type='button' onclick='cancelAddStats()'>Cancel</button>\";";
      echo "deckStats += \"</form></div>\";";

    ?>
    el.innerHTML = deckStats;

    function showAddStatsForm() {
      var form = document.getElementById('addStatsForm');
      form.style.position = 'fixed';
      form.style.top = '50%';
      form.style.left = '50%';
      form.style.transform = 'translate(-50%, -50%)';
      form.style.backgroundColor = '#2a2a2a';
      form.style.padding = '20px';
      form.style.border = '2px solid #5a5a5a';
      form.style.borderRadius = '8px';
      document.getElementById('addStatsForm').style.display = 'block';
      document.body.style.pointerEvents = 'none';
      form.style.pointerEvents = 'auto';
      clearStatsButton.disabled = true;
    }

    function validateNumberInput(input) {
      if (input.value !== "0") {  // to prevent users enter something like "012"
          input.value = input.value.replace(/^0+/, '');
      }
     if (input.value < 0) {
        input.value = 0;
      }
      if (input.value > 100) {
        input.value = 100;
      }
    }

    function handleKeyPress(event) {  // to prevent users enter "e" or "E"
      if (event.key === 'e' || event.key === 'E') {
        event.preventDefault();
      }
    }

    function confirmAddStats() {
      var winRadio = document.getElementById('win');
      var lossRadio = document.getElementById('loss');
      if (!winRadio.checked && !lossRadio.checked) {
        alert('Please select either Win or Loss.');
        return;
      }
      var firstPlayerRadio = document.getElementById('firstPlayer');
      var secondPlayerRadio = document.getElementById('secondPlayer');
      if (!firstPlayerRadio.checked && !secondPlayerRadio.checked) {
        alert('Please select either First Player or Second Player.');
        return;
      }
      var leader = document.getElementById('leader').value;
      var baseColor = document.getElementById('baseColor').value;
      var statType = winRadio.checked ? 'win' : 'loss';
      var playerType = firstPlayerRadio.checked ? 'firstPlayer' : 'secondPlayer';
      var rounds = document.getElementById('rounds').value;
      var winnerHealth = document.getElementById('winnerHealth').value;
      
      var mainDeckStats = {};
      var sideBoardStats = {};

      <?php foreach ($allMainDeck as $card): ?>
        mainDeckStats['<?php echo $card->CardID; ?>'] = {
          played: document.getElementById('played_<?php echo $card->CardID; ?>').value,
          resourced: document.getElementById('resourced_<?php echo $card->CardID; ?>').value
        };
      <?php endforeach; ?>

      <?php foreach ($allSideBoard as $card):
        if (in_array($card->CardID, $uniqueMainDeckCards)) {
          continue;
        } 
      ?>
        sideBoardStats['<?php echo $card->CardID; ?>'] = {
          played: document.getElementById('played_<?php echo $card->CardID; ?>').value,
          resourced: document.getElementById('resourced_<?php echo $card->CardID; ?>').value
        };
      <?php endforeach; ?>      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/TCGEngine/APIs/SubmitManualGameResult.php', true);
      xhr.setRequestHeader('Content-Type', 'application/json');        xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
          // Reload the page with the current source filter
          window.location.href = window.location.pathname + "?gameName=<?php echo($gameName); ?>&source=" + window.statsSource;
        }
      };

      var data = {
        deckID: <?php echo $gameName; ?>,
        won: statType == 'win',
        rounds: rounds,
        winnerHealth: winnerHealth,
        firstPlayer: playerType == 'firstPlayer',

        player: JSON.stringify({
          opposingHero: leader,
          opposingBaseColor: baseColor,
          cardResults: Object.keys(mainDeckStats).map(function(cardID) {
              return {
                cardID: cardID,
                resourced: mainDeckStats[cardID].resourced,
                played: mainDeckStats[cardID].played
              };
            }).concat(Object.keys(sideBoardStats).filter(function(cardID) {
              return !mainDeckStats.hasOwnProperty(cardID);
            }).map(function(cardID) {
              return {
                cardID: cardID,
                resourced: sideBoardStats[cardID].resourced,
                played: sideBoardStats[cardID].played
              };
            }))
        }),
      };
      console.log(JSON.stringify(data));
      xhr.send(JSON.stringify(data));
      document.body.style.pointerEvents = 'auto';
      form.style.pointerEvents = 'none';
      clearStatsButton.disabled = false;
    }

    function cancelAddStats()  {
      document.getElementById('addStatsForm').style.display = 'none';
      document.body.style.pointerEvents = 'auto';
      form.style.pointerEvents = 'none';
      clearStatsButton.disabled = false;
    }    function clearStats() {
      if (confirm('Are you sure you want to clear your stats? This is unreversable.')) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/TCGEngine/SWUDeck/ClearStats.php?deckID=' + <?php echo($gameName); ?>, true);
        xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
          // Reload the page with the current source filter
          window.location.href = window.location.pathname + "?gameName=<?php echo($gameName); ?>&source=" + window.statsSource;
        }
        };
        xhr.send();
      }
    }    function changeStatsSource(source) {
      // Update active state visually before page reload
      document.querySelectorAll('.selector-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      // Find the clicked button and add active class
      document.querySelector('.selector-btn[onclick*="' + source + '"]').classList.add('active');
      
      // Add a subtle fade effect before loading new data
      document.querySelector('.myStuff').style.opacity = '0.5';
      document.querySelector('.myStuff').style.transition = 'opacity 0.3s';
      
      // Update source and redirect after short delay for animation
      window.statsSource = source;
      setTimeout(function() {
        window.location.href = window.location.pathname + "?gameName=<?php echo($gameName); ?>&source=" + source;
      }, 300);
    }

  </script>

<style>
  .stuffParent {
    position:relative;
    top: 0px;
    bottom: 0px;
    left: 0px;
    right: 0px;
    width: 100%;
    height: 100%;
  }

  .stuff {
    position: absolute;
    top: 4px;
    bottom: 4px;
    left: 4px;
    right: 4px;
  }

  .myStuff {
    background-color: #3a3a3a; /* Medium grey */
    border: 2px solid #5a5a5a; /* Light grey */
    border-radius: 8px;
    font-family: 'Roboto', sans-serif; /* Modern font */
    color: #ffffff; /* White */
  }

  .myStuffWrapper {
    background-color: #2a2a2a; /* Dark grey */
  }
  .statsTable {
    padding: 10px;
    color: white;
    text-align: center;
    border: 1px solid #2a2a2a;
    border-collapse: collapse;
    width: 100%;
    table-layout: auto;
  }

  .statsTable th, .statsTable td {
    border: 1px solid #2a2a2a;
    padding: 5px;
  }
  
  /* Ensure table cells don't get too narrow */
  .statsTable th:first-child, .statsTable td:first-child {
    min-width: 120px;
  }

  .statsTable th {
    background-color: #3a3a3a;
  }

  .statsTable td {
    background-color: #2a2a2a;
  }
  .statsTable tr:nth-child(even) td {
    background-color: #3a3a3a;
  }
  
  /* Stats Source Selector Styling */
  .stats-source-selector {
    background-color: #2a2a2a;
    border-radius: 10px;
    padding: 15px;
    margin: 0 0 20px 0;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 10px;
    z-index: 100;
    border: 1px solid #444;
  }
  
  .selector-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    padding-top:10px;
  }
  
  .selector-btn {
    background-color: #3a3a3a;
    border: 1px solid #5a5a5a;
    color: #cccccc;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    flex: 1;
    justify-content: center;
    max-width: 150px;
    font-size: 14px;
  }
  
  .selector-btn:hover {
    background-color: #4a4a4a;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }
  
  .selector-btn.active {
    background-color: #5a76a0;
    color: white;
    border-color: #7a96c0;
    box-shadow: 0 2px 8px rgba(90, 118, 160, 0.4);
  }
  
  .selector-icon {
    margin-right: 6px;
    font-style: normal;
  }
  
  .all-icon {
    color: #ffcc33;
  }
  
  .owner-icon {
    color: #66cc66;
  }
  
  .community-icon {
    color: #6699ff;
  }
  
  @media (max-width: 768px) {
    .selector-buttons {
      flex-direction: column;
      align-items: center;
    }
    
    .selector-btn {
      width: 100%;
      max-width: 200px;
      margin-bottom: 5px;
    }
  }
</style>