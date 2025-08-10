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
$query = "SELECT leaderID, baseID, week, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond FROM deckmetastats WHERE week = 0";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
  echo "<table id='deckMetaStatsTable' border='1' cellspacing='0' cellpadding='5'>";
  echo "<thead><tr>";
  echo("<th>Deck Search</th>");
  echo "<th>Leader ID</th>";
  echo "<th>Base ID</th>";
  echo "<th>Num Plays</th>";
  echo "<th>Win Rate</th>";
  //echo "<th>Plays Going First</th>";
  echo "<th>Avg. Turns in Wins</th>";
  echo "<th>Avg. Turns in Losses</th>";
  echo "<th>Avg. Cards Resourced in Wins</th>";
  //echo "<th>Total Cards Resourced</th>";
  echo "<th>Avg. Remaining Health in Wins</th>";
  echo "</tr></thead>";
  echo "<tbody>";
  while ($row = $result->fetch_assoc()) {
    $leaderID = htmlspecialchars($row['leaderID']);
    $baseID = htmlspecialchars($row['baseID']);
    if($leaderID === "" || $baseID === "") {
      continue; // Skip rows with invalid leader or base IDs
    }
    if (intval($leaderID) === -1 || intval($baseID) === -1 || $row['numPlays'] == 0) {
      continue;
    }
    echo "<tr>";
    // Drilldown button
    echo '<td style="white-space:nowrap;display:flex;gap:6px;align-items:center;">'
        . '<a href="https://swustats.net/TCGEngine/Stats/Decks.php?leader=' . $leaderID . '&base=' . $baseID . '">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
        </svg>
        </a>'
        . '<button class="drilldown-btn" data-leader="' . $leaderID . '" data-base="' . $baseID . '" title="Show Matchup Breakout" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:2px 8px;cursor:pointer;">Matchups</button>'
        . '</td>';
    echo("<td><img src='../SWUDeck/concat/" . $leaderID . ".webp' style='height: 80px;' title='" . CardTitle($leaderID) . "' /></td>");
    echo("<td><img src='../SWUDeck/concat/" . $baseID . ".webp' style='height: 80px;' title='" . CardTitle($baseID) . "' /></td>");
    echo "<td>" . $row['numPlays'] . "</td>";
    echo "<td>" . number_format($row['numWins'] / $row['numPlays'] * 100, 2) . "%</td>";
    //echo "<td>" . $row['playsGoingFirst'] . "</td>";
    echo "<td>" . ($row['numWins'] ? number_format($row['turnsInWins'] / $row['numWins'], 2) : "N/A") . "</td>";
    echo "<td>" . (($row['numPlays'] - $row['numWins']) ? number_format(($row['totalTurns'] - $row['turnsInWins']) / ($row['numPlays'] - $row['numWins']), 2) : "N/A") . "</td>";
    echo "<td>" . ($row['numWins'] ? number_format($row['cardsResourcedInWins'] / $row['numWins'], 2) : "N/A") . "</td>";
    //echo "<td>" . $row['totalCardsResourced'] . "</td>";
    echo "<td>" . ($row['numWins'] ? number_format($row['remainingHealthInWins'] / $row['numWins'], 2) : "N/A") . "</td>";
    echo "</tr>";
  }
  echo "</tbody>";
  echo "</table>";
} else {
  echo "<p>No deck meta stats found for week 0.</p>";
}

$conn->close();

include_once '../SharedUI/Disclaimer.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- Popup Modal for Matchup Drilldown -->
<div id="matchupModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(10,16,32,0.85);align-items:center;justify-content:center;">
  <div id="matchupModalContent" style="background:#101a2b;color:#fff;padding:24px 18px 18px 18px;border-radius:12px;max-width:98vw;max-height:90vh;overflow:auto;box-shadow:0 0 32px #0074D9;position:relative;">
    <button id="closeMatchupModal" style="position:absolute;top:8px;right:12px;background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:2px 10px;cursor:pointer;font-size:18px;">&times;</button>
    <div id="matchupModalBody">Loading...</div>
  </div>
</div>

<script>
$(function() {
  // Drilldown button click handler
  $(document).on('click', '.drilldown-btn', function() {
    var leaderID = $(this).data('leader');
    var baseID = $(this).data('base');
    $('#matchupModal').show();
    $('#matchupModalBody').html('Loading...');
    // AJAX to fetch matchup data
    $.get('../APIs/DeckMetaMatchupStatsAPI.php', { leaderID: leaderID, baseID: baseID }, function(data) {
      try {
        var json = typeof data === 'string' ? JSON.parse(data) : data;
        if (!Array.isArray(json) || json.length === 0) {
          $('#matchupModalBody').html('<p>No matchup data found for this deck.</p>');
          return;
        }
        var html = '<table border="1" cellpadding="4" style="width:100%;background:#0d0d1a;color:#7FDBFF;">';
        html += '<thead><tr>'
          + '<th>Opponent Leader</th>'
          + '<th>Opponent Base</th>'
          + '<th>Num Plays</th>'
          + '<th>Win Rate</th>'
          + '<th>Avg. Turns in Wins</th>'
          + '<th>Avg. Turns in Losses</th>'
          + '<th>Avg. Cards Resourced in Wins</th>'
          + '<th>Avg. Remaining Health in Wins</th>'
          + '</tr></thead><tbody>';
        for (var i=0; i<json.length; ++i) {
          var r = json[i];
          html += '<tr>'
            + '<td>' + (r.opponentLeaderID ? '<img src="../SWUDeck/concat/' + r.opponentLeaderID + '.webp" style="height:40px;vertical-align:middle;" title="' + (r.opponentLeaderID) + '" />' : '') + '</td>'
            + '<td>' + (r.opponentBaseID ? '<img src="../SWUDeck/concat/' + r.opponentBaseID + '.webp" style="height:40px;vertical-align:middle;" title="' + (r.opponentBaseID) + '" />' : '') + '</td>'
            + '<td>' + r.numPlays + '</td>'
            + '<td>' + (r.numPlays > 0 ? (parseInt(r.numWins)/parseInt(r.numPlays)*100).toFixed(2) + '%' : 'N/A') + '</td>'
            + '<td>' + (r.numWins > 0 ? (parseFloat(r.turnsInWins)/parseInt(r.numWins)).toFixed(2) : 'N/A') + '</td>'
            + '<td>' + ((r.numPlays - r.numWins) > 0 ? ((parseFloat(r.totalTurns)-parseFloat(r.turnsInWins))/(parseInt(r.numPlays)-parseInt(r.numWins))).toFixed(2) : 'N/A') + '</td>'
            + '<td>' + (r.numWins > 0 ? (parseFloat(r.cardsResourcedInWins)/parseInt(r.numWins)).toFixed(2) : 'N/A') + '</td>'
            + '<td>' + (r.numWins > 0 ? (parseFloat(r.remainingHealthInWins)/parseInt(r.numWins)).toFixed(2) : 'N/A') + '</td>'
            + '</tr>';
        }
        html += '</tbody></table>';
        $('#matchupModalBody').html(html);
      } catch(e) {
        $('#matchupModalBody').html('<p>Error loading matchup data.</p>');
      }
    });
  });
  // Close modal
  $('#closeMatchupModal, #matchupModal').on('click', function(e) {
    if (e.target === this || e.target.id === 'closeMatchupModal') {
      $('#matchupModal').hide();
    }
  });
});
</script>

<style>
/* Sci-Fi themed scrollbar styling for the DataTable's scrollable body (Webkit-based browsers) */
.dataTables_scrollBody::-webkit-scrollbar {
  width: 12px;
}

/* Remove scrollbar arrows */
.dataTables_scrollBody::-webkit-scrollbar-button {
  display: none;
}

.dataTables_scrollBody::-webkit-scrollbar-track {
  background: #0d0d1a; /* deep space background */
  box-shadow: inset 0 0 5px #000;
  border-radius: 8px; /* Ensure rounded edges */
  overflow: hidden; /* Prevents clipping */
}

.dataTables_scrollBody::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #001f3f, #0074D9); /* futuristic blue gradient */
  border-radius: 12px; /* increased border radius for rounded ends */
  border: 3px solid #0d0d1a; /* matches the deep space background */
  box-shadow: inset 0 0 8px #7FDBFF; /* bright neon effect */
}

.dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #0074D9, #001f3f);
}

/* Fallback scrollbar styling for Firefox */
.dataTables_scrollBody {
  scrollbar-width: thin;
  scrollbar-color: #0074D9 #0d0d1a;
}

</style>

<script>

  var tableHeight = $(window).height() - 280;
  $('#deckMetaStatsTable').DataTable({
      "order": [[ 3, "desc" ]],
      "scrollY": tableHeight + "px",
      "paging": false,
      "searching": false,
      "columnDefs": [
          { "type": "num", "targets": [4, 5, 6, 7, 8] },
          { "targets": 4, "render": function ( data, type, row ) {
                if (type === 'sort') {
                    return parseFloat(data.replace('%',''));
                }
                return data;
             }
          },
          { "targets": [5, 6, 7, 8], "render": function( data, type, row ) {
                if (type === 'sort'){
                  var num = parseFloat(data);
                  if(isNaN(num)) return -1;
                  return num;
                }
                return data;
             }
          }
      ]
  });

</script>

