<?php
// DevTools/test_submit_game_result.php
// Simple script to POST test payloads to the local SubmitGameResult.php endpoint.
// Usage: php DevTools/test_submit_game_result.php [baseUrl]
// baseUrl defaults to http://localhost/TCGEngine/APIs/SubmitGameResult.php
require_once "../APIKeys/APIKeys.php";

if(isset($isProduction) && $isProduction) {
    exit(1);
}

$baseUrl = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://localhost/TCGEngine/APIs/SubmitGameResult.php';

function postJson($url, $data) {
    $ch = curl_init($url);
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$httpCode, $response, $err];
}

// Replace these tokens with real values from your oauth_access_tokens table for testing.
$validToken = 'e12cb1722a0cdf7ad22adf9578904235ef6ac720';
$expiredToken = 'EXPIRED_TOKEN_PLACEHOLDER';

// Minimal valid payload structure expected by SubmitGameResult.php
$common = [
    'apiKey' => $petranakiAPIKey,
    'winner' => 1,
    'firstPlayer' => 1,
    'p1id' => '5',
    'p2id' => '5',
    'p1DeckLink' => 'https://swustats.net/?gameName=123',
    'p2DeckLink' => 'https://swustats.net/?gameName=456',
    'player1' => json_encode([
        'leader' => 'LeaderA',
        'base' => 'Green',
        'opposingHero' => 'LeaderB',
        'opposingBaseColor' => 'Yellow',
        'cardResults' => [],
        'turnResults' => []
    ]),
    'player2' => json_encode([
        'leader' => 'LeaderB',
        'base' => 'Yellow',
        'opposingHero' => 'LeaderA',
        'opposingBaseColor' => 'Green',
        'cardResults' => [],
        'turnResults' => []
    ]),
    'round' => 5,
    'winnerHealth' => 10,
    'gameName' => 'testgame',
    'winHero' => 'H1',
    'loseHero' => 'H2',
    'winnerDeck' => 'DeckA',
    'loserDeck' => 'DeckB'
];

$tests = [
    'both_valid' => array_merge($common, [
        'p1SWUStatsToken' => $validToken,
        'p2SWUStatsToken' => $validToken
    ]),
    'p1_expired' => array_merge($common, [
        'p1SWUStatsToken' => $expiredToken,
        'p2SWUStatsToken' => $validToken
    ]),
    'p2_expired' => array_merge($common, [
        'p1SWUStatsToken' => $validToken,
        'p2SWUStatsToken' => $expiredToken
    ]),
    'both_expired' => array_merge($common, [
        'p1SWUStatsToken' => $expiredToken,
        'p2SWUStatsToken' => $expiredToken
    ]),
    'no_tokens' => $common
];
foreach ($tests as $name => $payload) {
    echo "<br>=== Test: $name ===<br>";
    list($code, $resp, $err) = postJson($baseUrl, $payload);
    if ($err) {
        echo "cURL error: $err<br>";
    }
    echo "HTTP: $code<br>";
    echo "Response:<br>" . $resp . "<br>";
}

echo "<br>Done.<br>";

?>
