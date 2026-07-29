<?php
// MeleeTournamentParserAPI.php
// API-safe version of the parser for FindOrImportMeleeTournament.php

include_once '../SWUDeck/Custom/CardIdentifiers.php';
set_time_limit(1800);
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once 'MeleeTournamentParser.php';

// Returns how many standings rows melee.gg has for this round (0 if none / on error).
// Top-cut bracket rounds can return a valid JSON payload with zero records, so a round
// must be probed before it is used as the import source.
function meleeRoundStandingsCount($roundId) {
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
    curl_close($ch);
    if (!$response) return 0;
    $json = json_decode($response, true);
    if (!is_array($json)) return 0;
    if (isset($json['recordsTotal'])) return (int)$json['recordsTotal'];
    if (isset($json['data']) && is_array($json['data'])) return count($json['data']);
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
function getHighestRoundIdFromTournament($tournamentId) {
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
    curl_close($ch);
    if (!$html) return false;
    $matches = [];
    preg_match_all('/<button[^>]*class="[^"]*round-selector[^"]*"[^>]*data-id="(\\d+)"[^>]*>/i', $html, $matches);
    if (!isset($matches[1]) || empty($matches[1])) return false;
    // The page renders the selector container more than once; dedupe but keep first-seen order.
    $roundIds = array_values(array_unique(array_map('intval', $matches[1])));
    if (empty($roundIds)) return false;
    // Latest round first, so ties resolve to the latest round with the full field.
    $bestRoundId = 0;
    $bestCount = 0;
    foreach (array_reverse($roundIds) as $roundId) {
        $count = meleeRoundStandingsCount($roundId);
        if ($count > $bestCount) {
            $bestCount = $count;
            $bestRoundId = $roundId;
        }
    }
    if ($bestRoundId > 0) return $bestRoundId;
    // Nothing probed successfully (melee throttling / transient failure) — fall back to
    // the last round in document order rather than importing nothing.
    return end($roundIds);
}

// $failureReason is filled in with the specific reason when this returns false, so callers
// can surface something more useful than "Failed to import tournament from melee.gg."
function importMeleeTournamentById($tournamentId, $progressCallback = null, &$failureReason = null) {
    $failureReason = null;
    $collect = function($update) use ($progressCallback, &$failureReason) {
        if (isset($update['error']) && $failureReason === null) $failureReason = $update['error'];
        if ($progressCallback) $progressCallback($update);
    };
    $roundId = getHighestRoundIdFromTournament($tournamentId);
    if (!$roundId) {
        $failureReason = 'Could not determine a roundId for this tournament (no round-selector buttons found on the melee.gg page).';
        $collect(['error' => $failureReason]);
        return false;
    }
    $conn = GetLocalMySQLConnection();
    if ($conn === false) {
        $failureReason = 'Error connecting to the database.';
        $collect(['error' => $failureReason]);
        return false;
    }
    $result = parseMeleeTournament($roundId, $conn, $collect);
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
    if ($failureReason === null) {
        $failureReason = "Parsed round $roundId but no tournament row was written.";
    }
    return false;
}

// If run directly (not included), allow CLI testing
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $id = (int)$argv[1];
    importMeleeTournamentById($id, function($update) {
        echo json_encode($update, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
    });
}
