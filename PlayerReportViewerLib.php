<?php
// PlayerReportViewerLib.php — rendering for zzPlayerReportViewer.php (moderator-only), kept pure so it can be tested
// with synthetic data. Styles and escaping are the bug-report viewer's (BugReportViewerLib.php), so the two mod pages
// look alike. Every value is escaped; whispers are shown in full — this page is the one place that is allowed.

require_once __DIR__ . '/BugReportViewerLib.php';

function _prvTime($unixOrIso): string {
    $t = is_numeric($unixOrIso) ? (int)$unixOrIso : (int)strtotime(strval($unixOrIso));
    return $t > 0 ? gmdate('Y-m-d H:i:s', $t) . ' UTC' : '';
}

function _prvFirstLine(string $s): string {
    $line = strtok(str_replace("\r", '', $s), "\n");
    return $line === false ? '' : $line;
}

// The list: open first, then newest (PlayerReportList's order is kept; this sorts again so the page never depends on it).
function PlayerReportViewerListHtml(array $rows): string {
    usort($rows, function ($a, $b) {
        $oa = ($a['status'] ?? '') === 'open' ? 0 : 1; $ob = ($b['status'] ?? '') === 'open' ? 0 : 1;
        if ($oa !== $ob) return $oa <=> $ob;
        return [(int)($b['createdAt'] ?? 0), (int)($b['number'] ?? 0)] <=> [(int)($a['createdAt'] ?? 0), (int)($a['number'] ?? 0)];
    });
    if (empty($rows)) return '<div class="brv-empty">No player reports yet.</div>';
    $out = '<div class="brv-count">' . count($rows) . ' report' . (count($rows) === 1 ? '' : 's') . '</div>'
         . '<div class="brv-table-wrap"><table class="brv-table"><thead><tr>'
         . '<th>#</th><th>When</th><th>Reporter → Reported</th><th>Game</th><th>Status</th><th>Follow-ups</th><th>Note</th>'
         . '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $id = 'R-' . (int)($r['number'] ?? 0);
        $status = strval($r['status'] ?? 'open');
        $out .= '<tr>'
              . '<td><a href="?report=' . _brvEsc($id) . '">' . _brvEsc($id) . '</a></td>'
              . '<td>' . _brvEsc(_prvTime($r['createdAt'] ?? 0)) . '</td>'
              . '<td>' . _brvEsc($r['reporterName'] ?? '') . ' → ' . _brvEsc($r['reportedName'] ?? '') . '</td>'
              . '<td>' . _brvEsc($r['gameName'] ?? '') . '</td>'
              . '<td><span class="brv-badge brv-badge-' . ($status === 'open' ? 'open' : 'resolved') . '">' . _brvEsc($status) . '</span></td>'
              . '<td>' . (int)($r['followups'] ?? 0) . '</td>'
              . '<td>' . _brvEsc(_prvFirstLine(strval($r['note'] ?? ''))) . '</td>'
              . '</tr>';
    }
    return $out . '</tbody></table></div>';
}

// One chat line. Seat 0 is a system line (left the game, sideboard countdown).
function _prvChatLine(array $row): string {
    $seat = (int)($row['playerID'] ?? 0);
    $who = $seat > 0 ? _brvEsc($row['playerLabel'] ?? '') . ' (P' . $seat . ')' : '<i>' . _brvEsc($row['playerLabel'] ?? 'system') . '</i>';
    $whisper = isset($row['to'])
        ? ' <span class="brv-badge brv-badge-open">whisper → ' . _brvEsc(implode(', ', array_map(fn($s) => 'P' . (int)$s, (array)$row['to']))) . '</span>'
        : '';
    return '<div class="prv-line"><span class="prv-meta">' . _brvEsc(_prvTime($row['time'] ?? 0)) . ' · ' . _brvEsc($row['src'] ?? '') . '</span> '
         . $who . $whisper . ': ' . _brvEsc($row['text'] ?? '') . '</div>';
}

// Every conversation's rows merged in time order.
function _prvMerged(array $byKey): array {
    $all = [];
    foreach ($byKey as $rows) foreach ((array)$rows as $row) if (is_array($row)) $all[] = $row;
    usort($all, fn($a, $b) => [(float)($a['ts'] ?? $a['time'] ?? 0), (int)($a['id'] ?? 0)] <=> [(float)($b['ts'] ?? $b['time'] ?? 0), (int)($b['id'] ?? 0)]);
    return $all;
}

// $loaded: PlayerReportLoad(); $after: PlayerReportChatAfter(); $matchGames: the reported match's game names.
// The transcript is the LATEST frozen copy (each follow-up's copy holds everything before it). Room chat from before
// the reported match's first line is collapsed; the match itself, and anything after the report, is shown open.
function PlayerReportViewerDetailHtml(array $loaded, array $after, array $matchGames): string {
    $j = $loaded['report'] ?? [];
    $id = 'R-' . (int)($j['number'] ?? 0);
    $status = strval($j['status'] ?? 'open');
    $field = fn($label, $value) => '<tr><th>' . _brvEsc($label) . '</th><td>' . $value . '</td></tr>';
    $out = '<p><a href="?">← All player reports</a></p><h2>' . _brvEsc($id) . '</h2><table class="brv-table prv-fields">'
         . $field('Filed', _brvEsc(_prvTime($j['created_at'] ?? '')))
         . $field('Reporter', _brvEsc($j['reporter_name'] ?? '') . ' (account ' . (int)($j['reporter_id'] ?? 0) . ', P' . (int)($j['reporter_seat'] ?? 0) . ')')
         . $field('Reported', _brvEsc($j['reported_name'] ?? '') . ' (account ' . (int)($j['reported_id'] ?? 0) . ', P' . (int)($j['reported_seat'] ?? 0) . ')')
         . $field('Game', _brvEsc($j['game_name'] ?? ''))
         . $field('Match', _brvEsc($j['match_id'] ?? '') . (($j['lobby_id'] ?? '') !== '' ? ' · room ' . _brvEsc($j['lobby_id']) : ''))
         . $field('Format', _brvEsc($j['format'] ?? ''))
         . $field('Note', nl2br(_brvEsc($j['note'] ?? '')))
         . $field('Status', '<span class="brv-badge brv-badge-' . ($status === 'open' ? 'open' : 'resolved') . '">' . _brvEsc($status) . '</span>'
                 . (($j['resolution_note'] ?? '') !== '' ? ' — ' . _brvEsc($j['resolution_note']) : ''))
         . '</table>';

    foreach ((array)($j['followups'] ?? []) as $k => $f) {
        $out .= '<div class="prv-followup"><b>Follow-up ' . ((int)$k + 1) . '</b> · ' . _brvEsc(_prvTime($f['at'] ?? ''))
              . ' · game ' . _brvEsc($f['game_name'] ?? '') . '<br>' . nl2br(_brvEsc($f['note'] ?? '')) . '</div>';
    }

    $frozen = !empty($loaded['followupChats']) ? end($loaded['followupChats']) : ($loaded['chat'] ?? []);
    $lines = _prvMerged((array)$frozen);
    $matchSideboard = 'sideboard:' . strval($j['match_id'] ?? '');
    $isMatch = function (array $row) use ($matchGames, $matchSideboard): bool {
        $src = strval($row['src'] ?? '');
        return $src === $matchSideboard || (strncmp($src, 'game:', 5) === 0 && in_array(substr($src, 5), array_map('strval', $matchGames), true));
    };
    $first = count($lines);
    foreach ($lines as $i => $row) if ($isMatch($row)) { $first = $i; break; }
    $out .= '<h3>Chat at the time of the report</h3>';
    if (empty($lines)) $out .= '<div class="brv-empty">No chat was saved for this game.</div>';
    if ($first > 0 && $first < count($lines)) {
        $out .= '<details><summary>Earlier room chat (' . $first . ' line' . ($first === 1 ? '' : 's') . ')</summary>';
        for ($i = 0; $i < $first; $i++) $out .= _prvChatLine($lines[$i]);
        $out .= '</details>';
    } else if ($first === count($lines)) {
        $first = 0;   // nothing tagged as the match (a matchless game, or chat from before saved chat): show it all
    }
    for ($i = $first; $i < count($lines); $i++) $out .= _prvChatLine($lines[$i]);

    $later = _prvMerged($after);
    if (!empty($later)) {
        $out .= '<h3>After the report</h3>';
        foreach ($later as $row) $out .= _prvChatLine($row);
    }

    $out .= '<div class="prv-actions">';
    if ($status === 'open') {
        $out .= '<input id="prv-note" class="brv-target" maxlength="1000" placeholder="Resolution note">'
              . '<button type="button" onclick="prvAct(\'close\', this)">Close</button>';
    } else {
        $out .= '<button type="button" onclick="prvAct(\'reopen\', this)">Reopen</button>';
    }
    $out .= ' <button type="button" onclick="prvAct(\'delete\', this)">Delete</button></div>';
    return $out . '<div id="brv-status" class="brv-status"></div>' . _prvActionScript($id);
}

function _prvActionScript(string $id): string {
    $idJs = json_encode($id);
    return "<script>
      function prvAct(action, btn){
        if(action === 'delete' && btn.dataset.armed !== '1'){
          btn.dataset.armed = '1'; btn.textContent = 'Confirm delete';
          setTimeout(function(){ if(btn.dataset.armed === '1'){ btn.dataset.armed = ''; btn.textContent = 'Delete'; } }, 4000);
          return;
        }
        var s = document.getElementById('brv-status');
        var n = document.getElementById('prv-note');
        btn.disabled = true;
        var body = new URLSearchParams({action: action, id: $idJs, note: n ? n.value : ''});
        fetch(window.location.pathname, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body})
          .then(function(r){ return r.json(); })
          .then(function(j){
            if(j && j.success){
              s.className = 'brv-status brv-status-ok'; s.textContent = '✅ ' + j.message;
              setTimeout(function(){ window.location = action === 'delete' ? window.location.pathname : window.location.href; }, 600);
            } else { s.className = 'brv-status brv-status-err'; s.textContent = '❌ ' + ((j && j.error) || 'Failed.'); btn.disabled = false; }
          })
          .catch(function(e){ s.className = 'brv-status brv-status-err'; s.textContent = '❌ ' + e; btn.disabled = false; });
      }
    </script>";
}

function PlayerReportViewerPage(string $body, string $notice = ''): string {
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
         . '<title>Player Reports</title>' . _brvViewerStyles()
         . '<style>.prv-line{padding:3px 0;border-bottom:1px solid rgba(127,127,127,.15);overflow-wrap:anywhere}'
         . '.prv-meta{opacity:.6;font-size:12px}.prv-followup{margin:8px 0;padding:6px 8px;border-left:3px solid #c9a227}'
         . '.prv-actions{margin:14px 0;display:flex;gap:8px;flex-wrap:wrap}.prv-fields th{text-align:left;white-space:nowrap}</style>'
         . '</head><body><h1>Player Reports</h1><p><a href="zzBugReportViewer.php">Bug reports →</a></p>'
         . ($notice !== '' ? '<div class="brv-error">' . _brvEsc($notice) . '</div>' : '')
         . $body . '</body></html>';
}
