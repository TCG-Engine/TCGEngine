<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_public_api_sql_injection.php
//
// Public stats APIs must not paste request values into SQL (2026-10-22, before the SWUStats rebuild).
//   - APIs/GetMeleeTournaments.php: date_from / date_to went into WHERE verbatim —
//     date_from=2099-01-01' OR '1'='1 returned EVERY tournament.
//   - Stats/CardMetaStatsAPI.php: sort went into ORDER BY verbatim.
// Contract (CLAUDE.md "Public APIs are a contract"): every legitimate request answers exactly as before.
// Read-only: GET requests against this container's own Apache; nothing is written.
header('Content-Type: text/plain');

function api_get(string $path): array {
    $ch = curl_init('http://localhost/TCGEngine/' . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, (string)$raw, json_decode((string)$raw, true)];
}
function melee_count(string $query): ?int {
    [, , $j] = api_get('APIs/GetMeleeTournaments.php?limit=1000' . $query);
    return (is_array($j) && isset($j['tournaments']) && is_array($j['tournaments'])) ? count($j['tournaments']) : null;
}

$checks = [];

// ── GetMeleeTournaments: date filters ───────────────────────────────────────────────────────────
$all = melee_count('');
$checks['melee: fixture has tournaments (precondition)'] = $all !== null && $all > 0;
$checks['melee: legit date_from in the past keeps every tournament'] = melee_count('&date_from=2000-01-01') === $all;
$checks['melee: legit date_from in the future returns none'] = melee_count('&date_from=2099-01-01') === 0;
$checks['melee: legit date_to in the past returns none'] = melee_count('&date_to=2000-01-01') === 0;
$inj = rawurlencode("2099-01-01' OR '1'='1");
$checks['melee: injected date_from does NOT return every tournament'] = melee_count('&date_from=' . $inj) !== $all;
$injTo = rawurlencode("2000-01-01' OR '1'='1");
$checks['melee: injected date_to does NOT return every tournament'] = melee_count('&date_to=' . $injTo) !== $all;

// ── CardMetaStatsAPI: sort ──────────────────────────────────────────────────────────────────────
// Legit sorts (real columns, any case, surrounding spaces) answer exactly as before (200 + JSON). The
// response bodies are compared with the default sort's, so "unchanged" is checked, not just "is JSON".
[, $defaultRaw] = api_get('Stats/CardMetaStatsAPI.php');
foreach (['cardID', 'timesPlayed', 'TIMESPLAYED', ' week '] as $s) {
    [$code, $raw, $j] = api_get('Stats/CardMetaStatsAPI.php?sort=' . rawurlencode($s) . '&order=desc');
    $checks["cardmeta: sort='$s' answers 200 + JSON"] = $code === 200 && is_array($j);
}
[, $cardIdRaw] = api_get('Stats/CardMetaStatsAPI.php?sort=cardID');
$checks['cardmeta: explicit sort=cardID is byte-identical to the default'] = $cardIdRaw === $defaultRaw;
// Anything that is not a column used to throw in mysqli: a PHP fatal with a stack trace (path + SQL) locally,
// a bare HTTP 500 on prod. It still fails — as a clean 400 JSON error — and the query never runs.
foreach (["cardID,(SELECT SLEEP(3))", "nosuchcolumn", "cardID; DROP TABLE x"] as $s) {
    $t0 = microtime(true);
    [$code, $raw, $j] = api_get('Stats/CardMetaStatsAPI.php?sort=' . rawurlencode($s));
    $checks["cardmeta: sort='$s' answers 400 + JSON error"] = $code === 400 && is_array($j) && isset($j['error']);
    $checks["cardmeta: sort='$s' leaks no SQL / stack trace"] = stripos($raw, 'mysqli') === false && stripos($raw, 'Stack trace') === false;
    $checks["cardmeta: sort='$s' runs no injected SQL (no delay)"] = (microtime(true) - $t0) < 2.5;
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
