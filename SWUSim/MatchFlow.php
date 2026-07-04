<?php
// SWUSim/MatchFlow.php — thin delegation shim over the shared Core/Match orchestration.
// The real logic lives in Core/Match/MatchFlow.php; these wrappers preserve the SWU* names
// (and the implicit 'SWUSim' rootName) that existing callers + the tdd-regression suite use.
// SWUSim's game-specific hook bodies + registration live in SWUSim/MatchHooks.php.
require_once __DIR__ . '/Match.php';                    // SWU* match-level delegators + Core/Match/Match.php
require_once __DIR__ . '/../Core/Match/MatchFlow.php';  // Match* orchestration
require_once __DIR__ . '/MatchHooks.php';               // SWUSim hook bodies + MatchRegisterHooks('SWUSim', ...)

function SWUMatchRefPath($gameName)                 { return MatchRefPath('SWUSim', $gameName); }
function SWUWriteMatchRef($gameName, $matchId, $gameNumber) { return MatchWriteRef('SWUSim', $gameName, $matchId, $gameNumber); }
function SWUReadMatchRef($gameName)                 { return MatchReadRef('SWUSim', $gameName); }
function SWUNextGamePointerPath($gameName)          { return MatchNextGamePointerPath('SWUSim', $gameName); }
function SWUSideboardPointerPath($gameName)         { return MatchSideboardPointerPath('SWUSim', $gameName); }
function SWUResolveOpponent($gameName, $userId)     { return MatchResolveOpponent('SWUSim', $gameName, $userId); }
function SWUAreGamePlayersBlocked($gameName)        { return MatchAreGamePlayersBlocked('SWUSim', $gameName); }
function SWUCreateMatchFromLobby($lobby)            { return MatchCreateFromLobby('SWUSim', $lobby); }
function SWUSpawnNextMatchGameWithDecks($matchId, $firstPlayer, $priorGame, $resolvedDecks) {
    return MatchSpawnNextGameWithDecks('SWUSim', $matchId, $firstPlayer, $priorGame, $resolvedDecks);
}
function SWUSpawnNextMatchGame($matchId, $loserSeat, $priorGame) { return MatchSpawnNextGame('SWUSim', $matchId, $loserSeat, $priorGame); }
function SWUSideboardTimeoutCheck($matchId)         { return MatchSideboardTimeoutCheck('SWUSim', $matchId); }
function SWUMaybeSpawnAfterSideboard($matchId)      { return MatchMaybeSpawnAfterSideboard('SWUSim', $matchId); }
function SWUConcedeMatch($matchId, $concedingSeat)  { return MatchConcede('SWUSim', $matchId, $concedingSeat); }
function SWUReapStaleMatches($maxAgeSeconds = 86400, $nowTs = null) { return MatchReapStale('SWUSim', $maxAgeSeconds, $nowTs); }
function SWURequestRematch($oldMatchId, $seat, $bestOf, $sideboard) { return MatchRequestRematch('SWUSim', $oldMatchId, $seat, $bestOf, $sideboard); }
function SWUAcceptRematch($oldMatchId)              { return MatchAcceptRematch('SWUSim', $oldMatchId); }
function SWURequestConvertToBo3($matchId, $seat)    { return MatchRequestConvertToBo3('SWUSim', $matchId, $seat); }
function SWUAcceptConvertToBo3($matchId)            { return MatchAcceptConvertToBo3('SWUSim', $matchId); }
function SWUMatchInactivityForfeit($gameName, $inactiveSeat) { return MatchInactivityForfeit('SWUSim', $gameName, $inactiveSeat); }
function SWUAfterActionMatchHook($folderPath, $gameName)     { return MatchAfterActionHook($folderPath, $gameName); }
