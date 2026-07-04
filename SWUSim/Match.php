<?php
// SWUSim/Match.php — thin delegation shim over the shared Core/Match state machine.
// The real logic lives in Core/Match/Match.php; these wrappers preserve the SWU* names
// (and the implicit 'SWUSim' rootName) that existing callers + the tdd-regression suite use.
require_once __DIR__ . '/../Core/Match/Match.php';
include_once __DIR__ . '/../AppCore/SWU/Formats.php'; // preserve prior side-effect load

if (!defined('SWU_SIDEBOARD_SECONDS')) define('SWU_SIDEBOARD_SECONDS', MATCH_SIDEBOARD_SECONDS);

function SWUMatchesDir()                       { return MatchesDir('SWUSim'); }
function SWUMatchPath($matchId)                { return MatchPath('SWUSim', $matchId); }
function SWUNextMatchId()                      { return MatchNextId('SWUSim'); }
function SWUReadMatch($matchId)                { return MatchRead('SWUSim', $matchId); }
function SWUWriteMatch(array $match)           { return MatchWrite($match); }
function SWUWithMatchLock($matchId, callable $fn) { return MatchWithLock('SWUSim', $matchId, $fn); }
function SWUCreateMatch($rootName, $format, $queueType, $players) { return MatchCreate($rootName, $format, $queueType, $players); }
function SWUMatchIsOver(array $match)          { return MatchIsOver($match); }
function SWUMatchWinner(array $match)          { return MatchWinner($match); }
function SWURecordGameResult($matchId, $gameName, $winnerSeat, $roundNumber = null) {
    return MatchRecordGameResult('SWUSim', $matchId, $gameName, $winnerSeat, $roundNumber);
}
function SWUBeginSideboarding($matchId, $loserSeat) { return MatchBeginSideboarding('SWUSim', $matchId, $loserSeat); }
function SWUSideboardSeatReady(array $m, $seat)     { return MatchSideboardSeatReady($m, $seat); }
function SWUSideboardBothReady(array $m)            { return MatchSideboardBothReady($m); }
function SWUSubmitSideboardDeck($matchId, $seat, $resolvedDeck) { return MatchSubmitSideboardDeck('SWUSim', $matchId, $seat, $resolvedDeck); }
