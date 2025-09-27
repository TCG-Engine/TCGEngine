<?php
include_once '../SharedUI/MenuBar.php';
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/StatsHelpers.php';

$isMobile = IsMobile();

$forIndividual = false;
?>
<!-- jQuery and DataTables (required for this page) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<?php
  // Compute current week upper bound server-side and render dropdowns
  $currentWeek = GetWeekSinceRef();
?>
<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
  Start week: <select id="startWeek" style="width:120px;padding:4px;"></select>
  End week: <select id="endWeek" style="width:120px;padding:4px;"></select>
  <button id="refreshWeeks" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:6px 12px;cursor:pointer;">Refresh</button>
</div>

<script>
  // Populate week dropdowns with options 0..currentWeek and default to show latest (start=0, end=currentWeek)
  (function() {
    var currentWeek = <?php echo intval($currentWeek); ?>;
    var startSelect = document.getElementById('startWeek');
    var endSelect = document.getElementById('endWeek');
    for (var w = 0; w <= currentWeek; ++w) {
      var opt1 = document.createElement('option');
      opt1.value = w; opt1.text = w;
      var opt2 = document.createElement('option');
      opt2.value = w; opt2.text = w;
      startSelect.appendChild(opt1);
      endSelect.appendChild(opt2);
    }
    // Defaults: show all data up to current week
    startSelect.value = 0;
    endSelect.value = currentWeek;
  })();
</script>

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

<!-- Matchup modal (was missing) -->
<div id="matchupModal" style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
  <div id="matchupModalContent" style="background:#071029;color:#7FDBFF;padding:12px;border-radius:8px;max-width:900px;width:90%;max-height:85%;overflow:auto;box-shadow:0 8px 24px rgba(0,0,0,0.8);margin:48px auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
      <h3 style="margin:0;padding:0;color:#7FDBFF;">Matchup Breakout</h3>
      <button id="closeMatchupModal" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:6px 10px;cursor:pointer;">Close</button>
    </div>
    <div id="matchupModalBody"> </div>
  </div>
</div>

<script>
  // Ensure drilldown button doesn't shrink in flex cells
  $('<style> .drilldown-btn{flex:0 0 auto;} .drilldown-btn img{vertical-align:middle;} </style>').appendTo('head');

  // Drilldown button click handler (delegated)
  $(document).on('click', '.drilldown-btn', function() {
    var leaderID = $(this).data('leader');
    var baseID = $(this).data('base');
  // use flex layout when showing so centering works
  $('#matchupModal').css('display','flex');
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
                  'red': 'Aggression',
                  'Green': 'Command',
                  'green': 'Command',
                  'Blue': 'Vigilance',
                  'blue': 'Vigilance',
                  'Yellow': 'Cunning',
                  'yellow': 'Cunning'
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
  });

  // Close modal (bound once)
  $('#closeMatchupModal, #matchupModal').on('click', function(e) {
    if (e.target === this || e.target.id === 'closeMatchupModal') {
      $('#matchupModal').css('display','none');
    }
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
    var start = parseInt($('#startWeek').val(), 10);
    var end = parseInt($('#endWeek').val(), 10);
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
        rows += '<td style="white-space:nowrap;gap:6px;align-items:stretch;">'
          + '<a href="https://swustats.net/TCGEngine/Stats/Decks.php?leader=' + r.leaderID + '&base=' + r.baseID + '">' 
          + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">'
          + '<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>'
          + '</svg></a>'
          + '<button class="drilldown-btn" data-leader="' + r.leaderID + '" data-base="' + r.baseID + '" title="Show Matchup Breakout" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:4px 10px;cursor:pointer;height:100%;align-self:stretch;">Matchups</button>'
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
        // If a DataTable instance exists, destroy it first to avoid conflicts
        if (dataTable) {
          try { dataTable.destroy(); } catch (e) { /* ignore */ }
          dataTable = null;
        }

        // Ensure the tbody contains the new rows
        $('#deckMetaStatsTable tbody').empty().append(rows);

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
      } catch (e) {
        console.error('Error rendering deck meta table', e);
      }
    }).fail(function() {
      $('#deckMetaStatsBody').html('<tr><td colspan="9">Error fetching data from API.</td></tr>');
    });
  }

  // Wire refresh button
  $('#refreshWeeks').on('click', function() { fetchAndRender(); });

  // Initial load
  fetchAndRender();

</script>

