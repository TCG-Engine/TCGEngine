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
function SWUSideboardWarningCheck($matchId)         { return MatchSideboardWarningCheck('SWUSim', $matchId); }
function SWUMaybeSpawnAfterSideboard($matchId)      { return MatchMaybeSpawnAfterSideboard('SWUSim', $matchId); }
// Between the games of a Bo3 against a bot (a 1v1 room's "Fill Seat with Bot", e.g. Dualing Suns), the bot never sideboards:
// it keeps the list it played and is Ready at once, so the human's Ready starts the next game. Without this the human
// waited forever — a private match has no sideboard clock. Limited readies its own bots (SWULimitedReadyBotSeats).
function SWUReadyBotSideboardSeats($matchId): void {
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || !empty($m['limited']) || ($m['state'] ?? '') !== 'sideboarding') return;
    foreach (($m['players'] ?? []) as $seat => $p) {
        if (strval($p['botProfile'] ?? '') === '' || !empty($m['sideboard'][strval($seat)]['ready'])) continue;
        SWUReadySideboardSeat($matchId, intval($seat), $p['currentDeck'] ?? $p['originalDeck'] ?? []);
    }
}
// The in-game concede input (10007) runs with the live gamestate loaded, so the open game's detail (pregameDone — is
// this match rated at all?) is captured here; BlockedUsers.php's forfeit has no gamestate and passes none.
function SWUConcedeMatch($matchId, $concedingSeat) {
    $detail = (function_exists('SWUCaptureCurrentGameDetail') && function_exists('GetTurnNumber')) ? SWUCaptureCurrentGameDetail() : null;
    // SWU-PGN: the open game ends here with no gamestate winner — finish its recording before the match is sealed.
    if ($detail !== null && function_exists('SwuPgnRecFinishConcede')) SwuPgnRecFinishConcede(intval($concedingSeat) === 1 ? 2 : 1);
    return MatchConcede('SWUSim', $matchId, $concedingSeat, $detail);
}
function SWUReapStaleMatches($maxAgeSeconds = 86400, $nowTs = null) { return MatchReapStale('SWUSim', $maxAgeSeconds, $nowTs); }
function SWURequestRematch($oldMatchId, $seat, $bestOf, $sideboard) { return MatchRequestRematch('SWUSim', $oldMatchId, $seat, $bestOf, $sideboard); }
function SWUAcceptRematch($oldMatchId)              { return MatchAcceptRematch('SWUSim', $oldMatchId); }
function SWURequestConvertToBo3($matchId, $seat)    { return MatchRequestConvertToBo3('SWUSim', $matchId, $seat); }
function SWUAcceptConvertToBo3($matchId)            { return MatchAcceptConvertToBo3('SWUSim', $matchId); }
// SWUMatchInactivityForfeit() deleted 2026-09-17 with Core's MatchInactivityForfeit() — no callers,
// 2-seat only. The inactivity clock's SWUApplyKick() replaces it (SWUSim/Custom/InactivityClock.php).
function SWUAfterActionMatchHook($folderPath, $gameName)     { return MatchAfterActionHook($folderPath, $gameName); }
