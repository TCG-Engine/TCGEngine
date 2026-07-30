<?php
// Hermetic guard for BugReportViewerLib.php (behind zzBugReportViewer.php). Render + counts +
// config/escaping logic against synthetic data — NO network (the live remote fetch happy-path
// is verified manually; here we only exercise the pure logic + fetch's config-error paths).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_bugreport_viewer.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
require_once './BugReportViewerLib.php';

$fails = 0;
$chk = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── Fetch config-error paths (no network) ──────────────────────────────────
$f1 = BugReportViewerFetch('', 'k');
$chk(!$f1['ok'] && strpos($f1['error'], 'URL') !== false, 'fetch: missing URL → config error');
$f2 = BugReportViewerFetch('https://example.test/api', '');
$chk(!$f2['ok'] && strpos($f2['error'], 'key') !== false, 'fetch: missing key → config error');

// ── Synthetic report set spanning multiple roots ───────────────────────────
$reports = [
    ['id' => 5, 'root_name' => 'SWUSim',          'game_name' => '3151', 'origin' => 'engine-ui', 'discord_username' => 'drixx', 'status' => 'open',     'gamestate_hash' => str_repeat('a', 64), 'has_snapshot' => 1, 'snapshot_format' => 'raw-gamestate-v1', 'created_at' => '2026-07-30 01:00:00', 'description' => 'Devastator did not set friendly-defeated'],
    ['id' => 4, 'root_name' => 'GrandArchiveSim', 'game_name' => '900',  'origin' => 'discord',   'discord_username' => 'user2', 'status' => 'resolved', 'gamestate_hash' => '',                   'has_snapshot' => 0, 'created_at' => '2026-07-29 01:00:00', 'description' => 'text'],
    ['id' => 3, 'root_name' => 'GrandArchiveSim', 'game_name' => '901',  'origin' => 'engine-ui', 'discord_username' => 'user3', 'status' => 'open',     'gamestate_hash' => str_repeat('b', 64), 'has_snapshot' => 1, 'snapshot_format' => 'raw-gamestate-v1', 'created_at' => '2026-07-28 01:00:00', 'description' => '<script>evilXss</script> injection attempt'],
    ['id' => 2, 'root_name' => '',                'game_name' => '',     'origin' => 'discord',   'discord_username' => 'user4', 'status' => 'open',     'gamestate_hash' => '',                   'has_snapshot' => 0, 'created_at' => '2026-07-27 01:00:00', 'description' => 'no-root discord report'],
];
$fetch = ['ok' => true, 'error' => '', 'reports' => $reports];

// ── Root counts ────────────────────────────────────────────────────────────
$counts = BugReportViewerRootCounts($reports);
$chk(($counts['GrandArchiveSim'] ?? 0) === 2, 'root counts: GrandArchiveSim=2');
$chk(($counts['SWUSim'] ?? 0) === 1, 'root counts: SWUSim=1');
$chk(($counts[''] ?? 0) === 1, "root counts: blank root=1");

// ── Render: filter by root ─────────────────────────────────────────────────
$ga = BugReportViewerRenderPage($fetch, 'GrandArchiveSim');
$chk(substr_count($ga, '<tr>') === 3, 'GA view: header + 2 GrandArchiveSim rows (got ' . substr_count($ga, '<tr>') . ')');
$chk(strpos($ga, '>901<') !== false && strpos($ga, '>900<') !== false, 'GA view: both GA games present');
$chk(strpos($ga, '>3151<') === false, 'GA view: SWUSim game NOT present (filtered out)');

$sw = BugReportViewerRenderPage($fetch, 'SWUSim');
$chk(substr_count($sw, '<tr>') === 2, 'SWUSim view: header + 1 row');
$chk(strpos($sw, 'raw-gamestate-v1') !== false, 'SWUSim view: snapshot format shown');

// ── Render: All (no root param) ─────────────────────────────────────────────
$all = BugReportViewerRenderPage($fetch, '');
$chk(substr_count($all, '<tr>') === 5, 'All view: header + 4 rows');
$chk(strpos($all, '>All <span>4</span>') !== false, 'All chip shows total 4');
$chk(strpos($all, '(no root · Discord)') !== false, 'blank root labeled');

// ── XSS: a report description with <script> must be escaped ─────────────────
$chk(strpos($all, '<script>evilXss</script>') === false, 'XSS: raw <script> NOT emitted');
$chk(strpos($all, '&lt;script&gt;') !== false, 'XSS: script tag HTML-escaped');

// ── Fetch error surfaces in the page, no table ─────────────────────────────
$errPage = BugReportViewerRenderPage(['ok' => false, 'error' => 'boom', 'reports' => []], 'SWUSim');
$chk(strpos($errPage, 'boom') !== false && strpos($errPage, '<table') === false, 'fetch error rendered, no table');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
