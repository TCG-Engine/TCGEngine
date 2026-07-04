<?php
// Client-polled post-game nav for GA matches. Given a game (+ viewer auth), returns where to go next:
// sideboard, the next game, or match-over. Derived from the Match object (single source of truth) +
// this game's ref (MatchId/GameNumber) stored in its gamestate. Adapted from SWUSim/EndGameInfo.php.
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/MatchFlow.php';   // Match model + GAReadGameMatchRef / GAGetGameWinner

$gameName        = TryGet("gameName");
$requestPlayerID = TryGet("playerID");
$viewerInfo      = NormalizeViewerIdentity($requestPlayerID);
$authKey         = TryGet("authKey", "");
if ($viewerInfo["viewerID"] === "" || !SimGameValidateViewerAuth('GrandArchiveSim', $gameName, $viewerInfo, $authKey)) {
    echo json_encode(['error' => 'auth']);
    exit;
}
ParseGamestate();   // loads the game's gamestate (global $gameName) so the refs are readable

$ref        = GAReadGameMatchRef();
$gameWinner = GAGetGameWinner();
$seat       = intval($requestPlayerID);
$m          = ($ref && !empty($ref['matchId'])) ? GAReadMatch($ref['matchId']) : null;

$matchId = $ref['matchId'] ?? null;
$gameNumber = $ref['gameNumber'] ?? null;
$sideboardPending = false;
$nextGameName = null;
$matchWinner = 0;
$wins = null;
$bestOf = 1;
$convertible = false;
$convertRequestedByMe = false;
$convertRequestedByOpp = false;
$rematchRequestedByMe = false;
$rematchRequestedByOpp = false;

if (is_array($m)) {
    // A rematch spawned a NEW match — steer this finished game into it (GA stores the pointer on the
    // old match, no pointer files). Report the new match's id/state so the client's buttons follow it.
    if (!empty($m['rematchInto'])) {
        $newM = GAReadMatch($m['rematchInto']);
        if (is_array($newM)) {
            $matchId = $m['rematchInto'];
            if (!empty($m['rematchGameName'])) {
                $nextGameName = strval($m['rematchGameName']);              // quick rematch → game 1 waiting
            } else if (($newM['state'] ?? '') === 'sideboarding') {
                $sideboardPending = true;                                   // full rematch → sideboard first
            }
            $bestOf = intval($newM['bestOf'] ?? 1);
            echo json_encode([
                'gameWinner' => $gameWinner, 'didWin' => ($gameWinner === $seat), 'isMatch' => true,
                'matchId' => $matchId, 'gameNumber' => $gameNumber, 'bestOf' => $bestOf,
                'sideboardPending' => $sideboardPending, 'nextGameName' => $nextGameName,
                'matchWinner' => 0, 'seriesOver' => false, 'wins' => $newM['wins'] ?? null,
                'convertible' => false, 'convertRequestedByMe' => false, 'convertRequestedByOpp' => false,
                'rematchRequestedByMe' => false, 'rematchRequestedByOpp' => false,
            ]);
            exit;
        }
    }

    $wins = $m['wins'] ?? null;
    $bestOf = intval($m['bestOf'] ?? 1);
    $matchWinner = GAMatchWinner($m);
    // A later game exists in the match → the series has moved on; navigate there.
    foreach (($m['games'] ?? []) as $g) {
        if (intval($g['gameNumber'] ?? 0) > intval($gameNumber)) { $nextGameName = strval($g['gameName']); break; }
    }
    // Sideboarding is pending when the match is mid-sideboard, no next game exists yet, and the series
    // isn't over — i.e. this ended game is waiting for both seats to submit their decks.
    $sideboardPending = (($m['state'] ?? '') === 'sideboarding') && $nextGameName === null && $matchWinner === 0;

    // A finished Bo1 can be converted to a Bo3 by mutual agreement.
    $convertible = ($bestOf === 1) && ($matchWinner !== 0) && (($m['state'] ?? '') === 'complete');
    $opp = ($seat === 1) ? 2 : 1;
    $convertRequestedByMe  = !empty($m['convertRequests'][strval($seat)]);
    $convertRequestedByOpp = !empty($m['convertRequests'][strval($opp)]);
    $rematchRequestedByMe  = !empty($m['rematchRequests'][strval($seat)]);
    $rematchRequestedByOpp = !empty($m['rematchRequests'][strval($opp)]);
}

echo json_encode([
    'gameWinner'            => $gameWinner,
    'didWin'               => ($gameWinner === $seat),
    'isMatch'              => is_array($m),
    'matchId'              => $matchId,
    'gameNumber'           => $gameNumber,
    'bestOf'               => $bestOf,
    'sideboardPending'     => $sideboardPending,
    'nextGameName'         => $nextGameName,
    'matchWinner'          => $matchWinner,
    'seriesOver'           => ($matchWinner !== 0),
    'wins'                 => $wins,
    'convertible'          => $convertible,
    'convertRequestedByMe' => $convertRequestedByMe,
    'convertRequestedByOpp'=> $convertRequestedByOpp,
    'rematchRequestedByMe' => $rematchRequestedByMe,
    'rematchRequestedByOpp'=> $rematchRequestedByOpp,
]);
