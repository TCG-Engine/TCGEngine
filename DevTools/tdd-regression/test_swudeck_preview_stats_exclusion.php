<?php
// RUN VIA CLI (this test makes loopback HTTP POSTs to SubmitGameResult.php; serving it over HTTP too
// can deadlock/stall the apache worker pool on docker-for-mac). Invoke:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_preview_stats_exclusion.php
//
// A PREVIEW format is played with hand-curated MOCK cards (AppCore/SWU/CardMocks.php) that can be
// wrong, mid-errata, or deleted outright on release day. Nothing about such a game should describe
// the real meta or a player's record, so a preview submission must write NO stats at all: no
// deckstats row, and no completedgame row (the raw log feeds meta + matchup browsing, and rows
// referencing mock CardIDs would linger after the mocks are gone).
//
// Design: docs/superpowers/specs/2026-07-29-swu-preview-format-design.md §3.
// Meta AGGREGATES were already excluded — 'preview' was never in SubmitGameResult.php's meta
// allowlist — so this covers the two write paths that were still live.
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
foreach (['preview', 'twinsuns-preview', 'padawan-preview'] as $fmt) {
    wipeDeck($conn, $deckID);
    $since = maxGid($conn);
    $resp  = postJson($endpoint, payload($apiKey, $deckID, $fmt, $sentinel));
    $row   = newRow($conn, $since, $sentinel);
    if ($row) $createdGids[] = $row['GameID'];
    $checks["$fmt: request still succeeds"]      = strpos((string)$resp, '"success":true') !== false;
    $checks["$fmt: writes no deckstats row"]     = deckRows($conn, $deckID) === 0;
    $checks["$fmt: writes no completedgame row"] = $row === null;
}

// The third write path: meta aggregates. No preview format is in SubmitGameResult's meta allowlist,
// so these were already excluded — pin it, so adding a preview format to that list can never silently
// start aggregating mock cards. Asserted across the WHOLE table rather than this test's own fixture:
// scoped to a synthetic leader the check could never fail, whereas a table-wide scan is a genuine
// leak detector. A failure here means real preview rows reached a meta aggregate — investigate and
// purge them; it is not a fixture problem.
$fmtList = "'preview','twinsuns-preview','padawan-preview'";
foreach (['deckmetastats', 'cardmetastats', 'deckmetamatchupstats'] as $t) {
    $n = intval($conn->query("SELECT COUNT(*) c FROM `$t` WHERE format IN ($fmtList)")->fetch_assoc()['c']);
    $checks["no preview-format row in $t"] = $n === 0;
}

// Control — a NON-preview format must still write both, proving the assertions above can detect a
// write at all rather than passing vacuously. 'hotseat' is deliberate: it exercises the same two
// write paths but sits outside the meta allowlist (SubmitGameResult.php), so no shared aggregate
// table is touched. Every row it creates is keyed by the throwaway deckID or the sentinel hero and
// is deleted below. (premier/eternal contract regression is already covered by
// test_swudeck_deckstats_auto_format.php + test_swudeck_completedgame_format.php.)
wipeDeck($conn, $deckID);
$since = maxGid($conn);
postJson($endpoint, payload($apiKey, $deckID, 'hotseat', $sentinel));
$controlRow = newRow($conn, $since, $sentinel);
if ($controlRow) $createdGids[] = $controlRow['GameID'];
$checks['control (hotseat) writes a deckstats row']     = deckRows($conn, $deckID) > 0;
$checks['control (hotseat) writes a completedgame row'] = $controlRow !== null;
$checks['control row carries its format']               = ($controlRow['Format'] ?? null) === 'hotseat';

// ── Part C: the manual-entry endpoint ────────────────────────────────────────
// SubmitManualGameResult.php is the second SaveDeckStats writer (hand-logged games). It already
// short-circuits on 'open' for the same reason preview needs one, and it writes the same deck-keyed
// tables — so a preview game logged here would leave rows referencing mock cards behind. SWUDeck's
// format catalog doesn't offer preview formats, but the endpoint accepts whatever it is sent.
$manualEndpoint = 'http://localhost/TCGEngine/APIs/SubmitManualGameResult.php';
function manualPayload($deckID, $format) {
    return [
        'deckID' => $deckID, 'won' => true, 'firstPlayer' => true, 'rounds' => 3, 'winnerHealth' => 10,
        'format' => $format,
        'player' => json_encode(['leader' => 'SEC_T01', 'base' => 'Green', 'cardResults' => [], 'turnResults' => []]),
    ];
}
foreach (['preview', 'twinsuns-preview', 'padawan-preview'] as $fmt) {
    wipeDeck($conn, $deckID);
    postJson($manualEndpoint, manualPayload($deckID, $fmt));
    $checks["$fmt (manual entry): writes no deckstats row"] = deckRows($conn, $deckID) === 0;
}
// Same control rationale as above — proves the manual path writes at all.
wipeDeck($conn, $deckID);
postJson($manualEndpoint, manualPayload($deckID, 'hotseat'));
$checks['control (hotseat, manual entry) writes a deckstats row'] = deckRows($conn, $deckID) > 0;

// Cleanup — leave the shared DB exactly as found.
foreach ($createdGids as $gid) delGid($conn, $gid);
wipeDeck($conn, $deckID);
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
