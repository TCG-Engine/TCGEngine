<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/Match/MatchFlow.php';
include_once __DIR__ . '/MatchHooks.php';

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', TryGet('gameName', ''));
$requestPlayerID = TryGet('playerID');
$viewerInfo = NormalizeViewerIdentity($requestPlayerID);
$authKey = TryGet('authKey', '');
if ($viewerInfo['viewerID'] === '' || !SimGameValidateViewerAuth('AzukiSim', $gameName, $viewerInfo, $authKey)) {
    echo json_encode(['error' => 'auth']);
    exit;
}

$ref = MatchReadRef('AzukiSim', $gameName);
$seat = intval($requestPlayerID);
$match = ($ref && !empty($ref['matchId'])) ? MatchRead('AzukiSim', $ref['matchId']) : null;
$gameWinner = 0;
if (is_array($match)) {
    foreach (($match['games'] ?? []) as $game) {
        if (strval($game['gameName'] ?? '') === $gameName) {
            $gameWinner = intval($game['winner'] ?? 0);
            break;
        }
    }
}

$nextGameName = null;
$nextPointer = MatchNextGamePointerPath('AzukiSim', $gameName);
if ($nextPointer !== '' && is_file($nextPointer)) {
    $decoded = json_decode(file_get_contents($nextPointer), true);
    $candidate = is_array($decoded) ? strval($decoded['nextGameName'] ?? '') : '';
    if ($candidate !== '') $nextGameName = $candidate;
}

$opponent = ($seat === 1) ? 2 : 1;
echo json_encode([
    'gameWinner' => $gameWinner,
    'didWin' => ($gameWinner === $seat),
    'isMatch' => is_array($match),
    'nextGameName' => $nextGameName,
    'seriesOver' => is_array($match) && MatchWinner($match) !== 0,
    'rematchRequestedByMe' => is_array($match) && !empty($match['rematchRequests'][strval($seat)]),
    'rematchRequestedByOpp' => is_array($match) && !empty($match['rematchRequests'][strval($opponent)]),
]);

