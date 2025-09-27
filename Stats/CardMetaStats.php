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

// ...existing code...

// Client-side rendering: fetch from CardMetaStatsAPI.php and display with DataTables
?>
<!-- jQuery and DataTables (required for this page) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- Shared stats table styles -->
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/statsTables.css">
 
<?php $cardCurrentWeek = GetWeekSinceRef(); ?>
<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
  Start week: <select id="cardStartWeek" style="width:120px;padding:4px;"></select>
  End week: <select id="cardEndWeek" style="width:120px;padding:4px;"></select>
  <button id="cardRefreshWeeks" style="background:#222a44;color:#7FDBFF;border:none;border-radius:4px;padding:6px 12px;cursor:pointer;">Refresh</button>
</div>
<script>
  (function() {
    var currentWeek = <?php echo intval($cardCurrentWeek); ?>;
    var s = document.getElementById('cardStartWeek');
    var e = document.getElementById('cardEndWeek');
    for (var w = 0; w <= currentWeek; ++w) {
      var o1 = document.createElement('option'); o1.value = w; o1.text = w;
      var o2 = document.createElement('option'); o2.value = w; o2.text = w;
      s.appendChild(o1);
      e.appendChild(o2);
    }
    s.value = 0;
    e.value = currentWeek;
  })();
</script>

<table id="cardMetaStatsTable" class="stats-table" cellspacing="0" cellpadding="5" style="width:100%;">
  <thead>
    <tr>
      <th>Card</th>
      <th>Times Included</th>
      <th>Times Included In Wins</th>
      <th>% Included In Wins</th>
      <th>Times Played</th>
      <th>Times Played In Wins</th>
      <th>% Played In Wins</th>
      <th>Times Resourced</th>
      <th>Times Resourced In Wins</th>
      <th>% Resourced In Wins</th>
    </tr>
  </thead>
  <tbody id="cardMetaStatsBody"></tbody>
</table>

<script>
  var cardTable = null;
  function fetchCardMeta() {
    var start = parseInt(document.getElementById('cardStartWeek').value || '0', 10);
    var end = parseInt(document.getElementById('cardEndWeek').value || '0', 10);
    var params = {};
    if (!isNaN(start)) params.startWeek = start;
    if (!isNaN(end)) params.endWeek = end;
    document.getElementById('cardMetaStatsBody').innerHTML = '<tr><td colspan="10">Loading...</td></tr>';
    $.get('../Stats/CardMetaStatsAPI.php', params, function(data) {
      var json = typeof data === 'string' ? JSON.parse(data) : data;
      if (!Array.isArray(json) || json.length === 0) {
        document.getElementById('cardMetaStatsBody').innerHTML = '<tr><td colspan="10">No records found for the selected week(s).</td></tr>';
        return;
      }
      var rows = '';
      for (var i=0;i<json.length;++i) {
        var r = json[i];
        rows += '<tr>';
        rows += '<td>' + (r.cardName || r.cardUid) + '</td>';
        rows += '<td>' + (r.timesIncluded || 0) + '</td>';
        rows += '<td>' + (r.timesIncludedInWins || 0) + '</td>';
        rows += '<td>' + (r.percentIncludedInWins || '0.00') + '%</td>';
        rows += '<td>' + (r.timesPlayed || 0) + '</td>';
        rows += '<td>' + (r.timesPlayedInWins || 0) + '</td>';
        rows += '<td>' + (r.percentPlayedInWins || '0.00') + '%</td>';
        rows += '<td>' + (r.timesResourced || 0) + '</td>';
        rows += '<td>' + (r.timesResourcedInWins || 0) + '</td>';
        rows += '<td>' + (r.percentResourcedInWins || '0.00') + '%</td>';
        rows += '</tr>';
      }

      // Reinit DataTable: destroy, set tbody, init
      try {
        if (cardTable) { try { cardTable.destroy(); } catch(e){} cardTable = null; }
        $('#cardMetaStatsTable tbody').empty().append(rows);
        cardTable = $('#cardMetaStatsTable').DataTable({
          "order": [[1, 'desc']],
          "paging": false,
          "searching": false
        });
      } catch(e) {
        console.error('Error rendering card meta', e);
      }
    }).fail(function() {
      document.getElementById('cardMetaStatsBody').innerHTML = '<tr><td colspan="10">Error fetching data from API.</td></tr>';
    });
  }

  document.getElementById('cardRefreshWeeks').addEventListener('click', fetchCardMeta);
  // initial load
  fetchCardMeta();
</script>

<?php

// ...existing code...