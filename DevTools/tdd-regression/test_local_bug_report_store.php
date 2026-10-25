<?php
// docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_local_bug_report_store.php
//
// Petranaki stores its own SWUSim bug reports (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md).
// The owner's top requirement: a submitted report must reliably land where the owner will find it. So the store
// writes to DISK FIRST (temp dir, then rename), hands out a P-<n> number even when the database is down, makes a
// retried submission (same client token) one report, and reconciles disk-only reports into the database later.
// CLI only. Uses a temp storage dir (SWUSIM_BUGREPORT_DIR) and the scratch databases swusim_bugreporttest(_notable),
// never the site's own store or tables.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', '1');
chdir('/var/www/html/TCGEngine');
require_once './Database/ConnectionManager.php';

$dir = sys_get_temp_dir() . '/lbr-test-' . getmypid() . '-' . time();
putenv("SWUSIM_BUGREPORT_DIR=$dir");
require_once './LocalBugReportLib.php';

$host = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
$user = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
$pass = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$admin = new mysqli($host, $user, $pass);
foreach (['swusim_bugreporttest', 'swusim_bugreporttest_notable'] as $db) {
    $admin->query("CREATE DATABASE IF NOT EXISTS `$db`");
    $admin->query("DROP TABLE IF EXISTS `$db`.`swusim_bug_reports`");
}
$conn = new mysqli($host, $user, $pass, 'swusim_bugreporttest');
$conn->multi_query(file_get_contents('./Database/migrations/19_swusim_bug_reports.sql'));
while ($conn->more_results() && $conn->next_result()) {}
$noTable = new mysqli($host, $user, $pass, 'swusim_bugreporttest_notable');

$checks = [];
$now = 1790000000;
$report = function (array $over = []) use ($now) {
    return $over + ['gameName' => '1234', 'playerID' => '1', 'verified' => true, 'unverifiedReason' => '',
        'reporter' => 'drixx', 'accountId' => 5, 'accountName' => 'Drixx', 'description' => 'Shield popped early',
        'gamestateHash' => str_repeat('a', 64), 'clientIp' => '203.0.113.7', 'createdAt' => $now];
};
$rows = fn() => (int)$conn->query('SELECT COUNT(*) FROM swusim_bug_reports')->fetch_row()[0];
$dirs = fn() => count(glob("$dir/P-*", GLOB_ONLYDIR) ?: []);

// ── first verified report ─────────────────────────────────────────────────────────────────────────
$r1 = LocalBugReportSave($report(), "GAMESTATE-ONE\n", 'token-aaaaaaaa', $conn);
$checks['save: first verified report → P-1, dir has report.json + Gamestate.txt, row in DB'] =
    $r1['ok'] && $r1['reportNumber'] === 1 && !$r1['duplicate'] && $r1['dbSaved']
    && is_file("$dir/P-1/report.json") && file_get_contents("$dir/P-1/Gamestate.txt") === "GAMESTATE-ONE\n" && $rows() === 1;
$json = json_decode((string)@file_get_contents("$dir/P-1/report.json"), true) ?: [];
$keys = ['report_number', 'number', 'root', 'created_at', 'game_name', 'player_id', 'verified', 'unverified_reason',
    'reporter', 'account_id', 'account_name', 'description', 'gamestate_hash', 'has_snapshot'];
$checks['save: report.json carries the 14 keys, no client IP'] = array_keys($json) == $keys && $json['report_number'] === 'P-1'
    && $json['root'] === 'SWUSim' && strpos((string)file_get_contents("$dir/P-1/report.json"), '203.0.113.7') === false;

// ── a retry with the same token is the same report ────────────────────────────────────────────────
$r1b = LocalBugReportSave($report(['description' => 'retry']), "GAMESTATE-ONE\n", 'token-aaaaaaaa', $conn);
$checks['save: same token twice → one report'] = $r1b['ok'] && $r1b['reportNumber'] === 1 && $r1b['duplicate'] && $dirs() === 1 && $rows() === 1;
$checks['find by token: known → number, unknown → null'] = LocalBugReportFindByToken('token-aaaaaaaa') === 1 && LocalBugReportFindByToken('token-zzzzzzzz') === null;

// ── unverified: no snapshot ever ──────────────────────────────────────────────────────────────────
$r2 = LocalBugReportSave($report(['verified' => false, 'unverifiedReason' => 'game not found', 'createdAt' => $now + 1]), "SHOULD-NOT-BE-KEPT", 'token-bbbbbbbb', $conn);
$j2 = json_decode((string)@file_get_contents("$dir/P-2/report.json"), true) ?: [];
$db2 = $conn->query('SELECT verified, hasSnapshot, unverifiedReason FROM swusim_bug_reports WHERE reportNumber = 2')->fetch_assoc() ?: [];
$checks['save: unverified → no Gamestate.txt, has_snapshot false, reason stored'] = $r2['reportNumber'] === 2
    && !is_file("$dir/P-2/Gamestate.txt") && ($j2['has_snapshot'] ?? null) === false
    && (int)($db2['hasSnapshot'] ?? 1) === 0 && ($db2['unverifiedReason'] ?? '') === 'game not found';

// ── database without the table: disk only, still a number ─────────────────────────────────────────
$r3 = LocalBugReportSave($report(['createdAt' => $now + 2]), "GS3", 'token-cccccccc', $noTable);
$checks['save: no table → disk only, dbSaved false'] = $r3['ok'] && $r3['reportNumber'] === 3 && !$r3['dbSaved'] && is_file("$dir/P-3/report.json");
$r3n = LocalBugReportSave($report(['createdAt' => $now + 3]), "GS4", 'token-dddddddd', null);
$checks['save: null connection → disk only, dbSaved false'] = $r3n['ok'] && $r3n['reportNumber'] === 4 && !$r3n['dbSaved'];
$checks['save: numbers increase across calls'] = [$r1['reportNumber'], $r2['reportNumber'], $r3['reportNumber'], $r3n['reportNumber']] === [1, 2, 3, 4];
$checks['save: no temp dirs left behind'] = (glob("$dir/.tmp-*") ?: []) === [];

// ── reconcile ─────────────────────────────────────────────────────────────────────────────────────
$added = LocalBugReportReconcile($conn);
$checks['reconcile inserts disk-only report'] = $added === 2 && $rows() === 4 && LocalBugReportReconcile($conn) === 0;
$rec = $conn->query('SELECT gameName, description, hasSnapshot FROM swusim_bug_reports WHERE reportNumber = 3')->fetch_assoc() ?: [];
$checks['reconcile: row rebuilt from report.json'] = ($rec['gameName'] ?? '') === '1234' && (int)($rec['hasSnapshot'] ?? 0) === 1;

// ── rate limits ───────────────────────────────────────────────────────────────────────────────────
// Verified: 1 per (game, seat) per 30 s. The newest verified report for game 1234 seat 1 is at $now + 3.
$w = LocalBugReportRateLimit($conn, true, '1234', '1', '198.51.100.1', $now + 13);
$checks['ratelimit: verified 2nd within 30s → wait >0'] = $w === 20;
$checks['ratelimit: verified after 30s → null'] = LocalBugReportRateLimit($conn, true, '1234', '1', '198.51.100.1', $now + 34) === null;
$checks['ratelimit: verified other seat → null'] = LocalBugReportRateLimit($conn, true, '1234', '2', '198.51.100.1', $now + 13) === null;
// Unverified: 5 per IP per hour (one unverified from 203.0.113.7 already exists at $now + 1).
for ($i = 0; $i < 4; $i++) {
    LocalBugReportSave($report(['verified' => false, 'unverifiedReason' => 'x', 'createdAt' => $now + 10 + $i]), null, "token-u$i-xxxxxx", $conn);
}
$wu = LocalBugReportRateLimit($conn, false, '9', '1', '203.0.113.7', $now + 100);
$checks['ratelimit: unverified 6th from one IP within an hour → wait >0'] = $wu === 3600 - 99;
$checks['ratelimit: unverified other IP → null'] = LocalBugReportRateLimit($conn, false, '9', '1', '203.0.113.8', $now + 100) === null;
$checks['ratelimit: unverified after the hour → null'] = LocalBugReportRateLimit($conn, false, '9', '1', '203.0.113.7', $now + 3602) === null;
$checks['ratelimit: no table → null (never refuse)'] = LocalBugReportRateLimit($noTable, true, '1234', '1', 'x', $now) === null
    && LocalBugReportRateLimit(null, true, '1234', '1', 'x', $now) === null;

// ── list / resolve / delete ───────────────────────────────────────────────────────────────────────
$list = LocalBugReportList($conn);
$checks['list: newest first, id P-n, has_snapshot mirrors verified'] = count($list) === 8 && $list[0]['id'] === 'P-8'
    && $list[count($list) - 1]['id'] === 'P-1' && $list[count($list) - 1]['has_snapshot'] === true
    && $list[count($list) - 2]['has_snapshot'] === false && $list[0]['source'] === 'local' && $list[0]['root_name'] === 'SWUSim'
    && $list[0]['number'] === 8 && $list[0]['snapshot_format'] === 'package-v1';
$checks['list: created_at as UTC Y-m-d H:i:s'] = $list[count($list) - 1]['created_at'] === gmdate('Y-m-d H:i:s', $now);
$res = LocalBugReportResolve($conn, 1, 'fixed in abc123');
$st = $conn->query('SELECT status, resolutionNote FROM swusim_bug_reports WHERE reportNumber = 1')->fetch_assoc();
$checks['resolve: status resolved + note'] = !empty($res['success']) && $st['status'] === 'resolved' && $st['resolutionNote'] === 'fixed in abc123';
$checks['resolve: unknown report → error'] = isset(LocalBugReportResolve($conn, 999, '')['error']);
$del = LocalBugReportDelete($conn, 1);
$checks['delete: dir, token and row gone'] = !empty($del['success']) && !is_dir("$dir/P-1") && LocalBugReportFindByToken('token-aaaaaaaa') === null
    && (int)$conn->query('SELECT COUNT(*) FROM swusim_bug_reports WHERE reportNumber = 1')->fetch_row()[0] === 0;
$checks['delete: unknown report → error'] = isset(LocalBugReportDelete($conn, 999)['error']);

// ── final review fixes (2026-10-22) ──────────────────────────────────────────────────────────────
// A description cut mid-character (the endpoint used to substr() at byte 4000) made json_encode return false and
// report.json land EMPTY behind a "saved" receipt. It must stay readable, with the bad byte substituted.
$cut = str_repeat('a', 10) . "\xE2\x80";   // the first two bytes of a curly quote
$rb = LocalBugReportSave($report(['description' => $cut, 'createdAt' => $now + 20]), "GS\n", 'token-badutf8-xx', $conn);
$jb = json_decode((string)@file_get_contents("$dir/P-{$rb['reportNumber']}/report.json"), true);
$checks['review: invalid UTF-8 description → report.json still readable'] = $rb['ok'] && is_array($jb)
    && strpos((string)($jb['description'] ?? ''), str_repeat('a', 10)) === 0;
$checks['review: invalid UTF-8 description → row in DB'] = $rb['dbSaved'];
// The insert bound the hash as an integer and hasSnapshot as a string: every row's hash read "0".
$hp = $conn->query('SELECT gamestateHash, hasSnapshot FROM swusim_bug_reports WHERE reportNumber = 2')->fetch_assoc();
$hv = $conn->query("SELECT gamestateHash, hasSnapshot FROM swusim_bug_reports WHERE reportNumber = {$rb['reportNumber']}")->fetch_assoc();
$checks['review: DB keeps the real gamestate hash and hasSnapshot'] = ($hv['gamestateHash'] ?? '') === str_repeat('a', 64)
    && (int)($hv['hasSnapshot'] ?? 0) === 1 && (int)($hp['hasSnapshot'] ?? 1) === 0;
// Spectator reports on a public game are "verified", and one IP could cycle through games: cap every report per IP.
for ($i = 0; $i < 20; $i++) {
    LocalBugReportSave($report(['gameName' => (string)(5000 + $i), 'clientIp' => '198.51.100.77', 'createdAt' => $now + 30 + $i]), "GS\n", "token-ipcap-$i-xx", $conn);
}
$checks['review: 21st report from one IP within an hour waits, verified or not'] =
    LocalBugReportRateLimit($conn, true, '6000', '1', '198.51.100.77', $now + 60) > 0
    && LocalBugReportRateLimit($conn, true, '6000', '1', '198.51.100.78', $now + 60) === null;
// Reports on disk that the database lacks (migration 19 not run, or DB down) must be announced, not hidden.
$checks['review: unindexed notice when the table is missing'] =
    strpos(LocalBugReportUnindexedNotice($noTable), 'not in the database') !== false
    && strpos(LocalBugReportUnindexedNotice(null), 'not in the database') !== false
    && LocalBugReportUnindexedNotice($conn) === '';

// ── report number parsing (paste from the viewer's copy text, or typed) ──────────────────────────
$checks['parse number: P-42, p-42, 42, pasted text → 42; abc → null'] = LocalBugReportParseNumber('P-42') === 42
    && LocalBugReportParseNumber('p-42') === 42 && LocalBugReportParseNumber('42') === 42
    && LocalBugReportParseNumber("Bug Report P-42\nRoot: SWUSim   Game: 7") === 42 && LocalBugReportParseNumber('abc') === null;

// cleanup: the temp store
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
@rmdir($dir);

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
exit(empty($fails) ? 0 : 1);
