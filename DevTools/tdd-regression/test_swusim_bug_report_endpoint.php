<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_bug_report_endpoint.php
//
// APIs/SubmitBugReport.php stores SWUSim reports on this box (LocalBugReportLib.php) instead of forwarding them
// (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md). Two players said they sent a report
// that never arrived; the endpoint used to REFUSE a report before saving anything when the game was gone (404)
// or the seat key unknown (403). Now such a report is saved as "unverified" (no snapshot), a retried request is
// the same report, and only an empty description or a rate limit is turned away. Other roots are unchanged.
// Builds a board with the Test Schema Editor's endpoint (seat key "testschema"), submits through Apache, and
// deletes every report it created. The GrandArchiveSim baseline comes from the unmodified endpoint:
//   /tmp/swusim_bugreport_ga_baseline.txt (body, newline, status), recorded in this container.
header('Content-Type: text/plain');
chdir(__DIR__ . '/../..');
require_once './Database/ConnectionManager.php';
require_once './LocalBugReportLib.php';

function http_post(string $path, $body, array $headers = []): array {
    $ch = curl_init('http://localhost/TCGEngine/' . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers]);
    $raw = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true), $raw];
}
$created = [];
$submit = function (array $fields) use (&$created) {
    $r = http_post('APIs/SubmitBugReport.php', json_encode($fields), ['Content-Type: application/json']);
    if (is_array($r[1]) && isset($r[1]['report_number'])) $created[] = LocalBugReportParseNumber($r[1]['report_number']);
    return $r;
};
$store = LocalBugReportStorageDir();
$dirs = fn() => count(glob("$store/P-*", GLOB_ONLYDIR) ?: []);
$checks = [];
$allJson = true;

[$code, $setup] = http_post('SWUSim/TestSchemaSetup.php', ['schema' => file_get_contents('./SWUSim/Tests/Visual/GameLog_UndoneAndDeckLines.md')]);
$game = (string)($setup['gameName'] ?? '');
$checks['precondition: schema board built'] = $game !== '';

$base = ['description' => 'endpoint test: shield popped early', 'gameName' => $game, 'playerID' => '1',
    'authKey' => 'testschema', 'folderPath' => 'SWUSim'];
$t1 = 'ept-' . bin2hex(random_bytes(8));

// ── a real seat: verified, snapshot on disk ───────────────────────────────────────────────────────
[$code, $j, $raw] = $submit($base + ['clientToken' => $t1]);
$allJson = $allJson && is_array($j);
$n1 = is_array($j) ? LocalBugReportParseNumber((string)($j['report_number'] ?? '')) : null;
$checks['endpoint: valid seat → 200, P-n, verified true, Gamestate.txt on disk'] = $code === 200 && ($j['success'] ?? null) === true
    && $n1 !== null && ($j['verified'] ?? null) === true && ($j['message'] ?? '') === "Bug report P-$n1 saved."
    && is_file("$store/P-$n1/Gamestate.txt") && filesize("$store/P-$n1/Gamestate.txt") > 0;

// ── the same request again (the response was lost, the browser retried) ───────────────────────────
$before = $dirs();
[$code, $j] = $submit($base + ['clientToken' => $t1]);
$allJson = $allJson && is_array($j);
$checks['endpoint: replayed token returns same number'] = $code === 200 && ($j['report_number'] ?? '') === "P-$n1"
    && ($j['duplicate'] ?? null) === true && $dirs() === $before;

// ── a second real report from the same seat inside 30 s ───────────────────────────────────────────
[$code, $j] = $submit($base + ['clientToken' => 'ept-' . bin2hex(random_bytes(8))]);
$allJson = $allJson && is_array($j);
$checks['endpoint: second verified report within 30s → 429 with retry_after'] = $code === 429
    && (int)($j['retry_after'] ?? 0) > 0 && (int)$j['retry_after'] <= 30 && isset($j['error']);

// ── the game is gone (end-of-game screen after cleanup) ───────────────────────────────────────────
[$code, $j] = $submit(['gameName' => '999999999', 'playerID' => '1', 'clientToken' => 'ept-' . bin2hex(random_bytes(8))] + $base);
$allJson = $allJson && is_array($j);
$nU = is_array($j) ? LocalBugReportParseNumber((string)($j['report_number'] ?? '')) : null;
$jU = $nU ? json_decode((string)@file_get_contents("$store/P-$nU/report.json"), true) : [];
$checks['endpoint: unknown game → unverified, no snapshot'] = $code === 200 && ($j['verified'] ?? null) === false && $nU !== null
    && !is_file("$store/P-$nU/Gamestate.txt") && ($jU['unverified_reason'] ?? '') === 'game not found';

// The seat-key-not-recognised branch is NOT checked here: this container runs with DEVENV=true, so Apache accepts
// every seat key. test_local_bug_report_verify.php covers it on the CLI with DEVENV off, as on prod.

// ── a long description is cut at 4000 BYTES — on a character boundary, never inside one ─────────────────
// (substr() cut a 2-byte "é" in half; json_encode then failed and report.json was written empty behind a receipt)
[$code, $j] = $submit(['gameName' => '999999998', 'description' => str_repeat('a', 3999) . 'é tail', 'clientToken' => 'ept-' . bin2hex(random_bytes(8))] + $base);
$allJson = $allJson && is_array($j);
$nL = is_array($j) ? LocalBugReportParseNumber((string)($j['report_number'] ?? '')) : null;
$jL = $nL ? json_decode((string)@file_get_contents("$store/P-$nL/report.json"), true) : null;
$checks['endpoint: 4000-byte cut lands on a character boundary'] = $code === 200 && is_array($jL)
    && ($jL['description'] ?? null) === str_repeat('a', 3999);

// ── nothing to report ─────────────────────────────────────────────────────────────────────────────
[$code, $j] = $submit(['description' => '   ', 'clientToken' => 'ept-' . bin2hex(random_bytes(8))] + $base);
$allJson = $allJson && is_array($j);
$checks['endpoint: empty description → 400'] = $code === 400 && isset($j['error']);
$checks['endpoint: response body is pure JSON'] = $allJson;

// ── every other root: exactly the old behaviour ──────────────────────────────────────────────────
$baseline = @file_get_contents('/tmp/swusim_bugreport_ga_baseline.txt');
$before = $dirs();
[$code, , $raw] = http_post('APIs/SubmitBugReport.php',
    json_encode(['description' => 'baseline', 'gameName' => 'abc', 'folderPath' => 'GrandArchiveSim', 'playerID' => '1']),
    ['Content-Type: application/json']);
$checks['endpoint: non-SWUSim root unchanged'] = is_string($baseline) && $baseline === $raw . "\n" . $code && $dirs() === $before;

// cleanup
$conn = GetLocalMySQLConnection();
foreach (array_filter($created) as $n) LocalBugReportDelete($conn, (int)$n);
$conn->close();

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
