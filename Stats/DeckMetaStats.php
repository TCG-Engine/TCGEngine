<?php
include_once '../SharedUI/MenuBar.php';
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$isMobile = IsMobile();

$forIndividual = false;
?>
<!-- jQuery and DataTables (local CDN references) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
  Start week: <input type="number" id="startWeek" value="0" min="0" style="width:80px;padding:4px;" />
  End week: <input type="number" id="endWeek" value="0" min="0" style="width:80px;padding:4px;" />
  <button id="refreshWeeks" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:6px 12px;cursor:pointer;">Refresh</button>
</div>

<table id="deckMetaStatsTable" border="1" cellspacing="0" cellpadding="5">
  <thead>
    <tr>
      <th>Deck Search</th>
      <th>Leader ID</th>
      <th>Base ID</th>
      <th>Num Plays</th>
      <th>Win Rate</th>
      <th>Avg. Turns in Wins</th>
      <th>Avg. Turns in Losses</th>
      <th>Avg. Cards Resourced in Wins</th>
      <th>Avg. Remaining Health in Wins</th>
    </tr>
  </thead>
  <tbody id="deckMetaStatsBody"></tbody>
</table>

<script>
  // Drilldown button click handler (delegated)
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
            + '<td>' + (function() {
                if (!r.opponentBaseID) return '';
                var colorMap = {
                  'Red': 'Aggression',
                  'Green': 'Command',
                  'Blue': 'Vigilance',
                  'Yellow': 'Cunning'
                };
                if (colorMap[r.opponentBaseID]) {
                  return '<img src="../Assets/Images/icons/SWU/' + colorMap[r.opponentBaseID] + '.webp" style="height:40px;vertical-align:middle;" title="' + r.opponentBaseID + '" />';
                } else {
                  return '<img src="../SWUDeck/concat/' + r.opponentBaseID + '.webp" style="height:40px;vertical-align:middle;" title="' + r.opponentBaseID + '" />';
                }
              })() + '</td>'
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
  // Fetch and populate table via API
  var dataTable = null;
  function fetchAndRender() {
    var start = parseInt($('#startWeek').val());
    var end = parseInt($('#endWeek').val());
    var params = {};
    if (!isNaN(start)) params.startWeek = start;
    if (!isNaN(end)) params.endWeek = end;
    $('#deckMetaStatsBody').html('<tr><td colspan="9">Loading...</td></tr>');
    $.get('../Stats/DeckMetaStatsAPI.php', params, function(data) {
      var json = typeof data === 'string' ? JSON.parse(data) : data;
      if (!Array.isArray(json) || json.length === 0) {
        $('#deckMetaStatsBody').html('<tr><td colspan="9">No records found for the selected week(s).</td></tr>');
        return;
      }
      var rows = '';
      for (var i=0; i<json.length; ++i) {
        var r = json[i];
        rows += '<tr>';
        rows += '<td style="white-space:nowrap;display:flex;gap:6px;align-items:center;">'
          + '<a href="https://swustats.net/TCGEngine/Stats/Decks.php?leader=' + r.leaderID + '&base=' + r.baseID + '">' 
          + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">'
          + '<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>'
          + '</svg></a>'
          + '<button class="drilldown-btn" data-leader="' + r.leaderID + '" data-base="' + r.baseID + '" title="Show Matchup Breakout" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:2px 8px;cursor:pointer;">Matchups</button>'
          + '</td>';
        rows += '<td><img src="../SWUDeck/concat/' + r.leaderID + '.webp" style="height: 80px;" title="' + r.leaderTitle + '" /></td>';
        rows += '<td><img src="../SWUDeck/concat/' + r.baseID + '.webp" style="height: 80px;" title="' + r.baseTitle + '" /></td>';
        rows += '<td>' + r.numPlays + '</td>';
        rows += '<td>' + (r.numPlays > 0 ? (parseFloat(r.winRate)).toFixed(2) + '%' : 'N/A') + '</td>';
        rows += '<td>' + (r.avgTurnsInWins !== null ? r.avgTurnsInWins : 'N/A') + '</td>';
        rows += '<td>' + (r.avgTurnsInLosses !== null ? r.avgTurnsInLosses : 'N/A') + '</td>';
        rows += '<td>' + (r.avgCardsResourcedInWins !== null ? r.avgCardsResourcedInWins : 'N/A') + '</td>';
        rows += '<td>' + (r.avgRemainingHealthInWins !== null ? r.avgRemainingHealthInWins : 'N/A') + '</td>';
        rows += '</tr>';
      }
      // Replace table body and (re)initialize DataTable in a robust way
      try {
        // Ensure the tbody contains the new rows
        $('#deckMetaStatsTable tbody').empty().append(rows);

        // If a DataTable instance exists, destroy it first to avoid conflicts
        if (dataTable) {
          try { dataTable.destroy(); } catch (e) { /* ignore */ }
          dataTable = null;
        }

        dataTable = $('#deckMetaStatsTable').DataTable({
        "order": [[ 3, "desc" ]],
        "scrollY": tableHeight + "px",
        "paging": false,
        "searching": false,
        "columnDefs": [
            { "type": "num", "targets": [3] },
            { "targets": 4, "render": function ( data, type, row ) {
                  if (type === 'sort') {
                      return parseFloat(data.replace('%',''));
                  }
                  return data;
               }
            }
        ]
      });
    }).fail(function() {
      $('#deckMetaStatsBody').html('<tr><td colspan="9">Error fetching data from API.</td></tr>');
    });
  }

  // Wire refresh button
  $('#refreshWeeks').on('click', function() { fetchAndRender(); });

  // Initial load
  fetchAndRender();

</script>

