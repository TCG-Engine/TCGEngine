<?php
// MeleeTournamentParserAPI.php
// API-safe version of the parser for FindOrImportMeleeTournament.php

include_once '../SWUDeck/Custom/CardIdentifiers.php';
set_time_limit(1800);
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once 'MeleeTournamentParser.php';

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
    $roundIds = array_map('intval', $matches[1]);
    return max($roundIds);
}

function importMeleeTournamentById($tournamentId, $progressCallback = null) {
    $roundId = getHighestRoundIdFromTournament($tournamentId);
    if (!$roundId) {
        if ($progressCallback) $progressCallback(['error' => 'Could not determine roundId for tournament.']);
        return false;
    }
    $conn = GetLocalMySQLConnection();
    if ($conn === false) {
        if ($progressCallback) $progressCallback(['error' => 'Error connecting to the database.']);
        return false;
    }
    $result = parseMeleeTournament($roundId, $conn, $progressCallback);
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
    return false;
}

// If run directly (not included), allow CLI testing
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $id = (int)$argv[1];
    importMeleeTournamentById($id, function($update) {
        echo json_encode($update, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
    });
}
