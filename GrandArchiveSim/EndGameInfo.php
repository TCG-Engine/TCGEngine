<?php
// Client-polled post-game nav for GA matches. Given a game (+ viewer auth), returns where to go next:
// sideboard, the next game, or match-over. Derived from the shared Match object (single source of
// truth) + the per-game pointer files (MatchRef / NextGame / Sideboard) written by Core/Match.
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/Match/MatchFlow.php';   // shared Match model + pointer files
include_once __DIR__ . '/MatchHooks.php';                // registers GA hooks

$gameName        = preg_replace('/[^A-Za-z0-9_]/', '', TryGet("gameName", ""));
$requestPlayerID = TryGet("playerID");
$viewerInfo      = NormalizeViewerIdentity($requestPlayerID);
$authKey         = TryGet("authKey", "");
if ($viewerInfo["viewerID"] === "" || !SimGameValidateViewerAuth('GrandArchiveSim', $gameName, $viewerInfo, $authKey)) {
    echo json_encode(['error' => 'auth']);
    exit;
}

$ref  = MatchReadRef('GrandArchiveSim', $gameName);
$seat = intval($requestPlayerID);
$m    = ($ref && !empty($ref['matchId'])) ? MatchRead('GrandArchiveSim', $ref['matchId']) : null;

// This game's record in the match → authoritative winner + gameNumber (survives gamestate eviction).
$gameRec = null;
if (is_array($m)) { foreach (($m['games'] ?? []) as $g) { if (($g['gameName'] ?? '') === $gameName) { $gameRec = $g; break; } } }
$gameWinner = intval($gameRec['winner'] ?? 0);
$gameNumber = intval($gameRec['gameNumber'] ?? ($ref['gameNumber'] ?? 0));

$matchId = $ref['matchId'] ?? null;
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
    $wins = $m['wins'] ?? null;
    $bestOf = intval($m['bestOf'] ?? 1);
    $matchWinner = MatchWinner($m);

    // A later game in THIS match → the series moved on (normal Bo3 advance / convert game 2). Navigate there.
    foreach (($m['games'] ?? []) as $g) {
        if (intval($g['gameNumber'] ?? 0) > $gameNumber) { $nextGameName = strval($g['gameName']); break; }
    }

    // Same-match sideboarding (normal between-games OR a convert-to-Bo3 that re-entered sideboarding):
    // pending when the match is mid-sideboard, no next game exists yet, and the series isn't over.
    $sideboardPending = (($m['state'] ?? '') === 'sideboarding') && $nextGameName === null && $matchWinner === 0;

    // A finished Bo1 can be converted to a Bo3 by mutual agreement.
    $opp = ($seat === 1) ? 2 : 1;
    $convertible = ($bestOf === 1) && ($matchWinner !== 0) && (($m['state'] ?? '') === 'complete');
    $convertRequestedByMe  = !empty($m['convertRequests'][strval($seat)]);
    $convertRequestedByOpp = !empty($m['convertRequests'][strval($opp)]);
    $rematchRequestedByMe  = !empty($m['rematchRequests'][strval($seat)]);
    $rematchRequestedByOpp = !empty($m['rematchRequests'][strval($opp)]);
}

// Full rematch (10016): MatchAcceptRematch writes a Sideboard.json on THIS finished game pointing to a
// NEW sideboarding match. Follow it so the menu steers into the new match's sideboard.
$sidePtr = MatchSideboardPointerPath('GrandArchiveSim', $gameName);
if ($sidePtr !== '' && is_file($sidePtr)) {
    $sp = json_decode(file_get_contents($sidePtr), true);
    $sideMatchId = is_array($sp) ? strval($sp['matchId'] ?? '') : '';
    if ($sideMatchId !== '' && $sideMatchId !== strval($ref['matchId'] ?? '')) {
        $sm = MatchRead('GrandArchiveSim', $sideMatchId);
        if (is_array($sm) && ($sm['state'] ?? '') === 'sideboarding') {
            $sideboardPending = true;
            $matchId = $sideMatchId;
            $bestOf = intval($sm['bestOf'] ?? $bestOf);
        }
    }
}

// Quick rematch (10013): MatchSpawnNextGameWithDecks wrote a NextGame.json on THIS game pointing to
// game 1 of the NEW match. Follow it if we haven't already found a same-match next game.
if ($nextGameName === null) {
    $ngPtr = MatchNextGamePointerPath('GrandArchiveSim', $gameName);
    if ($ngPtr !== '' && is_file($ngPtr)) {
        $ng = json_decode(file_get_contents($ngPtr), true);
        $ngName = is_array($ng) ? strval($ng['nextGameName'] ?? '') : '';
        if ($ngName !== '') {
            $nextGameName = $ngName;
            $ngRef = MatchReadRef('GrandArchiveSim', $ngName); // point buttons at the new match
            if (is_array($ngRef) && !empty($ngRef['matchId'])) {
                $matchId = strval($ngRef['matchId']);
                $nm = MatchRead('GrandArchiveSim', $matchId);
                if (is_array($nm)) $bestOf = intval($nm['bestOf'] ?? $bestOf);
            }
        }
    }
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
