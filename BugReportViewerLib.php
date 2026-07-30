<?php
// BugReportViewerLib.php — pure fetch + render helpers behind zzBugReportViewer.php.
// Kept separate from the web entrypoint so they can be unit-tested from the CLI
// (DevTools/tdd-regression) without the mod-login gate.

// Fetch the bug-report list from the remote intake API (read-only GET).
// Returns ['ok'=>bool, 'error'=>string, 'reports'=>array<assoc>].
function BugReportViewerFetch(string $apiUrl, string $apiKey): array {
    $apiUrl = trim($apiUrl);
    $apiKey = trim($apiKey);
    if ($apiUrl === '') return ['ok' => false, 'error' => 'Bug report API URL is not configured (APIKeys.php: $bugReportApiUrl).', 'reports' => []];
    if ($apiKey === '') return ['ok' => false, 'error' => 'Bug report API key is not configured (APIKeys.php: $bugReportApiKey).', 'reports' => []];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => ['X-API-Key: ' . $apiKey],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false)              return ['ok' => false, 'error' => 'Request to the bug report API failed: ' . $err, 'reports' => []];
    if ($code < 200 || $code >= 300)  return ['ok' => false, 'error' => 'Bug report API returned HTTP ' . $code . '.', 'reports' => []];
    $json = json_decode((string)$body, true);
    if (!is_array($json))             return ['ok' => false, 'error' => 'Bug report API returned a non-JSON response.', 'reports' => []];
    $reports = $json['bug_reports'] ?? $json['reports'] ?? null;
    if (!is_array($reports))          return ['ok' => false, 'error' => 'Bug report API response had no bug_reports array.', 'reports' => []];

    return ['ok' => true, 'error' => '', 'reports' => array_values($reports)];
}

// Fetch ONE report (with its gamestate snapshot) by id. Returns ['ok','error','report'=>assoc|null].
function BugReportViewerFetchOne(string $apiUrl, string $apiKey, int $id): array {
    $apiUrl = trim($apiUrl); $apiKey = trim($apiKey);
    if ($apiUrl === '' || $apiKey === '') return ['ok' => false, 'error' => 'Bug report API is not configured.', 'report' => null];
    if ($id <= 0)                         return ['ok' => false, 'error' => 'Invalid report id.', 'report' => null];

    $url = $apiUrl . (strpos($apiUrl, '?') !== false ? '&' : '?') . 'id=' . $id . '&includeSnapshot=1';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey]]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false)             return ['ok' => false, 'error' => 'Request failed: ' . $err, 'report' => null];
    if ($code < 200 || $code >= 300) return ['ok' => false, 'error' => 'API returned HTTP ' . $code . '.', 'report' => null];
    $json = json_decode((string)$body, true);
    $report = is_array($json) ? ($json['bug_report'] ?? null) : null;
    if (!is_array($report))          return ['ok' => false, 'error' => 'Report #' . $id . ' not found.', 'report' => null];
    return ['ok' => true, 'error' => '', 'report' => $report];
}

// Load a report's snapshot into a local SWUSim game and (for non-current modes) rewind the undo stack.
// SWUSim-only. $baseDir is the repo root. Returns a JSON-ready array: ['success'|'error', …].
function BugReportViewerHandleLoad(string $apiUrl, string $apiKey, int $id, string $mode, string $targetGame, string $baseDir): array {
    if (!in_array($mode, ['current', 'last-round', 'begin'], true)) return ['error' => 'Invalid mode.'];
    if ($targetGame === '' || !ctype_digit($targetGame))           return ['error' => 'Enter a numeric local SWUSim game number first.'];

    $one = BugReportViewerFetchOne($apiUrl, $apiKey, $id);
    if (!$one['ok']) return ['error' => $one['error']];
    $rep = $one['report'];

    $reportRoot = strval($rep['root_name'] ?? '');
    if ($reportRoot !== 'SWUSim') return ['error' => 'Loading is SWUSim-only — this report\'s root is "' . ($reportRoot !== '' ? $reportRoot : 'none') . '".'];
    $snap = strval($rep['gamestate_text'] ?? '');
    if ($snap === '') return ['error' => 'This report has no gamestate snapshot attached.'];

    $gameDir = $baseDir . '/SWUSim/Games/' . $targetGame;
    if (!is_dir($gameDir)) return ['error' => 'Local SWUSim game #' . $targetGame . ' was not found — create/open it once first, then load into it.'];
    $gsPath = $gameDir . '/Gamestate.txt';
    if (is_file($gsPath)) @copy($gsPath, $gameDir . '/Gamestate.backup.before_bugload.txt');
    if (file_put_contents($gsPath, $snap) === false) return ['error' => 'Failed to write ' . $gsPath . '.'];

    $step = ['stepped' => false, 'mode' => $mode, 'ordinal' => -1];
    if ($mode !== 'current') {
        $cli = $baseDir . '/SWUSim/DevTools/bugreport-load-state.php';
        $cmd = 'php ' . escapeshellarg($cli) . ' ' . escapeshellarg($targetGame) . ' ' . escapeshellarg($mode) . ' 2>&1';
        $out = shell_exec($cmd);
        $step = json_decode(trim((string)$out), true) ?: ['error' => 'Undo-step failed: ' . trim((string)$out)];
        if (!empty($step['error'])) return ['error' => $step['error']];
    }

    $where = $mode === 'begin' ? 'game start' : ($mode === 'last-round' ? 'start of the current round' : 'the reported state');
    return [
        'success'    => true,
        'message'    => 'Loaded bug #' . $id . ' into SWUSim game ' . $targetGame . ' at ' . $where . '.',
        'targetGame' => $targetGame,
        'mode'       => $mode,
        'step'       => $step,
    ];
}

// Close (resolve) a bug report via the intake API (POST operation:resolve). The API updates the DB to
// status='resolved' independently of Discord (and returns success even if the Discord notify fails —
// which it does for engine-ui reports, whose discord_channel_id is empty). Returns ['success'|'error', …].
function BugReportViewerResolve(string $apiUrl, string $apiKey, int $id, string $note = ''): array {
    $apiUrl = trim($apiUrl); $apiKey = trim($apiKey);
    if ($apiUrl === '' || $apiKey === '') return ['error' => 'Bug report API is not configured.'];
    if ($id <= 0)                         return ['error' => 'Invalid report id.'];

    $payload = json_encode(['operation' => 'resolve', 'id' => $id, 'resolution_note' => $note]);
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => ['X-API-Key: ' . $apiKey, 'Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) return ['error' => 'Request failed: ' . $err];
    $json = json_decode((string)$body, true);
    if ($code < 200 || $code >= 300) {
        return ['error' => (is_array($json) && isset($json['error'])) ? $json['error'] : ('Close failed (HTTP ' . $code . ').')];
    }
    return ['success' => true, 'id' => $id, 'message' => (is_array($json) && isset($json['message'])) ? $json['message'] : 'Bug report #' . $id . ' closed.'];
}

// A stable label for a report's root (blank root → Discord-origin reports with no game).
function BugReportViewerRootLabel(string $rootName): string {
    return $rootName === '' ? '(no root · Discord)' : $rootName;
}

// Count reports per root_name across the whole set → ['SWUSim'=>n, ''=>n, …], sorted by count desc.
function BugReportViewerRootCounts(array $reports): array {
    $counts = [];
    foreach ($reports as $r) {
        $root = strval($r['root_name'] ?? '');
        $counts[$root] = ($counts[$root] ?? 0) + 1;
    }
    arsort($counts);
    return $counts;
}

function _brvEsc($v): string { return htmlspecialchars(strval($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Render the full HTML page: root filter bar + the reports table for the active root.
function BugReportViewerRenderPage(array $fetch, string $rootName): string {
    $reports = $fetch['reports'];
    $counts  = BugReportViewerRootCounts($reports);
    $total   = count($reports);
    // Loading a snapshot into a local game only makes sense for SWUSim (undo rides in its gamestate),
    // so the Load controls appear only on the explicit SWUSim view.
    $loadable = ($rootName === 'SWUSim');

    // Filter to the active root when one is given; '' (no param) shows everything.
    $rows = $rootName === '' ? $reports : array_values(array_filter($reports, fn($r) => strval($r['root_name'] ?? '') === $rootName));

    $out  = '<!doctype html><html><head><meta charset="utf-8"><title>Bug Report Viewer'
          . ($rootName !== '' ? ' · ' . _brvEsc($rootName) : '') . '</title>' . _brvViewerStyles() . '</head><body>';
    $out .= '<h1>Bug Report Viewer</h1>';

    if (!$fetch['ok']) {
        $out .= '<div class="brv-error">' . _brvEsc($fetch['error']) . '</div></body></html>';
        return $out;
    }

    // ── Root filter bar ─────────────────────────────────────────────────────
    $out .= '<div class="brv-filters">';
    $allCls = $rootName === '' ? ' brv-active' : '';
    $out .= '<a class="brv-chip' . $allCls . '" href="?">All <span>' . $total . '</span></a>';
    foreach ($counts as $root => $n) {
        $cls = ($root === $rootName) ? ' brv-active' : '';
        $out .= '<a class="brv-chip' . $cls . '" href="?rootName=' . urlencode($root) . '">'
              . _brvEsc(BugReportViewerRootLabel($root)) . ' <span>' . $n . '</span></a>';
    }
    $out .= '</div>';

    // ── Load controls (SWUSim only) ─────────────────────────────────────────
    if ($loadable) {
        $out .= '<div class="brv-load-bar">Load into local SWUSim game #'
              . '<input id="brv-target-game" class="brv-target" inputmode="numeric" placeholder="e.g. 42">'
              . '<span class="brv-load-hint">— open that game once in the engine first, then Load a report\'s state into it.</span>'
              . '</div>';
    }
    // Shared status line for Load + Close actions (present on every view — Close works for any root).
    $out .= '<div id="brv-status" class="brv-status"></div>';

    // ── Table ───────────────────────────────────────────────────────────────
    if (empty($rows)) {
        $out .= '<div class="brv-empty">No bug reports'
              . ($rootName !== '' ? ' for <b>' . _brvEsc($rootName) . '</b>' : '') . ' yet.</div>';
    } else {
        $out .= '<div class="brv-count">' . count($rows) . ' report' . (count($rows) === 1 ? '' : 's') . '</div>';
        $out .= '<table class="brv-table"><thead><tr>'
              . '<th>#</th><th>When</th><th>Root</th><th>Game</th><th>Origin</th><th>Reporter</th>'
              . '<th>Status</th><th>Snapshot</th><th>Hash</th><th>Description</th>'
              . ($loadable ? '<th>Load</th>' : '') . '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $out .= _brvRenderRow($r, $loadable);
        }
        $out .= '</tbody></table>';
    }

    if ($loadable) $out .= _brvLoadScript();
    $out .= _brvActionScript();   // brvClose — available on every view
    $out .= '</body></html>';
    return $out;
}

function _brvRenderRow(array $r, bool $loadable = false): string {
    $origin = strval($r['origin'] ?? '');
    $originBadge = $origin === 'engine-ui'
        ? '<span class="brv-badge brv-badge-ui">in-game</span>'
        : '<span class="brv-badge brv-badge-discord">' . _brvEsc($origin !== '' ? $origin : 'discord') . '</span>';

    $status = strval($r['status'] ?? '');
    $statusBadge = '<span class="brv-badge ' . ($status === 'resolved' ? 'brv-badge-resolved' : 'brv-badge-open') . '">'
        . _brvEsc($status !== '' ? $status : 'open') . '</span>';
    // Close (resolve) button for still-open reports — works for any root.
    $rid = intval($r['id'] ?? 0);
    $statusCell = $statusBadge;
    if ($status !== 'resolved' && $rid > 0) {
        $statusCell .= ' <button class="brv-close-btn" onclick="brvClose(' . $rid . ', this)">Close</button>';
    }

    $hasSnap = !empty($r['has_snapshot']);
    $snap = $hasSnap ? _brvEsc($r['snapshot_format'] ?? 'snapshot') : '<span class="brv-muted">—</span>';

    $hash = strval($r['gamestate_hash'] ?? '');
    $hashCell = $hash !== ''
        ? '<code class="brv-hash" title="' . _brvEsc($hash) . '">' . _brvEsc(substr($hash, 0, 10)) . '…</code>'
        : '<span class="brv-muted">—</span>';

    $desc = strval($r['description'] ?? '');
    $descShort = mb_strlen($desc) > 140 ? mb_substr($desc, 0, 140) . '…' : $desc;
    $descCell = '<span title="' . _brvEsc($desc) . '">' . _brvEsc($descShort) . '</span>';

    $reporter = strval($r['discord_username'] ?? ($r['reporter_account_name'] ?? '')) ?: 'Unknown';

    $loadCell = '';
    if ($loadable) {
        $id = intval($r['id'] ?? 0);
        // Only in-game reports carry a loadable gamestate snapshot.
        if ($id > 0 && $origin === 'engine-ui' && $hasSnap) {
            $loadCell = '<td class="brv-load-cell">'
                . '<button class="brv-lbtn" onclick="brvLoad(' . $id . ",'current')\">Current</button>"
                . '<button class="brv-lbtn" onclick="brvLoad(' . $id . ",'last-round')\">Last Round</button>"
                . '<button class="brv-lbtn" onclick="brvLoad(' . $id . ",'begin')\">Game Begin</button>"
                . '</td>';
        } else {
            $loadCell = '<td class="brv-muted">—</td>';
        }
    }

    return '<tr>'
        . '<td>' . _brvEsc($r['id'] ?? '') . '</td>'
        . '<td class="brv-nowrap">' . _brvEsc($r['created_at'] ?? '') . '</td>'
        . '<td>' . _brvEsc(BugReportViewerRootLabel(strval($r['root_name'] ?? ''))) . '</td>'
        . '<td>' . _brvEsc($r['game_name'] ?? '') . '</td>'
        . '<td>' . $originBadge . '</td>'
        . '<td>' . _brvEsc($reporter) . '</td>'
        . '<td class="brv-nowrap">' . $statusCell . '</td>'
        . '<td>' . $snap . '</td>'
        . '<td>' . $hashCell . '</td>'
        . '<td class="brv-desc">' . $descCell . '</td>'
        . $loadCell
        . '</tr>';
}

// Client JS for the Close (resolve) buttons — posts ?action=resolve and flips the row to "resolved".
function _brvActionScript(): string {
    return "<script>
      function brvClose(id, btn){
        var s = document.getElementById('brv-status');
        if(btn){ btn.disabled = true; btn.textContent = 'Closing…'; }
        var body = new URLSearchParams({action:'resolve', id:id});
        fetch(window.location.pathname + window.location.search, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body})
          .then(function(r){ return r.json(); })
          .then(function(j){
            if(j && j.success){
              if(s){ s.className='brv-status brv-status-ok'; s.textContent='✅ '+(j.message||('Bug #'+id+' closed.')); }
              var td = btn && btn.closest('td');
              if(td){ td.innerHTML = '<span class=\"brv-badge brv-badge-resolved\">resolved</span>'; }
            } else {
              if(s){ s.className='brv-status brv-status-err'; s.textContent='❌ '+((j&&j.error)||'Close failed.'); }
              if(btn){ btn.disabled=false; btn.textContent='Close'; }
            }
          })
          .catch(function(e){ if(s){ s.className='brv-status brv-status-err'; s.textContent='❌ '+e; } if(btn){ btn.disabled=false; btn.textContent='Close'; } });
      }
    </script>";
}

// Client JS for the Load buttons — posts back to this page (?action=load) and shows an inline status
// (no native alert()/confirm() — those are banned and worse UX than an inline banner).
function _brvLoadScript(): string {
    return "<script>
      function brvLoad(id, mode){
        var g = (document.getElementById('brv-target-game').value || '').trim();
        var s = document.getElementById('brv-status');
        if(!/^[0-9]+$/.test(g)){ s.className='brv-status brv-status-err'; s.textContent='Enter a numeric local SWUSim game number first.'; return; }
        s.className='brv-status brv-status-busy'; s.textContent='Loading bug #'+id+' ('+mode+') into game '+g+'…';
        var body = new URLSearchParams({action:'load', id:id, mode:mode, targetGame:g});
        fetch(window.location.pathname + window.location.search, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body})
          .then(function(r){ return r.json(); })
          .then(function(j){
            if(j && j.success){ s.className='brv-status brv-status-ok'; s.textContent='✅ '+(j.message||'Loaded.'); }
            else { s.className='brv-status brv-status-err'; s.textContent='❌ '+((j&&j.error)||'Load failed.'); }
          })
          .catch(function(e){ s.className='brv-status brv-status-err'; s.textContent='❌ '+e; });
      }
    </script>";
}

function _brvViewerStyles(): string {
    return '<style>
      body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;margin:24px;background:#0f141b;color:#e6edf3;}
      h1{font-size:20px;margin:0 0 16px;}
      .brv-error{background:#3a1417;border:1px solid #7a2c34;color:#ffb3ba;padding:12px 16px;border-radius:8px;}
      .brv-empty{color:#8b98a5;padding:24px 0;}
      .brv-count{color:#8b98a5;font-size:13px;margin:4px 0 10px;}
      .brv-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;}
      .brv-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;text-decoration:none;
        background:#1b2430;border:1px solid #2a3543;color:#c9d4df;font-size:13px;}
      .brv-chip span{background:#2a3543;border-radius:999px;padding:1px 7px;font-size:11px;color:#9fb0c0;}
      .brv-chip.brv-active{background:#1f6feb;border-color:#1f6feb;color:#fff;}
      .brv-chip.brv-active span{background:rgba(255,255,255,0.22);color:#fff;}
      .brv-table{border-collapse:collapse;width:100%;font-size:13px;}
      .brv-table th,.brv-table td{border-bottom:1px solid #222c38;padding:8px 10px;text-align:left;vertical-align:top;}
      .brv-table th{color:#8b98a5;font-weight:600;position:sticky;top:0;background:#0f141b;}
      .brv-table tr:hover td{background:#151d27;}
      .brv-nowrap{white-space:nowrap;}
      .brv-desc{max-width:420px;}
      .brv-muted{color:#5a6b7b;}
      .brv-hash{color:#9fb0c0;}
      .brv-badge{display:inline-block;padding:1px 8px;border-radius:999px;font-size:11px;}
      .brv-badge-ui{background:#12324a;color:#7fd3ff;}
      .brv-badge-discord{background:#2b2440;color:#c3adf0;}
      .brv-badge-open{background:#3a2a12;color:#ffcf8f;}
      .brv-badge-resolved{background:#16351f;color:#8fe0a5;}
      .brv-load-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:#12324a;border:1px solid #1c4a6b;
        color:#cfe8ff;padding:9px 14px;border-radius:8px;margin:0 0 10px;font-size:13px;}
      .brv-target{width:80px;background:#0b1620;border:1px solid #2a3543;color:#e6edf3;border-radius:6px;padding:4px 8px;font-size:13px;}
      .brv-load-hint{color:#7fa9c7;}
      .brv-status{min-height:18px;font-size:13px;margin:0 0 12px;}
      .brv-status-busy{color:#9fb0c0;}
      .brv-status-ok{color:#8fe0a5;}
      .brv-status-err{color:#ffb3ba;}
      .brv-load-cell{white-space:nowrap;}
      .brv-lbtn{background:#1b2430;border:1px solid #2a3543;color:#cfe8ff;border-radius:6px;padding:3px 8px;margin:0 2px;font-size:12px;cursor:pointer;}
      .brv-lbtn:hover{background:#1f6feb;border-color:#1f6feb;color:#fff;}
      .brv-close-btn{background:#2a1e10;border:1px solid #5a4420;color:#ffcf8f;border-radius:6px;padding:2px 8px;margin-left:6px;font-size:11px;cursor:pointer;}
      .brv-close-btn:hover:not(:disabled){background:#7a5a1d;border-color:#7a5a1d;color:#fff;}
      .brv-close-btn:disabled{opacity:0.6;cursor:default;}
    </style>';
}
