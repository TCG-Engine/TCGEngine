<?php
// Maintenance gate — must precede any write.
// Same parser, same meleetournamentdeck writes.
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'stats');

// MeleeTournamentParserAPI.php
// API-safe version of the parser for FindOrImportMeleeTournament.php

include_once '../SWUDeck/Custom/CardIdentifiers.php';
set_time_limit(1800);
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once 'MeleeTournamentParser.php';

// Bumped whenever this file's import logic changes. Echoed back in the API's failure payload
// so a prod report proves which build actually ran (a stale deploy is otherwise invisible).
if (!defined('MELEE_PARSER_VERSION')) define('MELEE_PARSER_VERSION', '2026-07-29.2');

// Returns how many standings rows melee.gg has for this round (0 if none / on error).
// Top-cut bracket rounds can return a valid JSON payload with zero records, so a round
// must be probed before it is used as the import source.
// $probe is filled with the raw outcome (http code, curl error, body snippet) so a failed
// probe can be told apart from a genuinely empty round.
function meleeRoundStandingsCount($roundId, &$probe = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://melee.gg/Standing/GetRoundStandings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    // The endpoint is a server-side DataTables binding: without a `columns`/`order`
    // pair it 302s to an error page instead of returning JSON.
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'draw' => 1,
        'start' => 0,
        'length' => 1,
        'roundId' => $roundId,
        'search' => ['value' => '', 'regex' => false],
        'columns' => [
            ['data' => 'Rank', 'name' => 'Rank', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
        ],
        'order' => [['column' => 0, 'dir' => 'asc']],
    ]));
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Referer: https://melee.gg/',
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/melee_cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/melee_cookies.txt');
    $response = curl_exec($ch);
    $probe = [
        'roundId'  => (int)$roundId,
        'httpCode' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'curlError' => curl_error($ch) ?: null,
        'count'    => 0,
    ];
    curl_close($ch);
    if (!$response) { $probe['note'] = 'empty response body'; return 0; }
    $json = json_decode($response, true);
    if (!is_array($json)) {
        // Not JSON — melee served an error/redirect page. Keep a snippet; it is the only
        // way to tell throttling apart from a real "no standings" answer.
        $probe['note'] = 'non-JSON response: ' . substr(preg_replace('/\s+/', ' ', strip_tags($response)), 0, 120);
        return 0;
    }
    if (isset($json['recordsTotal'])) { $probe['count'] = (int)$json['recordsTotal']; return $probe['count']; }
    if (isset($json['data']) && is_array($json['data'])) { $probe['count'] = count($json['data']); return $probe['count']; }
    $probe['note'] = 'JSON with no recordsTotal/data key';
    return 0;
}

// Picks the round of a melee.gg tournament that holds the full final standings.
// Two melee behaviours make the naive "max(roundId)" wrong:
//   * round IDs are NOT monotonic with round order (melee re-issues IDs when a round is
//     re-created), so the numerically largest ID is not necessarily the last round;
//   * top-cut bracket rounds (Finals, and sometimes Quarter/Semifinals) expose an EMPTY
//     or cut-down standings table, so importing them yields no decklists at all.
// So: walk the round buttons in document order and take the latest round with the largest
// standings row count.
// $diag collects every observable step (page fetch result, rounds found, per-round probe
// outcomes, final pick) so a failure can be diagnosed from the API response alone.
function getHighestRoundIdFromTournament($tournamentId, &$diag = null) {
    $diag = ['stage' => 'fetch_tournament_page', 'tournamentId' => (string)$tournamentId];
    $url = "https://melee.gg/Tournament/View/" . urlencode($tournamentId);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Connection: keep-alive',
        'Referer: https://melee.gg/',
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/melee_cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/melee_cookies.txt');
    $html = curl_exec($ch);
    $diag['pageHttpCode'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $diag['pageBytes'] = is_string($html) ? strlen($html) : 0;
    $diag['pageCurlError'] = curl_error($ch) ?: null;
    curl_close($ch);
    if (!$html) {
        $diag['error'] = 'Could not fetch the melee.gg tournament page.';
        return false;
    }
    $matches = [];
    preg_match_all('/<button[^>]*class="[^"]*round-selector[^"]*"[^>]*data-id="(\\d+)"[^>]*>/i', $html, $matches);
    // The page renders the selector container more than once; dedupe but keep first-seen order.
    $roundIds = isset($matches[1]) ? array_values(array_unique(array_map('intval', $matches[1]))) : [];
    $diag['stage'] = 'parse_rounds';
    $diag['roundIds'] = $roundIds;
    if (empty($roundIds)) {
        // Either melee changed its markup, or we were served a login/challenge page.
        $diag['error'] = 'No round-selector buttons found on the tournament page.';
        $diag['pageTitle'] = preg_match('/<title>(.*?)<\/title>/is', $html, $t) ? trim($t[1]) : null;
        return false;
    }
    // Latest round first, so ties resolve to the latest round with the full field.
    $diag['stage'] = 'probe_rounds';
    $diag['roundProbes'] = [];
    $bestRoundId = 0;
    $bestCount = 0;
    foreach (array_reverse($roundIds) as $roundId) {
        $probe = null;
        $count = meleeRoundStandingsCount($roundId, $probe);
        $diag['roundProbes'][] = $probe;
        if ($count > $bestCount) {
            $bestCount = $count;
            $bestRoundId = $roundId;
        }
    }
    $diag['bestCount'] = $bestCount;
    if ($bestRoundId > 0) {
        $diag['pickedRoundId'] = $bestRoundId;
        return $bestRoundId;
    }
    // Nothing probed successfully (melee throttling / transient failure) — fall back to
    // the last round in document order rather than importing nothing.
    $diag['error'] = 'Every round probe came back empty (melee throttling, or all rounds have empty standings).';
    $diag['pickedRoundId'] = end($roundIds);
    $diag['pickedVia'] = 'fallback_last_round';
    return end($roundIds);
}

// $failureReason is filled in with the specific reason when this returns false, and $diag with
// the full step-by-step trace, so callers can surface something more useful than
// "Failed to import tournament from melee.gg."
function importMeleeTournamentById($tournamentId, $progressCallback = null, &$failureReason = null, &$diag = null) {
    $failureReason = null;
    $diag = ['parserVersion' => MELEE_PARSER_VERSION, 'stage' => 'start'];
    $errors = [];
    $collect = function($update) use ($progressCallback, &$failureReason, &$errors) {
        if (isset($update['error'])) {
            if ($failureReason === null) $failureReason = $update['error'];
            if (count($errors) < 10) $errors[] = $update['error'];
        }
        if ($progressCallback) $progressCallback($update);
    };

    $roundDiag = null;
    $roundId = getHighestRoundIdFromTournament($tournamentId, $roundDiag);
    $diag['roundSelection'] = $roundDiag;
    if (!$roundId) {
        $diag['stage'] = $roundDiag['stage'] ?? 'round_selection';
        $failureReason = $roundDiag['error'] ?? 'Could not determine a roundId for this tournament.';
        $collect(['error' => $failureReason]);
        $diag['errors'] = $errors;
        return false;
    }
    $diag['roundId'] = $roundId;

    $diag['stage'] = 'db_connect';
    $conn = GetLocalMySQLConnection();
    if ($conn === false) {
        $failureReason = 'Error connecting to the database.';
        $collect(['error' => $failureReason]);
        $diag['errors'] = $errors;
        return false;
    }

    $diag['stage'] = 'parse_tournament';
    $result = parseMeleeTournament($roundId, $conn, $collect);
    $diag['parseResult'] = is_bool($result) ? ($result ? 'true' : 'false') : $result;
    if (is_numeric($result) && $result > 0) {
        $conn->close();
        return $result;
    }
    // If parseMeleeTournament returns true, fetch the tournamentId
    $checkQuery = "SELECT tournamentId FROM meleetournament WHERE roundId = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $roundId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult && $row = $checkResult->fetch_assoc()) {
        $checkStmt->close();
        $conn->close();
        return $row['tournamentId'];
    }
    $checkStmt->close();
    $conn->close();
    $diag['stage'] = 'no_tournament_row';
    if ($failureReason === null) {
        $failureReason = "Parsed round $roundId but no tournament row was written.";
    }
    $diag['errors'] = $errors;
    return false;
}

// If run directly (not included), allow CLI testing
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $id = (int)$argv[1];
    importMeleeTournamentById($id, function($update) {
        echo json_encode($update, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
    });
}
