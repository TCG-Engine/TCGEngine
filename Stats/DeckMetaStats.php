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
<!-- Shared stats table styles -->
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/statsTables.css">

<?php
  // Compute current week upper bound server-side and render dropdowns
  $currentWeek = GetWeekSinceRef();
?>
<div class="week-controls">
  <div class="week-control">
    <label for="startWeek">Start week</label>
    <div class="select-wrap"><select id="startWeek" class="week-select"></select></div>
  </div>
  <div class="week-control">
    <label for="endWeek">End week</label>
    <div class="select-wrap"><select id="endWeek" class="week-select"></select></div>
  </div>
  <button id="refreshWeeks" class="week-refresh">Refresh</button>
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

<table id="deckMetaStatsTable" class="stats-table" cellspacing="0" cellpadding="5">
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

<!-- Sci-fi themed table styles -->
<style>
  /* Table overall */
  #deckMetaStatsTable {
    border-collapse: separate; /* allow neon separators */
    border-spacing: 0 6px; /* vertical spacing between rows */
    width: 100%;
    background: transparent;
    color: #BFDFFF;
    font-family: 'Montserrat', system-ui, Arial, sans-serif;
  }

  /* Remove any default table borders/attributes that show as white lines */
  #deckMetaStatsTable, #deckMetaStatsTable th, #deckMetaStatsTable td {
    border: none !important;
  }

  /* Header */
  /* Target both original table header and DataTables' cloned/scrolling header */
  #deckMetaStatsTable thead th,
  table.dataTable thead th,
  .dataTables_scrollHead table thead th {
    background: linear-gradient(180deg,#071029 0%, #08142a 100%);
    color: #7FDBFF;
    padding: 10px 12px;
    border-bottom: 1px solid rgba(127,200,255,0.08);
    text-align: left;
    font-weight: 600;
    letter-spacing: 0.6px;
    position: relative; /* for sort indicator */
    padding-right: 30px; /* room for sort icon */
  }

  /* Rows: card-like panels with subtle inner background */
  #deckMetaStatsTable tbody tr {
    background: linear-gradient(180deg, rgba(9,12,22,0.6), rgba(6,8,15,0.6));
    box-shadow: 0 2px 8px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.02);
    border-radius: 6px;
  }

  /* Cells inside rows should appear as separate columns (remove default cell borders) */
  #deckMetaStatsTable tbody td {
    border: none;
    padding: 10px 12px;
    vertical-align: middle;
  }

  /* Hover highlight */
  #deckMetaStatsTable tbody tr:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.7), inset 0 0 14px rgba(127,200,255,0.02);
    background: linear-gradient(180deg, rgba(12,20,36,0.75), rgba(8,12,22,0.75));
  }

  /* Subtle neon separators between columns using pseudo-element */
  #deckMetaStatsTable tbody td + td::before {
    content: '';
    position: absolute;
    width: 1px;
    height: 60%;
    background: linear-gradient(180deg, rgba(127,200,255,0.08), rgba(127,200,255,0.02));
    margin-left: -6px;
  }

  /* Make each cell positioned relative so pseudo-elements align */
  #deckMetaStatsTable tbody td { position: relative; }

  /* DataTables sort indicators (custom, to work with our themed header) */
  #deckMetaStatsTable thead th.sorting:after {
    content: '\25B4\25BE'; /* up + down */
    font-size: 10px;
    color: rgba(127,200,255,0.35);
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
  }
  #deckMetaStatsTable thead th.sorting_asc:after {
    content: '\25B4'; /* up */
    color: #7FDBFF;
  }
  #deckMetaStatsTable thead th.sorting_desc:after {
    content: '\25BE'; /* down */
    color: #7FDBFF;
  }
  #deckMetaStatsTable thead th { cursor: pointer; }

  /* Smaller screens: increase readability */
  @media (max-width:900px) {
    #deckMetaStatsTable thead th, #deckMetaStatsTable tbody td { padding: 8px; }
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
    "info": false,
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

