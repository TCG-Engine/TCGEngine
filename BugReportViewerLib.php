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

    // ── Table ───────────────────────────────────────────────────────────────
    if (empty($rows)) {
        $out .= '<div class="brv-empty">No bug reports'
              . ($rootName !== '' ? ' for <b>' . _brvEsc($rootName) . '</b>' : '') . ' yet.</div>';
    } else {
        $out .= '<div class="brv-count">' . count($rows) . ' report' . (count($rows) === 1 ? '' : 's') . '</div>';
        $out .= '<table class="brv-table"><thead><tr>'
              . '<th>#</th><th>When</th><th>Root</th><th>Game</th><th>Origin</th><th>Reporter</th>'
              . '<th>Status</th><th>Snapshot</th><th>Hash</th><th>Description</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $out .= _brvRenderRow($r);
        }
        $out .= '</tbody></table>';
    }

    $out .= '</body></html>';
    return $out;
}

function _brvRenderRow(array $r): string {
    $origin = strval($r['origin'] ?? '');
    $originBadge = $origin === 'engine-ui'
        ? '<span class="brv-badge brv-badge-ui">in-game</span>'
        : '<span class="brv-badge brv-badge-discord">' . _brvEsc($origin !== '' ? $origin : 'discord') . '</span>';

    $status = strval($r['status'] ?? '');
    $statusBadge = '<span class="brv-badge ' . ($status === 'resolved' ? 'brv-badge-resolved' : 'brv-badge-open') . '">'
        . _brvEsc($status !== '' ? $status : 'open') . '</span>';

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

    return '<tr>'
        . '<td>' . _brvEsc($r['id'] ?? '') . '</td>'
        . '<td class="brv-nowrap">' . _brvEsc($r['created_at'] ?? '') . '</td>'
        . '<td>' . _brvEsc(BugReportViewerRootLabel(strval($r['root_name'] ?? ''))) . '</td>'
        . '<td>' . _brvEsc($r['game_name'] ?? '') . '</td>'
        . '<td>' . $originBadge . '</td>'
        . '<td>' . _brvEsc($reporter) . '</td>'
        . '<td>' . $statusBadge . '</td>'
        . '<td>' . $snap . '</td>'
        . '<td>' . $hashCell . '</td>'
        . '<td class="brv-desc">' . $descCell . '</td>'
        . '</tr>';
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
    </style>';
}
