<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swudeck_format_stats_policy_e2e.php
//
// END-TO-END proof of the three-tier format policy, exercised through BOTH submission endpoints:
//
//   registered + stats-producing  → records deck stats, completedgame and meta aggregates
//                                   (premier, eternal, twinsuns, padawan, and the four PREVIEW formats)
//   registered, not producing     → 200, records nothing (open, goldfish, hotseat)
//   not registered in AppCore     → 400, records nothing
//
// Renamed from test_swudeck_preview_stats_exclusion.php on 2026-08-06: preview formats used to write
// nothing and now record under their own format key, so the old name described the opposite policy.
//
// ⚠ THIS TEST POSTS to a prod-clone database. Every row it creates is keyed by the throwaway deckID,
// the sentinel hero, or the synthetic token leaders SEC_T01/SEC_T02 — and is deleted in teardown.
// Meta aggregate rows are NOT deck-keyed, so they need their own cleanup; see the bottom of the file.
//
// Design: docs/superpowers/specs/2026-08-06-swu-preview-format-stats-design.md
header('Content-Type: text/plain');
include_once __DIR__ . '/../../AppCore/SWU/Formats.php';
include_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../APIKeys/APIKeys.php';

$checks = [];

// ── Part A: which formats are preview ────────────────────────────────────────
$previewSets = require __DIR__ . '/../../AppCore/SWU/PreviewSets.php';
$checks['preview set list is non-empty'] = is_array($previewSets) && count($previewSets) > 0;

$checks['SWUFormatIsPreview() exists'] = function_exists('SWUFormatIsPreview');
if ($checks['SWUFormatIsPreview() exists']) {
    // Every format that DECLARES an unreleased set in its pool is a preview format.
    foreach (['preview', 'twinsuns-preview', 'padawan-preview'] as $f) {
        $checks["$f is preview"] = SWUFormatIsPreview($f) === true;
    }
    // The released formats each preview format is derived from are NOT preview.
    foreach (['premier', 'eternal', 'twinsuns', 'padawan'] as $f) {
        $checks["$f is not preview"] = SWUFormatIsPreview($f) === false;
    }
    // The wildcard pool ('*') resolves to every registered set INCLUDING preview sets, but an
    // anything-goes pool is not a preview window. Open/Goldfish/Hotseat keep the stats behavior
    // they have today — widening the gate to them would be an unrequested behavior change on a
    // public endpoint.
    foreach (['open', 'goldfish', 'hotseat'] as $f) {
        $checks["$f (wildcard pool) is not preview"] = SWUFormatIsPreview($f) === false;
    }
    $checks['unknown format is not preview'] = SWUFormatIsPreview('nope') === false;

    // Preview-ness must stay DERIVED from PreviewSets.php, never a hardcoded format list: removing a
    // set on release day (sunset checklist step 5) has to turn the gate off by itself. This invariant
    // holds for every registered format, so a new format cannot silently escape the gate.
    foreach (array_keys(SWUFormatDefinitions()) as $f) {
        $legal = SWUGetFormat($f)['legalSets'];
        $declaresPreviewSet = is_array($legal) && count(array_intersect($legal, $previewSets)) > 0;
        $checks["$f preview flag tracks its declared pool"] = SWUFormatIsPreview($f) === $declaresPreviewSet;
    }
}

// ── Part B: what the endpoint actually writes ────────────────────────────────
$endpoint = 'http://localhost/TCGEngine/APIs/SubmitGameResult.php'; // loopback inside the web container
$conn = GetLocalMySQLConnection();
// Locally APIKeys.php may not define these; the endpoint accepts an empty key in dev.
$apiKey = isset($petranakiAPIKey) ? $petranakiAPIKey : (isset($karabastAPIKey) ? $karabastAPIKey : '');

$deckID   = 999900101;    // throwaway deck; every deck-keyed row this test writes carries it
$sentinel = 'TWI_T01'; // throwaway WinningHero; identifies this test's completedgame rows

function postJson($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($data)]);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}
function payload($apiKey, $deckID, $format, $sentinel) {
    return [
        'apiKey' => $apiKey, 'winner' => 1, 'firstPlayer' => 1, 'round' => 3, 'winnerHealth' => 10,
        'gameName' => strval($deckID), 'winHero' => $sentinel, 'loseHero' => 'TWI_T02',
        'format' => $format, 'winnerDeck' => 'x', 'loserDeck' => 'y',
        'p1DeckLink' => "http://localhost/TCGEngine/?gameName=$deckID",
        // cardResults is deliberately EMPTY: a synthetic cardId fatals in SaveDeckStats's
        // carddeckstats UPDATE (mysqli STRICT — "Truncated incorrect DOUBLE value" comparing a
        // non-numeric id against a numeric column), which would abort the request before the
        // completedgame insert and make these assertions pass for the wrong reason. The deckstats
        // row this test checks is written independently of card-level stats.
        'player1' => json_encode(['leader' => 'SEC_T01', 'base' => 'Green',
            'cardResults' => [], 'turnResults' => []]),
        'player2' => json_encode(['leader' => 'SEC_T02', 'base' => 'Red', 'cardResults' => [], 'turnResults' => []]),
    ];
}
function wipeDeck($conn, $deckID) {
    foreach (['deckstats', 'carddeckstats', 'opponentdeckstats', 'opponentnamedbasestats'] as $t)
        $conn->query("DELETE FROM `$t` WHERE deckID = " . intval($deckID));
}
function deckRows($conn, $deckID) {
    return intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = " . intval($deckID))->fetch_assoc()['c']);
}
// completedgame is a prod-data copy and WinningHero is unindexed, so snapshot MAX(GameID) (instant on
// the PK) before each POST and inspect only rows past it — a bounded PK range scan, never a full scan.
function maxGid($conn) {
    return intval($conn->query("SELECT MAX(GameID) m FROM completedgame")->fetch_assoc()['m']);
}
function newRow($conn, $since, $sentinel) {
    $s = $conn->real_escape_string($sentinel);
    $since = intval($since);
    return $conn->query("SELECT GameID, Format FROM completedgame WHERE GameID > $since AND WinningHero = '$s' ORDER BY GameID DESC LIMIT 1")->fetch_assoc();
}
function delGid($conn, $gid) { $conn->query("DELETE FROM completedgame WHERE GameID = " . intval($gid)); }

// Public ownership fixture so the deck link resolves and $privateDeck stays false — otherwise the
// completedgame assertions would pass for the wrong reason (private decks are excluded already).
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");
$conn->query("INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetVisibility) VALUES (1, $deckID, 999999999, 1, 2000000)");

$createdGids = [];

// ── Preview formats RECORD, under their own format key ──────────────────────
// Reversed 2026-08-06: preview games used to write nothing. They are now first-class formats and the
// KEY is what separates them — `format` is part of the PRIMARY KEY on the meta tables, so a preview
// row can never merge with premier, nor eternal-preview with eternal.
//
// These are POSITIVE assertions, so they double as the proof that the probes below can detect a
// write at all. The old synthetic-format control existed only to provide that proof and is gone:
// unregistered formats are now rejected outright, and 'padawan' + disableMetaStats could not serve
// either — disableMetaStats sets $explicitOptOut, which suppresses the completedgame row too.
foreach (['preview', 'twinsuns-preview', 'padawan-preview', 'eternal-preview'] as $fmt) {
    wipeDeck($conn, $deckID);
    $since = maxGid($conn);
    $resp  = postJson($endpoint, payload($apiKey, $deckID, $fmt, $sentinel));
    $row   = newRow($conn, $since, $sentinel);
    if ($row) $createdGids[] = $row['GameID'];
    $checks["$fmt: request succeeds"]           = strpos((string)$resp, '"success":true') !== false;
    $checks["$fmt: writes a deckstats row"]     = deckRows($conn, $deckID) > 0;
    $checks["$fmt: writes a completedgame row"] = $row !== null;
    $checks["$fmt: row carries its own key"]    = ($row['Format'] ?? null) === $fmt;
}

// Meta aggregates: preview formats are in the write allowlist now, so a preview game DOES aggregate —
// under its own format key. Asserted on the fixture's synthetic leader (SEC_T01), which no real deck
// can have, so this can neither collide with nor be satisfied by production data.
$previewMeta = intval($conn->query(
    "SELECT COUNT(*) c FROM deckmetastats WHERE leaderID = 'SEC_T01' AND format = 'preview'"
)->fetch_assoc()['c']);
$checks['preview game aggregates under its own key'] = $previewMeta > 0;
// ...and never leaks into a released format's rows.
$premierLeak = intval($conn->query(
    "SELECT COUNT(*) c FROM deckmetastats WHERE leaderID = 'SEC_T01' AND format = 'premier'"
)->fetch_assoc()['c']);
$checks['preview game does not touch premier rows'] = $premierLeak === 0;

// ── Formats that record NOTHING ─────────────────────────────────────────────
// Open is an anything-goes pool; Goldfish is solo and Hotseat is one person on both seats — practice,
// not results. All three were recording until 2026-08-06 because only 'open' was excluded.
foreach (['open', 'goldfish', 'hotseat'] as $skipFmt) {
    wipeDeck($conn, $deckID);
    $sinceSkip = maxGid($conn);
    $skipResp  = postJson($endpoint, payload($apiKey, $deckID, $skipFmt, $sentinel));
    $skipRow   = newRow($conn, $sinceSkip, $sentinel);
    if ($skipRow) $createdGids[] = $skipRow['GameID'];
    $checks["'$skipFmt' is accepted"]                = strpos((string)$skipResp, '"success":true') !== false;
    $checks["'$skipFmt' writes no deckstats row"]     = deckRows($conn, $deckID) === 0;
    $checks["'$skipFmt' writes no completedgame row"] = $skipRow === null;
}

// ── An UNREGISTERED format is rejected outright ─────────────────────────────
// Third tier: not registered in AppCore → 400, nothing recorded, nothing silently mislabelled.
wipeDeck($conn, $deckID);
$sinceBad = maxGid($conn);
$badResp  = postJson($endpoint, payload($apiKey, $deckID, 'notaformat', $sentinel));
$badRow   = newRow($conn, $sinceBad, $sentinel);
if ($badRow) $createdGids[] = $badRow['GameID'];
$checks['unregistered format is rejected']             = strpos((string)$badResp, '"success":false') !== false;
$checks['unregistered format writes no deckstats']     = deckRows($conn, $deckID) === 0;
$checks['unregistered format writes no completedgame'] = $badRow === null;

// ── Part C: the manual-entry endpoint ────────────────────────────────────────
// SubmitManualGameResult.php is the second SaveDeckStats writer (hand-logged games), with its OWN
// copy of the gates. The two diverged once already: the 2026-08-06 change that stopped Goldfish and
// Hotseat recording landed on the engine endpoint only, so hand-logged practice games kept writing.
// Both endpoints must now answer the registry the same way.
$manualEndpoint = 'http://localhost/TCGEngine/APIs/SubmitManualGameResult.php';
function manualPayload($deckID, $format) {
    return [
        'deckID' => $deckID, 'won' => true, 'firstPlayer' => true, 'rounds' => 3, 'winnerHealth' => 10,
        'format' => $format,
        'player' => json_encode(['leader' => 'SEC_T01', 'base' => 'Green', 'cardResults' => [], 'turnResults' => []]),
    ];
}
// Preview records here too — positive assertions, which also prove the probe fires.
foreach (['preview', 'padawan-preview'] as $fmt) {
    wipeDeck($conn, $deckID);
    postJson($manualEndpoint, manualPayload($deckID, $fmt));
    $checks["$fmt (manual entry): writes a deckstats row"] = deckRows($conn, $deckID) > 0;
}
// ...and the local/solo modes do not. This is the assertion that was missing: hand-logged Goldfish
// and Hotseat games were still recording after the engine endpoint was fixed.
foreach (['open', 'goldfish', 'hotseat'] as $skipFmt) {
    wipeDeck($conn, $deckID);
    postJson($manualEndpoint, manualPayload($deckID, $skipFmt));
    $checks["'$skipFmt' (manual entry): writes no deckstats row"] = deckRows($conn, $deckID) === 0;
}

// Cleanup — leave the shared DB exactly as found.
foreach ($createdGids as $gid) delGid($conn, $gid);
wipeDeck($conn, $deckID);
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");
// Preview games now aggregate, and meta rows are keyed by leader/base/week/format — NOT by deckID,
// so wipeDeck() cannot reach them. Scoped to the fixture's synthetic token leaders, which no real
// deck can have, so this can never delete production rows however the format list changes.
$syn = "'SEC_T01','SEC_T02'";
foreach (['deckmetastats', 'deckmetamatchupstats'] as $t) {
    $conn->query("DELETE FROM `$t` WHERE leaderID IN ($syn)");
}
$conn->query("DELETE FROM opponentnamedbasestats WHERE leaderID IN ($syn)");

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
